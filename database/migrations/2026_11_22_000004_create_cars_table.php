<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('showroom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('base_price');
            $table->string('fuel_type');
            $table->string('transmission');
            $table->string('engine');
            $table->unsignedSmallInteger('power_hp');
            $table->unsignedSmallInteger('torque_nm');
            $table->decimal('accel_0_100', 4, 1)->nullable();
            $table->unsignedSmallInteger('top_speed');
            $table->unsignedInteger('range_km')->nullable();
            $table->unsignedTinyInteger('seats');
            $table->unsignedInteger('mileage_km')->default(0);
            $table->string('condition');
            $table->unsignedInteger('stock')->default(0);
            $table->string('badge')->nullable();
            $table->string('status')->default('draft');
            $table->text('description')->nullable();
            $table->json('hotspots')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
