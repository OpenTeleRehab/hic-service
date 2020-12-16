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
            'include_feedback' => $this->include_feedback,
            'can_delete' => $this->canDelete(),
            'files' => FileResource::collection($this->files()->orderBy('order')->get()),
        ];
    }
}
