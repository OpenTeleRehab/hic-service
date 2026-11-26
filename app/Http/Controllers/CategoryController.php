<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryTreeResource;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Spec as OA;

class CategoryController extends Controller
{
    /**
     * @param Request $request
     *
     * @return array
     */
    #[OA\Operation\Get(
        path: '/api/category',
        tags: ['Category'],
        summary: 'Lists all categories by type',
        operationId: 'categoryList',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'type',
                in: 'query',
                description: 'Category type',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function index(Request $request)
    {
        $categories = Category::where('type', $request->input('type'))->get();

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
     * @param Request $request
     *
     * @return array
     */
    #[OA\Operation\Post(
        path: '/api/category',
        tags: ['Category'],
        summary: 'Create category',
        operationId: 'createCategory',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'current_category',
                in: 'query',
                description: 'Parent category id',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'Category name',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                description: 'Category type',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'category_value',
                in: 'query',
                description: 'Sub category name',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function store(Request $request)
    {
        if ($request->input('current_category')) {
            $parentCategory = Category::find($request->input('current_category'));
        } else {
            $parentCategory = Category::create([
                'title' => $request->input('category'),
                'type' => $request->input('type'),
            ]);
        }

        $subCategoryTitles = explode(';', $request->input('category_value', ''));
        foreach ($subCategoryTitles as $subCategoryTitle) {
            if (trim($subCategoryTitle)) {
                Category::create([
                    'title' => $subCategoryTitle,
                    'type' => $request->input('type'),
                    'parent_id' => $parentCategory->id,
                ]);
            }
        }

        return ['success' => true, 'message' => 'success_message.category_add'];
    }

    /**
     * @param Request $request
     * @param Category $category
     *
     * @return array
     */
    #[OA\Operation\Put(
        path: '/api/category/{id}',
        tags: ['Category'],
        summary: 'Update category',
        operationId: 'updateCategory',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Category id',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'Category name',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                description: 'Language id',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function update(Request $request, Category $category)
    {
        $category->update([
            'title' => $request->input('category'),
        ]);

        return ['success' => true, 'message' => 'success_message.category_update'];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    #[OA\Operation\Get(
        path: '/api/category-tree',
        tags: ['Category'],
        summary: 'List category tree data by type',
        operationId: 'categoryTreeDataList',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'type',
                in: 'query',
                description: 'Category type',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function getCategoryTreeData(Request $request)
    {
        $categories = Category::where('type', $request->input('type'))
            ->where('parent_id', null)
            ->get();

        return [
            'success' => true,
            'data' => CategoryTreeResource::collection($categories),
        ];
    }

    /**
     * @param Category $category
     *
     * @return array
     * @throws Exception
     */
    #[OA\Operation\Delete(
        path: '/api/category/{id}',
        tags: ['Category'],
        summary: 'Delete category',
        operationId: 'deleteCategory',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Category id',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function destroy(Category $category)
    {
        if (!$category->isUsed()) {
            $category->delete();

            return ['success' => true, 'message' => 'success_message.category_delete'];
        }

        return ['success' => false, 'message' => 'error_message.category_delete'];
    }
}
