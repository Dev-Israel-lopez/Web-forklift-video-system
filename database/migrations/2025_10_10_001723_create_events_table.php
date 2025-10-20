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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
           $table->unsignedBigInteger('user_id');
            $table->string('timestamp_str', 32);     // "20251009_141530"
            $table->string('event', 64);             // "Persona detectada"
            $table->unsignedTinyInteger('zone_idx'); // 0,1,2...
            $table->string('level', 16);             // red|yellow|green
            $table->boolean('save')->default(false);
            $table->string('filename', 255)->nullable();
            $table->string('forklift_name', 128)->nullable();
            $table->decimal('confidence', 5, 3)->nullable(); // 0.000 - 999.999
            $table->json('meta')->nullable();        // por si mandas extra

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'timestamp_str']);
            $table->index(['level', 'zone_idx']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
