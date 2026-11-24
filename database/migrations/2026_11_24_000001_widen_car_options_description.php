<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Option descriptions are validated up to 300 characters; varchar(255) would cut them off.
     */
    public function up(): void
    {
        Schema::table('car_options', function (Blueprint $table) {
            $table->string('description', 300)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('car_options', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
    }
};
