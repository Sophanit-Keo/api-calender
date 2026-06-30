<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('api');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['user_id', 'date']);
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['user_id', 'starts_at']);
            $table->index(['user_id', 'ends_at']);
        });

        Schema::table('holiday_events', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'end_date']);
            $table->index(['user_id', 'type']);
        });

        Schema::table('work_shift_templates', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'code']);
        });

        Schema::table('work_schedule_settings', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique('user_id');
        });

        Schema::table('work_schedule_cycles', function (Blueprint $table): void {
            $table->dropUnique(['cycle_start_date']);
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'cycle_start_date']);
        });

        Schema::table('work_schedule_days', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::table('work_schedule_days', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'work_date']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('work_schedule_cycles', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'cycle_start_date']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('cycle_start_date');
        });

        Schema::table('work_schedule_settings', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('work_shift_templates', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'code']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('code');
        });

        Schema::table('holiday_events', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['user_id', 'end_date']);
            $table->dropIndex(['user_id', 'type']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'starts_at']);
            $table->dropIndex(['user_id', 'ends_at']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'date']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::dropIfExists('api_tokens');
    }
};
