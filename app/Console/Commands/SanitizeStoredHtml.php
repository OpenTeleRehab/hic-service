<?php

namespace App\Console\Commands;

use App\Helpers\HtmlSanitizer;
use App\Models\PrivacyPolicy;
use App\Models\StaticPage;
use App\Models\TermAndCondition;
use Illuminate\Console\Command;

class SanitizeStoredHtml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hi:sanitize-stored-html {--dry-run : Only report the records that would change}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sanitize rich text saved before HTML sanitization was enforced on input';

    /**
     * Rich text (HTML) attributes per model. Every attribute is translatable.
     */
    const HTML_ATTRIBUTES = [
        StaticPage::class => ['content', 'partner_content'],
        PrivacyPolicy::class => ['content'],
        TermAndCondition::class => ['content'],
    ];

    /**
     * @return int
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        foreach (self::HTML_ATTRIBUTES as $modelClass => $attributes) {
            $changed = 0;

            // The models' global "order" scope sorts by published_date / title before id, which makes
            // chunkById skip rows, so page by id only.
            $modelClass::withoutGlobalScope('order')->chunkById(100, function ($records) use ($attributes, $dryRun, &$changed) {
                foreach ($records as $record) {
                    if ($this->sanitizeRecord($record, $attributes, $dryRun)) {
                        $changed++;
                    }
                }
            });

            $this->info(class_basename($modelClass) . ': ' . $changed . ' record(s) ' . ($dryRun ? 'would change' : 'sanitized'));
        }

        return self::SUCCESS;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $record
     * @param array $attributes
     * @param bool $dryRun
     *
     * @return bool whether any value changed
     */
    private function sanitizeRecord($record, array $attributes, bool $dryRun)
    {
        $isChanged = false;

        foreach ($attributes as $attribute) {
            foreach ($record->getTranslations($attribute) as $locale => $html) {
                if (!is_string($html)) {
                    continue;
                }

                $clean = HtmlSanitizer::clean($html);
                if ($clean !== $html) {
                    $record->setTranslation($attribute, $locale, $clean);
                    $isChanged = true;
                }
            }
        }

        if ($isChanged && !$dryRun) {
            // Keep updated_at and skip model events (e.g. auto translation): only the markup is cleaned.
            $record->timestamps = false;
            $record->saveQuietly();
        }

        return $isChanged;
    }
}
