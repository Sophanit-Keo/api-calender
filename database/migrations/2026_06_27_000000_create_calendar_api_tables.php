<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->index();
            $table->text('text');
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('reminder_minutes_before')->nullable();
            $table->timestamps();
        });

        Schema::create('holiday_events', function (Blueprint $table): void {
            $table->id();
            $table->string('name_km')->nullable();
            $table->string('name_en')->nullable();
            $table->date('date')->index();
            $table->date('end_date')->nullable()->index();
            $table->string('type', 50)->default('custom')->index();
            $table->string('source', 50)->default('manual');
            $table->boolean('is_fixed')->default(false);
            $table->boolean('is_recurring_yearly')->default(false);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('work_shift_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('work_schedule_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('system_type')->default(2);
            $table->boolean('remind')->default(true);
            $table->unsignedSmallInteger('reminder_minutes_before')->default(30);
            $table->timestamps();
        });

        Schema::create('work_schedule_cycles', function (Blueprint $table): void {
            $table->id();
            $table->date('cycle_start_date')->unique();
            $table->date('cycle_end_date');
            $table->timestamps();
        });

        Schema::create('work_schedule_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_schedule_cycle_id')->constrained()->cascadeOnDelete();
            $table->date('work_date')->index();
            $table->unsignedTinyInteger('day_offset');
            $table->foreignId('work_shift_template_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['work_schedule_cycle_id', 'work_date']);
            $table->unique(['work_schedule_cycle_id', 'day_offset']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedule_days');
        Schema::dropIfExists('work_schedule_cycles');
        Schema::dropIfExists('work_schedule_settings');
        Schema::dropIfExists('work_shift_templates');
        Schema::dropIfExists('holiday_events');
        Schema::dropIfExists('events');
        Schema::dropIfExists('notes');
    }
};
