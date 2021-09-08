<?php

namespace App\Http\Resources;

use App\Models\Contributor;
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
            'included_in_acknowledgment' => $this->included_in_acknowledgment === 1,
            'background_colors' => $this->randomColors(Contributor::BACKGROUND_COLORS)
        ];
    }

    protected function randomColors($backgroundColors)
    {
        $count = count($backgroundColors) - 1;
        $i = rand(0, $count);

        return $backgroundColors[$i];
    }
}
