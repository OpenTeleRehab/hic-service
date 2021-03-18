<?php

namespace App\Http\Controllers;

use App\Helpers\FavoriteActivityHelper;
use App\Helpers\FileHelper;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\FavoriteActivitiesTherapist;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $therapistId = $request->get('therapist_id');
        $query = Exercise::where(function ($query) use ($therapistId) {
            $query->whereNull('therapist_id');
            if ($therapistId) {
                $query->orWhere('therapist_id', $therapistId);
            }
        });

        $filter = json_decode($request->get('filter'), true);

        if (!empty($filter['search_value'])) {
            $locale = App::getLocale();
            $query->whereRaw("JSON_EXTRACT(LOWER(title), \"$.$locale\") LIKE ?", ['%' . strtolower($filter['search_value']) . '%']);
        }

        if ($request->get('categories')) {
            $categories = $request->get('categories');
            foreach ($categories as $category) {
                $query->whereHas('categories', function ($query) use ($category) {
                    $query->where('id', $category);
                });
            }
        }

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
    public function store(Request $request)
    {
        $therapistId = $request->get('therapist_id');
        if (!Auth::user() && !$therapistId) {
            return ['success' => false, 'message' => 'error_message.exercise_create'];
        }

        $exercise = Exercise::create([
            'title' => $request->get('title'),
            'include_feedback' => $request->boolean('include_feedback'),
            'get_pain_level' => $request->boolean('get_pain_level'),
            'additional_fields' => $request->get('additional_fields'),
            'therapist_id' => $therapistId
        ]);

        // Upload files and attach to Exercise.
        $i = 0;
        $allFiles = $request->allFiles();
        foreach ($allFiles as $uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::EXERCISE_PATH, File::EXERCISE_THUMBNAIL_PATH);
            if ($file) {
                $exercise->files()->attach($file->id, ['order' => ++$i]);
            }
        }

        // Attach category to exercise.
        $categories = $request->get('categories') ? explode(',', $request->get('categories')) : [];
        foreach ($categories as $category) {
            $exercise->categories()->attach($category);
        }

        if ($exercise) {
            return ['success' => true, 'message' => 'success_message.exercise_create'];
        }
        return ['success' => false, 'message' => 'error_message.exercise_create'];
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
        $therapistId = $request->get('therapist_id');
        if (!Auth::user() && !$therapistId) {
            return ['success' => false, 'message' => 'error_message.exercise_update'];
        }

        if ((int) $exercise->therapist_id !== (int) $therapistId) {
            return ['success' => false, 'message' => 'error_message.exercise_update'];
        }

        $exercise->update([
            'title' => $request->get('title'),
            'include_feedback' => $request->boolean('include_feedback'),
            'get_pain_level' => $request->boolean('get_pain_level'),
            'additional_fields' => $request->get('additional_fields'),
        ]);

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
        $allFiles = $request->allFiles();
        foreach ($allFiles as $index => $uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::EXERCISE_PATH, File::EXERCISE_THUMBNAIL_PATH);
            if ($file) {
                $exercise->files()->attach($file->id, ['order' => (int) $index]);
            }
        }

        // Attach category to exercise.
        $categories = $request->get('categories') ? explode(',', $request->get('categories')) : [];
        ExerciseCategory::where('exercise_id', $exercise->id)->delete();
        foreach ($categories as $category) {
            $exercise->categories()->attach($category);
        }

        return ['success' => true, 'message' => 'success_message.exercise_update'];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function countTherapistLibrary(Request $request)
    {
        $therapistId = $request->get('therapist_id');
        $exerciseCount = Exercise::where('therapist_id', $therapistId)->count();

        // TODO: to count education material and questionnaire of therapist then sum these three items.
        return [
            'success' => true,
            'data' => $exerciseCount,
        ];
    }

    /**
     * @param \App\Models\Exercise $exercise
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Exercise $exercise)
    {
        if ($exercise->canDelete()) {
            // Todo: delete media resources.
            $exercise->delete();
            return ['success' => true, 'message' => 'success_message.exercise_delete'];
        }
        return ['success' => false, 'message' => 'error_message.exercise_delete'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getByIds(Request $request)
    {
        $exerciseIds = $request->get('exercise_ids', []);
        $exercises = Exercise::whereIn('id', $exerciseIds)->get();
        return ExerciseResource::collection($exercises);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function markAsUsed(Request $request)
    {
        $exerciseIds = $request->get('exercise_ids', []);
        Exercise::where('is_used', false)
            ->whereIn('id', $exerciseIds)
            ->update(['is_used' => true]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Exercise $exercise
     *
     * @return array
     */
    public function updateFavorite(Request $request, Exercise $exercise)
    {
        $favorite = $request->get('is_favorite');
        $therapistId = $request->get('therapist_id');

        FavoriteActivityHelper::flagFavoriteActivity($favorite, $therapistId, $exercise);
        return ['success' => true, 'message' => 'success_message.exercise_update'];
    }
}
