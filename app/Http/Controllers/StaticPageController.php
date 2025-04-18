<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\StaticPageResource;
use App\Models\AdditionalHome;
use App\Models\Contributor;
use App\Models\File;
use App\Models\StaticPage;
use Illuminate\Http\Request;

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
     * @param StaticPage $staticPage
     *
     * @return StaticPageResource
     */
    public function show(StaticPage $staticPage)
    {
        return new StaticPageResource($staticPage);
    }

    /**
     * @param Request $request
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

        $featuredResources = json_decode($request->get('featureResources'), true);
        $featuredResourcesToUpdate = [];
        if ($featuredResources) {
            foreach ($featuredResources as $featuredResource) {
                $type = str_replace(' ', '_', strtolower($featuredResource['type']));
                $featuredResourcesToUpdate[$type][] = $featuredResource;
            }
        }

        if ($pageType === StaticPage::PAGE_TYPE_HOMEPAGE) {
            $additionalHome = AdditionalHome::create([
                'display_quick_stat' => $request->boolean('display_quick_stat'),
                'display_feature_resource' => $request->boolean('display_feature_resource'),
                'resources' => json_encode($featuredResourcesToUpdate)
            ]);

            $staticPage->update(['additional_home_id' => $additionalHome->id]);
        }

        if ($pageType === StaticPage::PAGE_TYPE_ACKNOWLEDGMENT) {
            Contributor::where('included_in_acknowledgment', false)
                ->update([
                    'included_in_acknowledgment' => true
                ]);

            Contributor::whereIn('id', json_decode($request->get('hideContributors')))
                ->update([
                    'included_in_acknowledgment' => false
                ]);
        }

        $staticPage->save();

        switch ($pageType) {
            case StaticPage::PAGE_TYPE_ABOUT_US:
                $message = 'success_message.about_us_add';
                break;
            case StaticPage::PAGE_TYPE_ACKNOWLEDGMENT:
                $message = 'success_message.acknowledgment_add';
                break;
            default:
                $message = 'success_message.home_page_add';
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * @param Request $request
     * @param StaticPage $staticPage
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

        $featuredResources = json_decode($request->get('featureResources'), true);
        $featuredResourcesToUpdate = [];
        if ($featuredResources) {
            foreach ($featuredResources as $featuredResource) {
                $type = str_replace(' ', '_', strtolower($featuredResource['type']));
                $featuredResourcesToUpdate[$type][] = $featuredResource;
            }
        }

        $staticPage->update([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'url_path_segment' => $request->get('url'),
            'partner_content' => $request->get('partnerContent')
        ]);

        if ($pageType === StaticPage::PAGE_TYPE_HOMEPAGE) {
            $additionalHome = AdditionalHome::updateOrCreate(
                ['id' => $staticPage->additional_home_id],
                [
                    'display_quick_stat' => $request->boolean('display_quick_stat'),
                    'display_feature_resource' => $request->boolean('display_feature_resource'),
                    'resources' => json_encode($featuredResourcesToUpdate)
                ]
            );

            $staticPage->update(['additional_home_id' => $additionalHome->id]);
        }

        if ($pageType === StaticPage::PAGE_TYPE_ACKNOWLEDGMENT) {
            Contributor::where('included_in_acknowledgment', false)
                ->update([
                    'included_in_acknowledgment' => true
                ]);

            Contributor::whereIn('id', json_decode($request->get('hideContributors')))
                ->update([
                    'included_in_acknowledgment' => false
                ]);
        }

        $staticPage->save();

        switch ($pageType) {
            case StaticPage::PAGE_TYPE_ABOUT_US:
                $message = 'success_message.about_us_update';
                break;
            case StaticPage::PAGE_TYPE_ACKNOWLEDGMENT:
                $message = 'success_message.acknowledgment_update';
                break;
            default:
                $message = 'success_message.home_page_update';
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getStaticPage(Request $request)
    {
        $page = StaticPage::where('url_path_segment', $request->get('url-segment'))
            ->first();
        return ['success' => true, 'data' => $page ? new StaticPageResource($page) : []];
    }
}
