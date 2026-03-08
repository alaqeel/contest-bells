<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buzz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contestant_id')->constrained()->cascadeOnDelete();
            $table->timestamp('attempted_at'); // server timestamp of incoming request
            $table->boolean('accepted')->default(false);
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['round_id', 'accepted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buzz_attempts');
    }
};
