<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNullableToGlobalIdColumnToEachResourceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('global_category_id')->nullable()->change();
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->integer('global_exercise_id')->nullable()->change();
        });

        Schema::table('education_materials', function (Blueprint $table) {
            $table->integer('global_education_material_id')->nullable()->change();
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->integer('global_questionnaire_id')->nullable()->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->integer('global_question_id')->nullable()->change();
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->integer('global_answer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
