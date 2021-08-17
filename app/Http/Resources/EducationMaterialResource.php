<?php

namespace App\Http\Resources;

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
            'file' => $this->file_id_no_fallback ? new FileResource($this->file) : null,
            'categories' => $this->categories ? $this->categories->pluck('id') : [],
            'uploaded_date' => $this->created_at->format(config('settings.date_format')),
            'uploaded_by' => $this->getContributorName(),
            'uploaded_by_email' => $this->getContributorEmail(),
            'reviewed_by' => $this->getReviewerName(),
            'status' => $this->status,
            'auto_translated' => $this->auto_translated,
        ];
    }
}
