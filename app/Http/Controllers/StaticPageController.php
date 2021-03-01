<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\StaticPageResource;
use App\Models\StaticPage;
use Illuminate\Http\Request;
use App\Models\File;

class StaticPageController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $staticPages = StaticPage::all();

        return ['success' => true, 'data' => StaticPageResource::collection($staticPages)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $uploadedFile = $request->file('file');
        $file = null;
        if ($uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::STATIC_PAGE_PATH);
        }

        $existingUrl = StaticPage::where('url_path_segment', $request->get('url'))->count();
        if ($existingUrl) {
            // Todo: message will be replaced.
            return abort(409, 'error_message.url_exists');
        }

        StaticPage::create([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'private' => $request->get('private'),
            'platform' => $request->get('platform'),
            'url_path_segment' => $request->get('url'),
            'file_id' => $file !== null ? $file->id : $file
        ]);

        return ['success' => true, 'message' => 'success_message.static_page_add'];
    }
}
