<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportDefaultTranslation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hi:import-default-translation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import default translation with key and value';

    /**
     * The console command example helper.
     *
     * @var string
     */
    protected $help = 'php artisan hi:import-default-translation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $platforms = [
            Translation::ADMIN_PORTAL,
            Translation::THERAPIST_PORTAL,
            Translation::PATIENT_APP
        ];
        foreach ($platforms as $platform) {
            $this->alert('Start importing: ' . $platform);
            $localeContent = Storage::get("translation/$platform.json");
            $translateData = json_decode($localeContent, true) ?? [];

            $this->output->progressStart(count($translateData));
            foreach ($translateData as $key => $value) {
                $translateKeyPlatform = Translation::where('key', $key)->where('platform', $platform)->first();
                if (!$translateKeyPlatform) {
                    Translation::create([
                        'key' => $key,
                        'value' => $value,
                        'platform' => $platform
                    ]);
                }
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();
        }
        return 0;
    }
}
