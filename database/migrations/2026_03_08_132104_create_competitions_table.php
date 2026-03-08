<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 8)->unique(); // public shareable code
            $table->string('judge_token', 64)->unique(); // secret token for judge
            $table->string('title')->default('Quiz Competition');
            $table->enum('status', ['setup', 'active', 'ended'])->default('setup');
            $table->unsignedTinyInteger('contestant_count')->default(2);
            // nullable — not a FK constraint here to avoid circular dependency with rounds table
            $table->unsignedBigInteger('current_round_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
