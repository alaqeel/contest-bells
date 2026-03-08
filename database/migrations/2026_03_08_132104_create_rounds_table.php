<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round_number');
            $table->enum('status', ['pending', 'active', 'locked', 'completed'])->default('pending');
            $table->foreignId('first_buzz_contestant_id')->nullable()->constrained('contestants')->nullOnDelete();
            $table->timestamp('buzz_opened_at')->nullable();   // when judge started the round
            $table->timestamp('first_buzzed_at')->nullable();  // when first buzz was accepted
            $table->timestamp('answer_deadline_at')->nullable(); // first_buzzed_at + 10 seconds
            $table->timestamp('resolved_at')->nullable();      // when judge marked correct/wrong
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
