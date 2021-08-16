<?php

namespace App\Listeners;

use App\Events\ApplyMaterialAutoTranslationEvent;
use App\Helpers\GoogleTranslateHelper;
use App\Models\Language;
use Illuminate\Support\Facades\App;

class ApplyMaterialAutoTranslationListener
{

    /**
     * Handle the event.
     *
     * @param ApplyMaterialAutoTranslationEvent $event
     *
     * @return void
     */
    public function handle(ApplyMaterialAutoTranslationEvent $event)
    {
        if (App::getLocale() !== config('app.locale')) {
            return;
        }

        $translate = new GoogleTranslateHelper();
        $supportedLanguages = $translate->supportedLanguages();
        $educationMaterial = $event->educationMaterial;
        $languages = Language::where('code', '<>', config('app.locale'))->get();
        foreach ($languages as $language) {
            $languageCode = $language->code;
            if (!in_array($languageCode, $supportedLanguages)) {
                continue;
            }

            // Do not override the static translation.
            $autoTranslated = $educationMaterial->getTranslation('auto_translated', $languageCode);
            if ($autoTranslated === false) {
                continue;
            }

            $translatedTitle = $translate->translate($educationMaterial->title, $languageCode);
            $educationMaterial->setTranslation('title', $languageCode, $translatedTitle);
            $educationMaterial->setTranslation('auto_translated', $languageCode, true);
        }
        $educationMaterial->save();
    }
}
