<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Rich text saved before input sanitization was enforced is served unchanged by the JSON API
 * (rendered with dangerouslySetInnerHTML), so clean it once on deploy.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('hi:sanitize-stored-html');
    }

    /**
     * Reverse the migrations.
     *
     * Removed markup is unsafe by definition and is not restored.
     *
     * @return void
     */
    public function down()
    {
    }
};
