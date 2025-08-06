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
            'fallback' => [
                'title' => $this->getTranslation('title', config('app.fallback_locale'))
            ],
            'slug' => $this->slug
        ];

        return $data;
    }
}
