<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnsInTermAndConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('term_and_conditions', function (Blueprint $table) {
            $table->dropColumn('version');
            $table->dropColumn('published_date');
            $table->dropColumn('status');
            $table->json('title');
            $table->integer('file_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('term_and_conditions', function (Blueprint $table) {
            $table->string('version');
            $table->dateTime('published_date')->nullable();
            $table->string('status');
            $table->dropColumn('title');
            $table->dropColumn('file_id');
        });
    }
}
