<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEducationMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('education_materials', function (Blueprint $table) {
            $table->string('status');
            $table->string('hash')->nullable(true);
            $table->integer('approved_by')->nullable();
            $table->integer('uploaded_by')->nullable();
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
            $table->dropColumn('status');
            $table->dropColumn('hash');
            $table->dropColumn('approved_by');
            $table->dropColumn('uploaded_by');
        });
    }
}
