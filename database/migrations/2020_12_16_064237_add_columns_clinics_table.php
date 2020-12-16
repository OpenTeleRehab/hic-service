<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsClinicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->integer('country_id');
            $table->string('region');
            $table->string('province')->nullable();
            $table->string('city');
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
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn('country_id');
            $table->dropColumn('region');
            $table->dropColumn('province');
            $table->dropColumn('city');
            $table->integer('identity')->nullable();
        });
    }
}
