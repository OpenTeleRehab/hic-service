<?php

namespace App\Http\Resources;

use App\Models\EducationMaterial;
use Illuminate\Http\Resources\Json\JsonResource;

class EducationMaterialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_id' => $this->file_id_no_fallback,
            'file' => new FileResource($this->file),
            'categories' => $this->categories ? $this->categories->pluck('id') : [],
            'uploaded_date' => $this->created_at->format(config('settings.date_format')),
            'uploaded_by' => $this->getContributorName(),
            'uploaded_by_email' => $this->getContributorEmail(),
            'reviewed_by' => $this->getReviewerName(),
            'editing_by' => $this->getEditorName(),
            'blocked_editing' => $this->blockedEditing(),
            'status' => $this->status,
            'auto_translated' => $this->auto_translated,
            'edit_translations' => EducationMaterial::where('edit_translation', $this->id)->get(),
            'fallback' => [
                'title' => $this->getTranslation('title', config('app.fallback_locale'))
            ],
        ];
    }
}
