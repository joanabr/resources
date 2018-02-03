<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHcResourcesTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hc_resources_translations', function (Blueprint $table) {

            $table->increments('count');
            $table->uuid('id')->unique();
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->datetime('deleted_at')->nullable();

            $table->uuid('record_id');
            $table->uuid('language_code');

            $table->string('label');
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();

            $table->foreign('record_id')->references('id')->on('hc_resources')
                ->onUpdate('NO ACTION')->onDelete('NO ACTION');

            $table->foreign('language_code')->references('iso_639_1')->on('hc_languages')
                ->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hc_resources_translations');
    }
}
