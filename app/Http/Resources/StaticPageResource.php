<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaticPageResource extends JsonResource
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
            'content' => $this->content,
            'partner_content' => $this->partner_content,
            'url' => $this->url_path_segment,
            'file_id' => $this->file_id,
            'file' => new FileResource($this->file),
            'homeData' => new AdditionalHomeResource($this->additionalHome),
            'acknowledgmentData' => new AdditionalAcknowledgmentResource($this->additionalAcknowledgment),
        ];
    }
}
