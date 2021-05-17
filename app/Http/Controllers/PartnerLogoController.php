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
     * @OA\Get(
     *     path="/api/partner-logo",
     *     tags={"Partner Logo"},
     *     summary="Lists partner logo",
     *     operationId="partnerLogoList",
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
     * @OA\Post(
     *     path="/api/partner-logo",
     *     tags={"Partner Logo"},
     *     summary="Create partner logo",
     *     operationId="createPartnerLogo",
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
