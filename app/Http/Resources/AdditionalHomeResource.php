<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdditionalHomeResource extends JsonResource
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
            'display_quick_stat' =>  $this->display_quick_stat,
            'display_feature_resource' =>  $this->display_feature_resource,
        ];
    }
}
