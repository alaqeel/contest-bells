<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contestant_lockouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contestant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->timestamp('locked_until');
            $table->timestamps();

            $table->unique(['contestant_id', 'round_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contestant_lockouts');
    }
};
