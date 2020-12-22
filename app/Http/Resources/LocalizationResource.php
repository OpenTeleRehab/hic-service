<?php

namespace App\Http\Resources;

use App\Models\Language;
use App\Models\Localization;
use Illuminate\Http\Resources\Json\JsonResource;

class LocalizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $translations = [
            'id' => $this->id,
            'key' => $this->key,
            'en' => $this->value,
            'platform' => $this->platform,
        ];
        $localizations = $this->getLocalization($this->id);

        foreach ($localizations as $localization) {
            $language = Language::where('id', $localization->language_id)->get()->first();
            $translations[$language->code] = $localization->value;
        }

        return $translations;
    }

    /**
     * @param $id
     * @return mixed
     */
    private function getLocalization($id)
    {
     $localizations = Localization::where('translation_id', $id)->get();

     return $localizations;
    }
}
