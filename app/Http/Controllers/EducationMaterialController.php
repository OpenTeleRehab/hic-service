<?php

namespace App\Http\Controllers;

use App\Events\ApplyMaterialAutoTranslationEvent;
use App\Helpers\ExerciseHelper;
use App\Helpers\FileHelper;
use App\Http\Resources\EducationMaterialResource;
use App\Models\Contributor;
use App\Models\EducationMaterial;
use App\Models\EducationMaterialCategory;
use App\Models\File;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class EducationMaterialController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/education-material",
     *     tags={"Education Material"},
     *     summary="Lists education materials",
     *     operationId="educationMaterialList",
     *     @OA\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Limit",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="successful operation"
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=401, description="Authentication is required"),
     *     security={
     *         {
     *             "oauth2_security": {}
     *         }
     *     },
     * )
     *
     * @param Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $query = ExerciseHelper::generateFilterQuery($request, with(new EducationMaterial));
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
     * @OA\Post(
     *     path="/api/education-material",
     *     tags={"Education Material"},
     *     summary="Create education materials",
     *     operationId="createEducationMaterial",
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Title",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="categories",
     *         in="query",
     *         description="Category id",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     description="file to upload",
     *                     property="file",
     *                     type="file",
     *                ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="successful operation"
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=401, description="Authentication is required"),
     *     security={
     *         {
     *             "oauth2_security": {}
     *         }
     *     },
     * )
     *
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $email = $request->get('email');
        $first_name = $request->get('first_name');
        $last_name = $request->get('last_name');
        $edit_translation = !Auth::check() ? json_decode($request->get('edit_translation')) : false;
        $hash = !Auth::check() ? $request->get('hash') : null;
        $included_in_acknowledgment = $request->boolean('included_in_acknowledgment');
        $status = !Auth::check() ? EducationMaterial::STATUS_DRAFT : EducationMaterial::STATUS_PENDING;

        $contributor = $this->updateOrCreateContributor($first_name, $last_name, $email, $included_in_acknowledgment);

        if ($request->hasFile('file')) {
            if ($request->file('file')->isValid()) {
                $file = FileHelper::createFile($request->file('file'), File::EDUCATION_MATERIAL_PATH, File::EDUCATION_MATERIAL_THUMBNAIL_PATH);
            }
        }

        $educationMaterial = EducationMaterial::create([
            'title' => $request->get('title'),
            'file_id' => !empty($file) ? intval($file->id) : intval($request->get('file_id')),
            'hash' => $hash,
            'status' => $status,
            'uploaded_by' => $contributor ? $contributor->id : null,
            'edit_translation' => $edit_translation ? $request->get('id') : null
        ]);

        if (empty($educationMaterial)) {
            return ['success' => false, 'message' => 'error_message.education_material_create'];
        }

        // Attach category to education material.
        $this->attachCategories($educationMaterial, $request->get('categories'));

        return ['success' => true, 'message' => 'success_message.education_material_create'];
    }

    /**
     * @param EducationMaterial $educationMaterial
     *
     * @return EducationMaterialResource
     */
    public function show(EducationMaterial $educationMaterial)
    {
        if (Auth::check()) {
            $currentDataTime = Carbon::now();
            if (!$educationMaterial->editing_by || $currentDataTime->gt($educationMaterial->editing_at->addMinutes(3))) {
                $educationMaterial->update(['editing_by' => Auth::id(), 'editing_at' => $currentDataTime]);
            }
        }
        return new EducationMaterialResource($educationMaterial);
    }

    /**
     * @OA\Put(
     *     path="/api/education-material/{id}",
     *     tags={"Education Material"},
     *     summary="Update education materials",
     *     operationId="updateEducationMaterial",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Material id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Title",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="lang",
     *         in="path",
     *         description="Language id",
     *         required=false,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="categories",
     *         in="query",
     *         description="Category id",
     *         required=false,
     *         @OA\Schema(
     *          type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     description="file to upload",
     *                     property="file",
     *                     type="file",
     *                ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="successful operation"
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=401, description="Authentication is required"),
     *     security={
     *         {
     *             "oauth2_security": {}
     *         }
     *     },
     * )
     *
     * @param Request $request
     * @param EducationMaterial $educationMaterial
     *
     * @return array
     */
    public function update(Request $request, EducationMaterial $educationMaterial)
    {
        if ($educationMaterial->blockedEditing()) {
            return ['success' => false, 'message' => 'error_message.education_material_update'];
        }

        $uploadedFile = $request->file('file');
        if ($uploadedFile) {
            $oldFile = File::find($educationMaterial->file_id_no_fallback);
            if ($oldFile) {
                $oldFile->delete();
            }

            $newFile = FileHelper::createFile($uploadedFile, File::EDUCATION_MATERIAL_PATH, File::EDUCATION_MATERIAL_THUMBNAIL_PATH);
            $educationMaterial->update([
                'title' => $request->get('title'),
                'file_id' => $newFile->id,

                'editing_by' => null,
                'editing_at' => null,
            ]);
        } else {
            $educationMaterial->update([
                'title' => $request->get('title'),
                'status' => EducationMaterial::STATUS_APPROVED,
                'reviewed_by' => Auth::id(),
                'editing_by' => null,
                'editing_at' => null,
            ]);
        }

        // Attach category to education material.
        EducationMaterialCategory::where('education_material_id', $educationMaterial->id)->delete();
        $this->attachCategories($educationMaterial, $request->get('categories'));

        // Add automatic translation for Exercise.
        event(new ApplyMaterialAutoTranslationEvent($educationMaterial));

        return ['success' => true, 'message' => 'success_message.education_material_update'];
    }

    /**
     * @param Request $request
     * @param EducationMaterial $educationMaterial
     *
     * @return array
     */
    public function approveEditTranslation(Request $request, EducationMaterial $educationMaterial)
    {
        $educationMaterial->update([
            'title' => $request->get('title'),
            'file_id' => $request->get('file'),
            'auto_translated' => false
        ]);

        // Update submitted translation status.
        EducationMaterial::find($request->get('id'))->update([
            'status' => EducationMaterial::STATUS_APPROVED,
            'title' => $educationMaterial->title
        ]);

        // Remove submitted translation remaining.
        EducationMaterial::whereNotNull('title->' . App::getLocale())
            ->where('edit_translation', $educationMaterial->id)
            ->whereNotIn('id', [$request->get('id')])
            ->delete();

        return ['success' => true, 'message' => 'success_message.education_material_update'];
    }

    /**
     * @param EducationMaterial $educationMaterial
     *
     * @return array
     */
    public function cancelEditing(EducationMaterial $educationMaterial)
    {
        if ($educationMaterial->editing_by === Auth::id()) {
            $educationMaterial->update(['editing_by' => null, 'editing_at' => null]);
            return ['success' => true, 'message' => 'success_message.education_material_cancel_editing'];
        }
        return ['success' => false, 'message' => 'error_message.education_material_cancel_editing'];
    }

    /**
     * @param EducationMaterial $educationMaterial
     *
     * @return array
     */
    public function continueEditing(EducationMaterial $educationMaterial)
    {
        if ($educationMaterial->editing_by === Auth::id()) {
            $educationMaterial->update(['editing_at' => Carbon::now()]);
            return ['success' => true, 'message' => 'success_message.exercise_continue_editing'];
        }
        return ['success' => false, 'message' => 'error_message.exercise_continue_editing'];
    }

    /**
     * @param EducationMaterial $educationMaterial
     *
     * @return array
     */
    public function reject(EducationMaterial $educationMaterial)
    {
        $educationMaterial->update(['status' => EducationMaterial::STATUS_REJECTED, 'reviewed_by' => Auth::id()]);

        return ['success' => true, 'message' => 'success_message.education_material_reject'];
    }

    /**
     * @OA\Delete(
     *     path="/api/education-material/{id}",
     *     tags={"Education Material"},
     *     summary="Delete education materials",
     *     operationId="deleteEducationMaterial",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Material id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="successful operation"
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=401, description="Authentication is required"),
     *     security={
     *         {
     *             "oauth2_security": {}
     *         }
     *     },
     * )
     *
     * @param EducationMaterial $educationMaterial
     *
     * @return array
     * @throws Exception
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
     * @param Request $request
     *
     * @return AnonymousResourceCollection
     */
    public function getByIds(Request $request)
    {
        $materialIds = $request->get('material_ids', []);
        $materials = EducationMaterial::whereIn('id', $materialIds)->get();
        return EducationMaterialResource::collection($materials);
    }

    /**
     * @param Request $request
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

    /**
     * @param string $first_name
     * @param string $last_name
     * @param string $email
     * @param bool $included_in_acknowledgment
     *
     * @return mixed
     */
    public static function updateOrCreateContributor($first_name, $last_name, $email, $included_in_acknowledgment)
    {
        $contributor = Contributor::updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'included_in_acknowledgment' => $included_in_acknowledgment
            ]
        );

        return $contributor;
    }

    /**
     * @param Request $request
     *
     * @return EducationMaterialResource
     */
    public function getBySlug(Request $request)
    {
        $slug = $request->get('slug');
        $material = EducationMaterial::where('slug', $slug)->whereNull('edit_translation')->first();
        return new EducationMaterialResource($material);
    }
}
