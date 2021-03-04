<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Category $category
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
}
