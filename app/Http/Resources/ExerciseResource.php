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
            'uploaded_by' => $this->uploaded_by,
            'approved_by' => $this->approved_by,
            'status' => $this->status,
            'files' => FileResource::collection($this->files()->orderBy('order')->get()),
            'categories' => $this->categories ? $this->categories->pluck('id') : [],
            'additional_fields' => AdditionalFieldResource::collection($this->additionalFields)
        ];
    }
}
