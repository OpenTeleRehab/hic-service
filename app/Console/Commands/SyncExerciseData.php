<?php

namespace App\Console\Commands;

use App\Helpers\FileHelper;
use App\Helpers\KeycloakHelper;
use App\Models\AdditionalField;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncExerciseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hi:sync-exercise-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync exercises data from global to open library';

    /**
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle()
    {
        // Sync exercise data.
        $globalExercises = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-exercises-for-open-library'));

        // Remove existing global data before import.
        $exercises = Exercise::withTrashed()->where('global', true)->get();
        if ($exercises) {
            foreach ($exercises as $exercise) {
                // Remove files.
                $removeFileIDs = $exercise->files()->pluck('id')->toArray();
                foreach ($removeFileIDs as $removeFileID) {
                    $removeFile = File::find($removeFileID);
                    $removeFile->delete();
                }
                // Remove exercise file in exercise file table.
                DB::table('exercise_file')->where('exercise_id', $exercise->id)->delete();
            }
        }

        // Import global exercises to library.
        $this->output->progressStart(count($globalExercises));
        $globalExerciseIds = [];
        foreach ($globalExercises as $globalExercise) {
            $this->output->progressAdvance();
            $globalExerciseIds[] = $globalExercise->id;
            DB::table('exercises')->updateOrInsert(
                [
                    'global_exercise_id' => $globalExercise->id,
                    'global' => true,
                ],
                [
                    'title' => json_encode($globalExercise->title),
                    'sets' => $globalExercise->sets,
                    'reps' => $globalExercise->reps,
                    'status' => 'approved',
                    'global_exercise_id' => $globalExercise->id,
                    'global' => true,
                    'auto_translated' => json_encode($globalExercise->auto_translated),
                    'slug' => Str::slug($globalExercise->title->en),
                    'updated_at' => Carbon::now(),
                    'deleted_at' => $globalExercise->deleted_at ? Carbon::parse($globalExercise->deleted_at) : $globalExercise->deleted_at,
                ]
            );

            $newExercise = Exercise::withTrashed()->where('global_exercise_id', $globalExercise->id)->where('global', true)->first();
            if (!$newExercise->created_at) {
                $newExercise->update([
                    'created_at' => Carbon::now(),
                ]);
            }
            // Add files.
            $files = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-exercise-files', ['exercise_id' => $globalExercise->id]));
            if (!empty($files)) {
                $index = 0;
                foreach ($files as $file) {
                    $file_url = env('GLOBAL_ADMIN_SERVICE_URL') . '/file/' . $file->id;
                    $file_path = File::EXERCISE_PATH . '/' . $file->filename;

                    try {
                        $file_content = file_get_contents($file_url);
                        $record = File::create([
                            'filename' => $file->filename,
                            'path' => $file_path,
                            'content_type' => $file->content_type,
                            'size' => $file->size,
                        ]);

                        // Save file to storage.
                        Storage::put($file_path, $file_content);
                        if ($record) {
                            if ($file->content_type === 'video/mp4') {
                                $thumbnailFilePath = FileHelper::generateVideoThumbnail(
                                    $record->id,
                                    $file_path,
                                    File::EXERCISE_THUMBNAIL_PATH
                                );

                                if ($thumbnailFilePath) {
                                    $record->update([
                                        'thumbnail' => $thumbnailFilePath,
                                    ]);
                                }
                            }

                            if ($file->content_type === 'application/pdf') {
                                $thumbnailFilePath = FileHelper::generatePdfThumbnail(
                                    $record->id,
                                    $file_path,
                                    File::EXERCISE_THUMBNAIL_PATH
                                );

                                if ($thumbnailFilePath) {
                                    $record->update([
                                        'thumbnail' => $thumbnailFilePath,
                                    ]);
                                }
                            }
                            // Add to exercise file.
                            DB::table('exercise_file')
                                ->insert([
                                    'exercise_id' => $newExercise->id,
                                    'file_id' => $record->id,
                                    'order' => $index,
                                ]);
                        }
                        $index++;
                    } catch (\Exception $e) {
                        Log::debug($e->getMessage());
                    }
                }
            }

            // Create/Update additional fields
            AdditionalField::where('exercise_id', $newExercise->id)->delete();
            $globalExerciseAdditionalFields = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-exercise-additional-fields-for-open-library', ['id' => $globalExercise->id]));
            foreach ($globalExerciseAdditionalFields as $globalExerciseAdditionalField) {
                DB::table('additional_fields')->insert([
                    'field' => json_encode($globalExerciseAdditionalField->field),
                    'value' => json_encode($globalExerciseAdditionalField->value),
                    'exercise_id' => $newExercise->id,
                ]);
            }

            // Create/Update education material categories.
            ExerciseCategory::where('exercise_id', $newExercise->id)->delete();
            $globalExerciseCategories = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-exercise-categories-for-open-library', ['id' => $globalExercise->id]));
            foreach ($globalExerciseCategories as $globalExerciseCategory) {
                $category = Category::where('global_category_id', $globalExerciseCategory->category_id)->first();
                if ($category) {
                    ExerciseCategory::create([
                        'exercise_id' => $newExercise->id,
                        'category_id' => $category->id,
                    ]);
                }
            }
        }

        // Remove the previous global synced.
        Exercise::where('global_exercise_id', '<>', null)
            ->whereNotIn('global_exercise_id', $globalExerciseIds)->delete();

        $this->output->progressFinish();

        $this->info('Exercise data has been sync successfully');
    }
}
