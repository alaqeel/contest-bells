<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contestants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->unsignedSmallInteger('score')->default(0);
            $table->string('claim_token', 64)->nullable()->unique(); // session token issued on claim
            $table->string('session_id', 128)->nullable(); // browser session id
            $table->timestamp('claimed_at')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contestants');
    }
};
