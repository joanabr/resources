<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHcResourcesTranslationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hc_resource_translation', function (Blueprint $table) {

            $table->increments('count');
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->uuid('record_id');
            $table->string('language_code', 2);

            $table->unique(['record_id', 'language_code']);

            $table->string('label');
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();

            $table->foreign('record_id')->references('id')->on('hc_resource')
                ->onUpdate('NO ACTION')->onDelete('NO ACTION');

            $table->foreign('language_code')->references('iso_639_1')->on('hc_language')
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
        Schema::dropIfExists('hc_resource_translation');
    }
}
