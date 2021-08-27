<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\StaticPageResource;
use App\Models\AdditionalHome;
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
     * @param \App\Models\StaticPage $staticPage
     *
     * @return \App\Http\Resources\StaticPageResource
     */
    public function show(StaticPage $staticPage)
    {
        return new StaticPageResource($staticPage);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $uploadedFile = $request->file('file');
        $pageType = $request->get('url');
        $file = null;
        if ($uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::STATIC_PAGE_PATH);
        }

        $existingUrl = StaticPage::where('url_path_segment', $request->get('url'))->count();
        if ($existingUrl) {
            // Todo: message will be replaced.
            return abort(409, 'error_message.url_exists');
        }

        $staticPage = StaticPage::create([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'url_path_segment' => $request->get('url'),
            'file_id' => $file !== null ? $file->id : $file,
            'partner_content' => $request->get('partnerContent')
        ]);


        if ($pageType === StaticPage::PAGE_TYPE_HOMEPAGE) {
            $additionalHome = AdditionalHome::create([
                'display_quick_stat' => $request->boolean('display_quick_stat'),
                'display_feature_resource' => $request->boolean('display_feature_resource'),
            ]);

            $staticPage->update(['additional_home_id' => $additionalHome->id] );
        }

        $staticPage->save();

        return ['success' => true, 'message' => 'success_message.static_page_add'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\StaticPage $staticPage
     *
     * @return array
     */
    public function update(Request $request, StaticPage $staticPage)
    {
        $uploadedFile = $request->file('file');
        $pageType = $request->get('url');

        if ($uploadedFile) {
            $oldFile = File::find($staticPage->file_id);
            if ($oldFile) {
                $oldFile->delete();
            }

            $newFile = FileHelper::createFile($uploadedFile, File::STATIC_PAGE_PATH);
            $staticPage->update([
                'file_id' => $newFile->id,
            ]);
        }

        if ($request->get('file') === 'undefined') {
            $oldFile = File::find($staticPage->file_id);
            if ($oldFile) {
                $oldFile->delete();
            }
        }

        $existingStaticPage = StaticPage::where('url_path_segment', $request->get('url'))->first();

        if ($existingStaticPage && $existingStaticPage->id !== $staticPage->id) {
            // Todo: message will be replaced.
            return abort(409, 'error_message.url_exists');
        }

        $staticPage->update([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'url_path_segment' => $request->get('url'),
            'partner_content' => $request->get('partnerContent')
        ]);

        if ($pageType === StaticPage::PAGE_TYPE_HOMEPAGE) {
            $additionalHome = AdditionalHome::where('id', $staticPage->additional_home_id)->first();
            $additionalHome->update([
                'display_quick_stat' => $request->boolean('display_quick_stat'),
                'display_feature_resource' => $request->boolean('display_feature_resource'),
            ]);
        }

        $staticPage->save();

        return ['success' => true, 'message' => 'success_message.static_file.update'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\View\View
     */
    public function getStaticPage(Request $request)
    {
        $platform = $request->get('platform');
        $page = StaticPage::where('url_path_segment', $request->get('url-segment'))
            ->where('platform', $platform)
            ->firstOrFail();

        return view('templates.default', compact('page', 'platform'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getStaticPageHome(Request $request)
    {
        $page = StaticPage::where('url_path_segment', $request->get('url-segment'))
            ->first();
        return ['success' => true, 'data' => $page ? new StaticPageResource($page) : []];
    }
}
