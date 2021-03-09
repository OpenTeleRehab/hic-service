<?php

namespace App\Http\Controllers;

use App\Helpers\CategoryHelper;
use App\Helpers\FileHelper;
use App\Http\Resources\EducationMaterialResource;
use App\Models\Category;
use App\Models\EducationMaterial;
use App\Models\EducationMaterialCategory;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class EducationMaterialController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $query = EducationMaterial::select();
        $filter = json_decode($request->get('filter'), true);

        if (!empty($filter['search_value'])) {
            $locale = App::getLocale();
            $query->whereRaw("JSON_EXTRACT(LOWER(title), \"$.$locale\") LIKE ?", ['%' . strtolower($filter['search_value']) . '%']);
        }

        if ($request->get('categories')) {
            $categories = $request->get('categories', []);

            // Unset parents if there is any children.
            foreach ($request->get('categories', []) as $category) {
                $cat = Category::find($category);
                CategoryHelper::unsetParents($categories, $cat);
            }

            $catChildren = [];
            // Set children if there is any.
            foreach ($categories as $category) {
                $cat = Category::find($category);
                CategoryHelper::addChildren($catChildren, $cat);
            }

            $categories = array_merge($categories, $catChildren);

            $query->whereHas('categories', function ($query) use ($categories) {
                $query->whereIn('id', $categories);
            });
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
        $uploadedFile = $request->file('file');
        if ($uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::EDUCATION_MATERIAL_PATH);
            $educationMaterial = EducationMaterial::create([
                'title' => $request->get('title'),
                'file_id' => $file->id,
            ]);

            // Attach category to education material.
            $categories = $request->get('categories') ? explode(',', $request->get('categories')) : [];
            foreach ($categories as $category) {
                $educationMaterial->categories()->attach($category);
            }

            return ['success' => true, 'message' => 'success_message.education_material_create'];
        }

        return ['success' => false, 'message' => 'error_message.education_material_create'];
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
        $categories = $request->get('categories') ? explode(',', $request->get('categories')) : [];
        EducationMaterialCategory::where('education_material_id', $educationMaterial->id)->delete();
        foreach ($categories as $category) {
            $educationMaterial->categories()->attach($category);
        }


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
}
