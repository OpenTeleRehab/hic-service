<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLocalizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('localizations', function (Blueprint $table) {
            $table->dropColumn('sys_lang');
            $table->integer('language_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('localizations', function (Blueprint $table) {
            $table->dropColumn('language_id');
            $table->string('sys_lang');
        });
    }
}
