<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId("restaurant_id")->constrained()->cascadeOnDelete();
            $table->tinyInteger("day"); // 1-7 (1 = Monday, 7 = Sunday)
            $table->integer("opens"); // represents second of the day 0-86400
            $table->integer("closes"); // 0-86400
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};
