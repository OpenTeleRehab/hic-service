<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContributorResource extends JsonResource
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
            'name' => $this->getFullName(),
            'email' => $this->email,
            'isModerator' => $this->isModerator(),
            'included_in_acknowledgment' => $this->included_in_acknowledgment === 1
        ];
    }
}
