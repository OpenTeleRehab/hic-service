<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterInternationalClassificationDiseasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('international_classification_diseases', function (Blueprint $table) {
            $table->dropColumn('name');
        });
        Schema::table('international_classification_diseases', function (Blueprint $table) {
            $table->json('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('international_classification_diseases', function (Blueprint $table) {
            $table->dropColumn('name');
        });
        Schema::table('international_classification_diseases', function (Blueprint $table) {
            $table->string('name');
        });
    }
}
