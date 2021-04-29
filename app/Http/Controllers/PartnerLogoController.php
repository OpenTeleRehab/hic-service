<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\PartnerLogo;
use Illuminate\Http\Request;
use App\Models\File;
use App\Http\Resources\FileResource;

class PartnerLogoController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $partnerLogo = PartnerLogo::first();
        return ['success' => true, 'data' => $partnerLogo ? new FileResource($partnerLogo->file) : [] ];
    }

    /**
     * @param \App\Models\PartnerLogo $partnerLogo
     *
     * @return \App\Http\Resources\FileResource
     */
    public function show(PartnerLogo $partnerLogo)
    {
        return new FileResource($partnerLogo);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $uploadedFile = $request->file('file');
        $newFile = FileHelper::createFile($uploadedFile, File::STATIC_PAGE_PATH);
        $partnerLogo = PartnerLogo::first();
        if ($partnerLogo) {
            $partnerLogo->file->delete();
            $partnerLogo->update([
                'file_id' => $newFile->id,
            ]);
        } else {
            PartnerLogo::create([
                'file_id' => $newFile->id,
            ]);
        }
        return ['success' => true, 'message' => 'success_message.partner_logo.save'];
    }

    /**
     * @return array
     */
    public function getPartnerLogo()
    {
        $partnerLogo = PartnerLogo::first();
        return ['success' => true, 'data' => $partnerLogo ? new FileResource($partnerLogo->file) : [] ];
    }
}
