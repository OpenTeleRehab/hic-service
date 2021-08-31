<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterStaticPageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn('platform');
            $table->dropColumn('private');
            $table->dropColumn('background_color');
            $table->dropColumn('text_color');
            $table->json('partner_content')->nullable(true);
            $table->integer('additional_home_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->string('platform');
            $table->boolean('private')->default(false);
            $table->string('background_color')->nullable(true);
            $table->string('text_color')->nullable(true);
            $table->dropColumn('partner_content');
            $table->dropColumn('additional_home_id');
        });
    }
}
