<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalFieldsToHcResourceAuthorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hc_resource_author', function (Blueprint $table) {

            $table->string('description')->nullable();
            $table->string('copyright')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hc_resource_author', function (Blueprint $table) {

            $table->dropColumn(['description', 'copyright']);
        });
    }
}
