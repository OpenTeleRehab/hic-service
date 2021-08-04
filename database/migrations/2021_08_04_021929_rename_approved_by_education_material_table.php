<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameApprovedByEducationMaterialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('education_materials', function (Blueprint $table) {
            $table->renameColumn('approved_by', 'reviewed_by');
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
            $table->renameColumn('reviewed_by', 'approved_by');
        });
    }
}
