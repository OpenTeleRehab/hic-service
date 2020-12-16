<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('iso_code');
            $table->string('phone_code');
            $table->integer('language_id')->nullable();
            $table->dropColumn('identity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
           $table->dropColumn('iso_code');
           $table->dropColumn('phone_code');
           $table->dropColumn('language_id');
           $table->string('identity')->nullable();
        });
    }
}
