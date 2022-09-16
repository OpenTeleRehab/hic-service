<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGlobalEducationMaterialIdAndGlobalToEducationMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('education_materials', function (Blueprint $table) {
            $table->integer('global_education_material_id');
            $table->boolean('global');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('education_materials', function (Blueprint $table) {
            $table->dropColumn('global_education_material_id');
            $table->dropColumn('global');
        });
    }
}
