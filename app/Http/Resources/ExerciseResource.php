<?php

namespace App\Http\Resources;

use App\Helpers\ExerciseHelper;
use App\Models\Contributor;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
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
            'sets' => $this->sets,
            'reps' => $this->reps,
            'uploaded_date' => $this->created_at->format(config('settings.date_format')),
            'uploaded_by' => $this->getContributorName(),
            'uploaded_by_email' => $this->getContributorEmail(),
            'reviewed_by' => $this->getReviewerName(),
            'status' => $this->status,
            'files' => FileResource::collection($this->files()->orderBy('order')->get()),
            'categories' => $this->categories ? $this->categories->pluck('id') : [],
            'additional_fields' => AdditionalFieldResource::collection($this->additionalFields),
            'auto_translated' => $this->auto_translated,
        ];
    }
}
