<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
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
            'fileName' =>  $this->filename,
            'fileType' =>  $this->content_type,
            'fileExtension' => strtoupper($this->getFileExtension($this->path))
        ];
    }

    /**
     * @param string $path
     * @return mixed
     */
    private function getFileExtension($path)
    {
        $path_parts = pathinfo($path);

        return $path_parts['extension'];
    }
}
