<?php

namespace App\Console\Commands;

use App\Helpers\FileHelper;
use App\Helpers\KeycloakHelper;
use App\Models\Category;
use App\Models\EducationMaterial;
use App\Models\EducationMaterialCategory;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncEducationMaterialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hi:sync-education-material-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync materials data from global to open library';

    /**
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle()
    {
        // Get eduction materials from Global.
        $globalEducationMaterials = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-education-materials-for-open-library'));
        $educationMaterials = DB::table('education_materials')->where('global', true)->get();

        // Remove old files.
        if ($educationMaterials) {
            foreach ($educationMaterials as $educationMaterial) {
                $fileIDs = array_values(get_object_vars(json_decode($educationMaterial->file_id)));
                File::whereIn('id', $fileIDs)->delete();
            }
        }

        // Import materials from to library.
        $this->output->progressStart(count($globalEducationMaterials));
        $globalEducationMaterialIds = [];
        foreach ($globalEducationMaterials as $globalEducationMaterial) {
            $this->output->progressAdvance();
            $globalEducationMaterialIds[] = $globalEducationMaterial->id;
            DB::table('education_materials')->updateOrInsert(
                [
                    'global_education_material_id' => $globalEducationMaterial->id,
                    'global' => true,
                ],
                [
                    'title' => json_encode($globalEducationMaterial->title),
                    'file_id' => json_encode($globalEducationMaterial->file_id),
                    'global_education_material_id' => $globalEducationMaterial->id,
                    'auto_translated' => json_encode($globalEducationMaterial->auto_translated),
                    'status' => 'approved',
                    'global' => true,
                    'slug' => Str::slug($globalEducationMaterial->title->en),
                    'deleted_at' => $globalEducationMaterial->deleted_at ? Carbon::parse($globalEducationMaterial->deleted_at) : $globalEducationMaterial->deleted_at,
                ]
            );

            $filesIDs = array_values(get_object_vars($globalEducationMaterial->file_id));
            $files = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-education-material-files', ['file_ids' => $filesIDs]));
            $newFileIDs = $globalEducationMaterial->file_id;
            $education = EducationMaterial::withTrashed()->where('global_education_material_id', $globalEducationMaterial->id)->where('global', true)->first();
            if (!$education->created_at) {
                $education->update([
                    'created_at' => Carbon::now(),
                ]);
            }
            if (!empty($files)) {
                foreach ($files as $file) {
                    $file_url = env('GLOBAL_ADMIN_SERVICE_URL') . '/file/' . $file->id;
                    $file_path = File::EDUCATION_MATERIAL_PATH . '/' . $file->filename;

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
                                    File::EDUCATION_MATERIAL_THUMBNAIL_PATH
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
                                    File::EDUCATION_MATERIAL_THUMBNAIL_PATH
                                );

                                if ($thumbnailFilePath) {
                                    $record->update([
                                        'thumbnail' => $thumbnailFilePath,
                                    ]);
                                }
                            }
                            // Update file id with new created id.
                            foreach ($newFileIDs as $key => $value) {
                                if ($file->id == $value) {
                                    $newFileIDs->$key = $record->id;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::debug($e->getMessage());
                    }
                }

                // Update material file id.
                DB::table('education_materials')->where('id', $education->id)->update(['file_id' => json_encode($newFileIDs)]);
            }

            // Create/Update education material categories.
            EducationMaterialCategory::where('education_material_id', $education->id)->delete();
            $globalEducationMaterialCategories = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-education-material-categories-for-open-library', ['id' => $globalEducationMaterial->id]));
            foreach ($globalEducationMaterialCategories as $globalEducationMaterialCategory) {
                $category = Category::where('global_category_id', $globalEducationMaterialCategory->category_id)->first();
                if ($category) {
                    EducationMaterialCategory::create([
                        'education_material_id' => $education->id,
                        'category_id' => $category->id,
                    ]);
                }
            }
        }

        // Remove the previous global synced.
        EducationMaterial::where('global_education_material_id', '<>', null)
            ->whereNotIn('global_education_material_id', $globalEducationMaterialIds)->delete();

        $this->output->progressFinish();

        $this->info('Education material data has been sync successfully');
    }
}
