<?php

namespace App\Http\Resources;

use App\Models\EducationMaterial;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EducationMaterialListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'file' => new FileResource($this->file),
            'uploaded_date' => $this->created_at->format(config('settings.date_format')),
            'fallback' => [
                'title' => $this->getTranslation('title', config('app.fallback_locale'))
            ],
            'slug' => $this->slug,
            'status' => $this->status
        ];

        if (Auth::check()) {
            $data['uploaded_by_email'] = $this->getContributorEmail();
            $data['uploaded_by'] = $this->getContributorName();
            $data['reviewed_by'] = $this->getReviewerName();
            $data['editing_by'] = $this->getEditorName();
        }

        return $data;
    }
}
