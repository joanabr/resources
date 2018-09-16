<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWidthHeightFieldsToHcResourceGrabPropertyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hc_resource_grab_property', function (Blueprint $table) {
            $table->string('width');
            $table->string('height');
            $table->string('x')->change();
            $table->string('y')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hc_resource_grab_property', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
            $table->integer('x')->change();
            $table->integer('y')->change();
        });
    }
}
