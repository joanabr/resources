<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class AddDiskFieldToHcResourceTable
 */
class AddDiskFieldToHcResourceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('hc_resource', function (Blueprint $table) {
            $table->string('disk', 30)->default('local');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('hc_resource', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
}
