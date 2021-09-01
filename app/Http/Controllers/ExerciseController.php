<?php

namespace App\Http\Controllers;

use App\Events\ApplyExerciseAutoTranslationEvent;
use App\Exports\ExercisesExport;
use App\Helpers\ExerciseHelper;
use App\Helpers\FileHelper;
use App\Http\Resources\ExerciseResource;
use App\Http\Resources\FeaturedResourceResource;
use App\Models\AdditionalField;
use App\Models\EducationMaterial;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\File;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExerciseController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $query = ExerciseHelper::generateFilterQuery($request, with(new Exercise));
        $exercises = $query->paginate($request->get('page_size'));

        $info = [
            'current_page' => $exercises->currentPage(),
            'total_count' => $exercises->total(),
        ];

        return [
            'success' => true,
            'data' => ExerciseResource::collection($exercises),
            'info' => $info,
        ];
    }
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getFeaturedResources(Request $request)
    {
        $exercises = Exercise::where('status', Exercise::STATUS_APPROVED)->get();
        $educationMaterials = EducationMaterial::where('status', Exercise::STATUS_APPROVED)->get();
        $questionnaires = Questionnaire::where('status', Exercise::STATUS_APPROVED)->get();

        return [
            'success' => true,
            'data' => [
                'exercises' =>  FeaturedResourceResource::collection($exercises),
                'education_materials' => FeaturedResourceResource::collection($educationMaterials),
                'questionnaires' => FeaturedResourceResource::collection($questionnaires)
            ]
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $email = $request->get('email');
        $first_name = $request->get('first_name');
        $last_name = $request->get('last_name');
        $hash = !Auth::check() ? $request->get('hash') : null;
        $status = !Auth::check() ? Exercise::STATUS_DRAFT : Exercise::STATUS_PENDING;

        $contributor = ExerciseHelper::updateOrCreateContributor($first_name, $last_name, $email);
        $additionalFields = json_decode($request->get('additional_fields'));

        $exercise = Exercise::create([
            'title' => $request->get('title'),
            'sets' => $request->get('sets'),
            'reps' => $request->get('reps'),
            'status' => $status,
            'hash' => $hash,
            'uploaded_by' => $contributor ? $contributor->id : null,
        ]);

        if (empty($exercise)) {
            return ['success' => false, 'message' => 'error_message.exercise_create'];
        }

        foreach ($additionalFields as $index => $additionalField) {
            AdditionalField::create([
                'field' => $additionalField->field,
                'value' => $additionalField->value,
                'exercise_id' => $exercise->id
            ]);
        }

        // Upload files and attach to Exercise.
        $this->attachFiles($exercise, $request->allFiles());

        // Attach category to exercise.
        $this->attachCategories($exercise, $request->get('categories'));

        return ['success' => true, 'message' => 'success_message.exercise_create'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getConfirmed(Request $request)
    {
        return ['success' => true, 'message' => 'success_message.exercise_update'];
    }

    /**
     * @param \App\Models\Exercise $exercise
     *
     * @return \App\Http\Resources\ExerciseResource
     */
    public function show(Exercise $exercise)
    {
        return new ExerciseResource($exercise);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Exercise $exercise
     *
     * @return array
     */
    public function update(Request $request, Exercise $exercise)
    {
        $exercise->update([
            'title' => $request->get('title'),
            'sets' => $request->get('sets'),
            'reps' => $request->get('reps'),
            'status' => Exercise::STATUS_APPROVED,
            'reviewed_by' => Auth::id()
        ]);

        $additionalFields = json_decode($request->get('additional_fields'));
        $additionalFieldIds = [];
        foreach ($additionalFields as $index => $additionalField) {
            $additionalField = AdditionalField::updateOrCreate(
                [
                    'id' => isset($additionalField->id) ? $additionalField->id : null,
                ],
                [
                    'field' => $additionalField->field,
                    'value' => $additionalField->value,
                    'exercise_id' => $exercise->id
                ]
            );
            $additionalFieldIds[] = $additionalField->id;
        }

        // Remove deleted additional field.
        AdditionalField::where('exercise_id', $exercise->id)
            ->whereNotIn('id', $additionalFieldIds)
            ->delete();

        // Remove files.
        $exerciseFileIDs = $exercise->files()->pluck('id')->toArray();
        $mediaFileIDs = $request->get('media_files', []);
        $removeFileIDs = array_diff($exerciseFileIDs, $mediaFileIDs);
        foreach ($removeFileIDs as $removeFileID) {
            $removeFile = File::find($removeFileID);
            $removeFile->delete();
        }

        // Update ordering.
        foreach ($mediaFileIDs as $index => $mediaFileID) {
            DB::table('exercise_file')
                ->where('exercise_id', $exercise->id)
                ->where('file_id', $mediaFileID)
                ->update(['order' => $index]);
        }

        // Upload files and attach to Exercise.
        $this->attachFiles($exercise, $request->allFiles());

        // Attach category to exercise.
        ExerciseCategory::where('exercise_id', $exercise->id)->delete();
        $this->attachCategories($exercise, $request->get('categories'));

        // Add automatic translation for Exercise.
        event(new ApplyExerciseAutoTranslationEvent($exercise));

        return ['success' => true, 'message' => 'success_message.exercise_update'];
    }

    /**
     * @param \App\Models\Exercise $exercise
     *
     * @return array
     */
    public function reject(Exercise $exercise)
    {
        $exercise->update(['status' => Exercise::STATUS_REJECTED, 'reviewed_by' => Auth::id()]);

        return ['success' => true, 'message' => 'success_message.exercise_reject'];
    }

    /**
     * @param \App\Models\Exercise $exercise
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Exercise $exercise)
    {
        if (!$exercise->is_used) {
            $exercise->delete();

            return ['success' => true, 'message' => 'success_message.exercise_delete'];
        }

        return ['success' => false, 'message' => 'error_message.exercise_delete'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $type
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request, $type)
    {
        return Excel::download(new ExercisesExport($request), "exercises.$type");
    }

    /**
     * @param Exercise $exercise
     * @param array $requestFiles
     *
     * @return void
     */
    private function attachFiles($exercise, $requestFiles)
    {
        foreach ($requestFiles as $index => $uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::EXERCISE_PATH, File::EXERCISE_THUMBNAIL_PATH);
            if ($file) {
                $exercise->files()->attach($file->id, ['order' => (int)$index]);
            }
        }
    }

    /**
     * @param Exercise $exercise
     * @param string $requestCategories
     *
     * @return void
     */
    private function attachCategories($exercise, $requestCategories)
    {
        $categories = $requestCategories ? explode(',', $requestCategories) : [];
        foreach ($categories as $category) {
            $exercise->categories()->attach($category);
        }
    }
}
