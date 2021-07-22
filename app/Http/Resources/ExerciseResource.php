<?php

namespace App\Http\Resources;

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
            'include_feedback' => $this->include_feedback,
            'get_pain_level' => $this->get_pain_level,
            'files' => FileResource::collection($this->files()->orderBy('order')->get()),
            'is_used' => $this->is_used,
            'categories' => $this->categories ? $this->categories->pluck('id') : [],
            'therapist_id' => $this->therapist_id,
            'additional_fields' => AdditionalFieldResource::collection($this->additionalFields)
        ];
    }
}
