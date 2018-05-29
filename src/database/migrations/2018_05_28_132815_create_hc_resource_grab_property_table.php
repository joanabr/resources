<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHcResourceGrabPropertyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hc_resource_grab_property', function (Blueprint $table) {
            $table->increments('count');
            $table->uuid('id')->unique();
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('deleted_at')->nullable();

            $table->uuid('thumbnail_id');
            $table->uuid('resource_id');

            $table->uuid('source_id');
            $table->string('source_type');

            $table->integer('x')->nullable();
            $table->integer('y')->nullable();
            $table->double('zoom')->nullable();

            $table->foreign('resource_id')->references('id')->on('hc_resource')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('thumbnail_id')->references('id')->on('hc_resource_thumbnail')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hc_resource_grab_property');
    }
}
