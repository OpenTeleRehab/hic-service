<?php

namespace App\Console\Commands;

use App\Helpers\FileHelper;
use App\Helpers\KeycloakHelper;
use App\Models\Category;
use App\Models\EducationMaterial;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        foreach ($globalCategories as $globalCategory) {
            $this->output->progressAdvance();
            $parentCategory = Category::where('global_category_id', $globalCategory->parent_id)->first();
            DB::table('categories')->updateOrInsert(
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
        $this->output->progressFinish();

        $this->info('Category data has been sync successfully');
    }
}
