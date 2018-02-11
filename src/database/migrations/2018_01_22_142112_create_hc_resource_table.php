<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHcResourcesTable
 */
class CreateHcResourceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('hc_resource', function (Blueprint $table) {
            $table->increments('count');
            $table->uuid('id')->unique();
            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->datetime('deleted_at')->nullable();

            $table->uuid('uploaded_by')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('safe_name');
            $table->string('extension', 128);
            $table->string('mime_type', 128);
            $table->bigInteger('size');
            $table->string('checksum', 64)->nullable();

            $table->foreign('uploaded_by')->references('id')->on('hc_user')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('hc_resource');
    }
}
