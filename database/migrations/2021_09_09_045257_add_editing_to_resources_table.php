<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEditingToResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->unsignedInteger('editing_by')->nullable();
            $table->timestamp('editing_at')->nullable();
        });

        Schema::table('education_materials', function (Blueprint $table) {
            $table->unsignedInteger('editing_by')->nullable();
            $table->timestamp('editing_at')->nullable();
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->unsignedInteger('editing_by')->nullable();
            $table->timestamp('editing_at')->nullable();
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
            $table->dropColumn('editing_by');
            $table->dropColumn('editing_at');
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropColumn('editing_by');
            $table->dropColumn('editing_at');
        });

        Schema::table('education_materials', function (Blueprint $table) {
            $table->dropColumn('editing_by');
            $table->dropColumn('editing_at');
        });
    }
}
