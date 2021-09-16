<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAdditionalAcknowledgmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('additional_acknowledgments');

        Schema::table('static_pages', function($table) {
            $table->dropColumn('additional_acknowledgment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('additional_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->json('hide_contributors');
        });

        Schema::table('static_pages', function($table) {
            $table->integer('additional_acknowledgment_id');
        });
    }
}
