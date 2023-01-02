<?php

namespace App\Console\Commands;

use App\Helpers\KeycloakHelper;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncCategoryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hi:sync-category-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync categories data from global to open library';

    /**
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle()
    {
        // Get categories from Global.
        $globalCategories = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-categories-for-open-library'));

        // Import categories from to library.
        $this->output->progressStart(count($globalCategories));
        $globalCategoryIds = [];
        foreach ($globalCategories as $globalCategory) {
            $this->output->progressAdvance();
            $globalCategoryIds[] = $globalCategory->id;
            $parentCategory = Category::where('global_category_id', $globalCategory->parent_id)->first();
            Category::updateOrCreate(
                [
                    'global_category_id' => $globalCategory->id,
                ],
                [
                    'title' => json_encode($globalCategory->title),
                    'global_category_id' => $globalCategory->id,
                    'type' => $globalCategory->type,
                    'parent_id' => $parentCategory ? $parentCategory->id : null,
                ]
            );
        }

        // Remove the previous global synced.
        Category::where('global_category_id', '<>', null)
            ->whereNotIn('global_category_id', $globalCategoryIds)->delete();

        $this->output->progressFinish();

        $this->info('Category data has been sync successfully');
    }
}
