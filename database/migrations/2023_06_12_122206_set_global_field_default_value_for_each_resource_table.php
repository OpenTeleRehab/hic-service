<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetGlobalFieldDefaultValueForEachResourceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->boolean('global')->default(false)->change();
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->boolean('global')->default(false)->change();
        });

        Schema::table('education_materials', function (Blueprint $table) {
            $table->boolean('global')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->boolean('global')->default(null)->change();
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->boolean('global')->default(null)->change();
        });

        Schema::table('education_materials', function (Blueprint $table) {
            $table->boolean('global')->default(null)->change();
        });
    }
}
