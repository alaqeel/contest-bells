<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            // Optional judge identification for admin reporting.
            // Null for competitions created before this feature.
            $table->string('judge_name')->nullable()->after('title');
            $table->string('judge_email')->nullable()->after('judge_name');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['judge_name', 'judge_email']);
        });
    }
};
