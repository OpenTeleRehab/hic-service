<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryTreeResource;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/category",
     *     tags={"Category"},
     *     summary="Lists all categories by type",
     *     operationId="categoryList",
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Category type",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
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
        $categories = Category::where('type', $request->get('type'))->get();

        return [
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ];
    }

    /**
     * @param Category $category
     *
     * @return CategoryResource
     */
    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    /**
     * @OA\Post(
     *     path="/api/category",
     *     tags={"Category"},
     *     summary="Create category",
     *     operationId="createCategory",
     *     @OA\Parameter(
     *         name="current_category",
     *         in="query",
     *         description="Parent category id",
     *         required=false,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Category name",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Category type",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="category_value",
     *         in="query",
     *         description="Sub category name",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
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
        if ($request->get('current_category')) {
            $parentCategory = Category::find($request->get('current_category'));
        } else {
            $parentCategory = Category::create([
                'title' => $request->get('category'),
                'type' => $request->get('type'),
            ]);
        }

        $subCategoryTitles = explode(';', $request->get('category_value', ''));
        foreach ($subCategoryTitles as $subCategoryTitle) {
            if (trim($subCategoryTitle)) {
                Category::create([
                    'title' => $subCategoryTitle,
                    'type' => $request->get('type'),
                    'parent_id' => $parentCategory->id,
                ]);
            }
        }

        return ['success' => true, 'message' => 'success_message.category_add'];
    }

    /**
     * @OA\Put(
     *     path="/api/category/{id}",
     *     tags={"Category"},
     *     summary="Update category",
     *     operationId="updateCategory",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Category name",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="lang",
     *         in="query",
     *         description="Language id",
     *         required=false,
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
     * @param Category $category
     *
     * @return array
     */
    public function update(Request $request, Category $category)
    {
        $category->update([
            'title' => $request->get('category'),
        ]);

        return ['success' => true, 'message' => 'success_message.category_update'];
    }

    /**
     * @OA\Get(
     *     path="/api/category-tree",
     *     tags={"Category"},
     *     summary="List category tree data by type",
     *     operationId="categoryTreeDataList",
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Category type",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
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
    public function getCategoryTreeData(Request $request)
    {
        $categories = Category::where('type', $request->get('type'))
            ->where('parent_id', null)
            ->get();

        return [
            'success' => true,
            'data' => CategoryTreeResource::collection($categories),
        ];
    }

    /**
     * @OA\Delete(
     *     path="/api/category/{id}",
     *     tags={"Category"},
     *     summary="Delete category",
     *     operationId="deleteCategory",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category id",
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
     * @param Category $category
     *
     * @return array
     * @throws Exception
     */
    public function destroy(Category $category)
    {
        if (!$category->isUsed()) {
            $category->delete();

            return ['success' => true, 'message' => 'success_message.category_delete'];
        }

        return ['success' => false, 'message' => 'error_message.category_delete'];
    }
}
