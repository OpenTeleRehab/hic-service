<?php

namespace App\Http\Controllers;

use App\Helpers\FavoriteActivityHelper;
use App\Helpers\FileHelper;
use App\Http\Resources\EducationMaterialResource;
use App\Models\EducationMaterial;
use App\Models\EducationMaterialCategory;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class EducationMaterialController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $therapistId = $request->get('therapist_id');
        $query = EducationMaterial::where(function ($query) use ($therapistId) {
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

        $educationMaterials = $query->paginate($request->get('page_size'));

        $info = [
            'current_page' => $educationMaterials->currentPage(),
            'total_count' => $educationMaterials->total(),
        ];
        return [
            'success' => true,
            'data' => EducationMaterialResource::collection($educationMaterials),
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
            return ['success' => false, 'message' => 'error_message.education_material_create'];
        }

        $uploadedFile = $request->file('file');
        if ($uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::EDUCATION_MATERIAL_PATH);
        }

        $copyId = $request->get('copy_id');
        if ($copyId) {
            // Clone education material.
            $educationMaterial = EducationMaterial::findOrFail($copyId)->replicate(['is_used']);

            // Append (copy) label to all title translations.
            $titleTranslations = $educationMaterial->getTranslations('title');
            $appendedTitles = array_map(function ($value) {
                // TODO: translate copy label to each language.
                return "$value (Copy)";
            }, $titleTranslations);
            $educationMaterial->setTranslations('title', $appendedTitles);
            $educationMaterial->save();

            // CLone files.
            if (empty($file)) {
                $originalFile = File::findOrFail($educationMaterial->file_id);
                $file = FileHelper::replicateFile($originalFile);
            }

            // Update form elements.
            $educationMaterial->update([
                'title' => $request->get('title'),
                'file_id' => $file->id,
                'therapist_id' => $therapistId,
            ]);
        } elseif (!empty($file)) {
            $educationMaterial = EducationMaterial::create([
                'title' => $request->get('title'),
                'file_id' => $file->id,
                'therapist_id' => $therapistId,
            ]);
        }

        if (empty($educationMaterial)) {
            return ['success' => false, 'message' => 'error_message.education_material_create'];
        }

        // Attach category to education material.
        $this->attachCategories($educationMaterial, $request->get('categories'));

        return ['success' => true, 'message' => 'success_message.education_material_create'];
    }

    /**
     * @param \App\Models\EducationMaterial $educationMaterial
     *
     * @return \App\Http\Resources\EducationMaterialResource
     */
    public function show(EducationMaterial $educationMaterial)
    {
        return new EducationMaterialResource($educationMaterial);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\EducationMaterial $educationMaterial
     *
     * @return array
     */
    public function update(Request $request, EducationMaterial $educationMaterial)
    {
        $therapistId = $request->get('therapist_id');
        if (!Auth::user() && !$therapistId) {
            return ['success' => false, 'message' => 'error_message.education_material_update'];
        }

        if ((int) $educationMaterial->therapist_id !== (int) $therapistId) {
            return ['success' => false, 'message' => 'error_message.education_material_update'];
        }

        $uploadedFile = $request->file('file');
        if ($uploadedFile) {
            $oldFile = File::find($educationMaterial->file_id);
            $oldFile->delete();

            $newFile = FileHelper::createFile($uploadedFile, File::EDUCATION_MATERIAL_PATH);
            $educationMaterial->update([
                'title' => $request->get('title'),
                'file_id' => $newFile->id,
            ]);
        } else {
            $educationMaterial->update([
                'title' => $request->get('title'),
            ]);
        }

        // Attach category to education material.
        EducationMaterialCategory::where('education_material_id', $educationMaterial->id)->delete();
        $this->attachCategories($educationMaterial, $request->get('categories'));

        return ['success' => true, 'message' => 'success_message.education_material_update'];
    }

    /**
     * @param \App\Models\EducationMaterial $educationMaterial
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(EducationMaterial $educationMaterial)
    {
        if (!$educationMaterial->is_used) {
            $educationMaterial->delete();
            return ['success' => true, 'message' => 'success_message.education_material_delete'];
        }
        return ['success' => false, 'message' => 'error_message.education_material_delete'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getByIds(Request $request)
    {
        $materialIds = $request->get('material_ids', []);
        $materials = EducationMaterial::whereIn('id', $materialIds)->get();
        return EducationMaterialResource::collection($materials);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function markAsUsed(Request $request)
    {
        $materialIds = $request->get('material_ids', []);
        EducationMaterial::where('is_used', false)
            ->whereIn('id', $materialIds)
            ->update(['is_used' => true]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\EducationMaterial $educationMaterial
     *
     * @return array
     */
    public function updateFavorite(Request $request, EducationMaterial $educationMaterial)
    {
        $favorite = $request->get('is_favorite');
        $therapistId = $request->get('therapist_id');

        FavoriteActivityHelper::flagFavoriteActivity($favorite, $therapistId, $educationMaterial);
        return ['success' => true, 'message' => 'success_message.education_material_update'];
    }

    /**
     * @param EducationMaterial $educationMaterial
     * @param string $requestCategories
     *
     * @return void
     */
    private function attachCategories($educationMaterial, $requestCategories)
    {
        $categories = $requestCategories ? explode(',', $requestCategories) : [];
        foreach ($categories as $category) {
            $educationMaterial->categories()->attach($category);
        }
    }
}
