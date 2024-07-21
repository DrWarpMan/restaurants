<?php

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
            $table->foreignId("restaurant_id")->constrained()->cascadeOnDelete();
            // TODO
            $table->tinyInteger("day"); // 1-7
            $table->integer("from"); // 0-86400
            $table->integer("to"); // 0-86400
            $table->timestamps();
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
