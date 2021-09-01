<?php

namespace App\Http\Resources;

use App\Helpers\ExerciseHelper;
use App\Models\Contributor;
use App\Models\Exercise;
use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedResourceResource extends JsonResource
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
            'id' => $this->getTable() . '-' . $this->id,
            'key' => $this->title,
            'type' => config("settings.featured_type." . $this->getTable()) ,
        ];
    }
}
