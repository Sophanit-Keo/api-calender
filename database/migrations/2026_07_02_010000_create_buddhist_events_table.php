<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buddhist_events', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 2)->index();
            $table->string('country', 100);
            $table->string('code', 120);
            $table->string('name_km')->nullable();
            $table->string('name_en');
            $table->date('date')->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('type', 50)->index();
            $table->string('tradition', 50)->default('theravada')->index();
            $table->boolean('is_public_holiday')->default(false);
            $table->string('lunar_month_name')->nullable();
            $table->unsignedTinyInteger('lunar_day')->nullable();
            $table->boolean('is_waxing')->nullable();
            $table->unsignedSmallInteger('buddhist_era')->nullable();
            $table->unsignedSmallInteger('day_number')->default(1);
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->string('source')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->timestamps();

            $table->unique(
                ['country_code', 'date', 'code', 'day_number'],
                'buddhist_events_country_date_code_day_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buddhist_events');
    }
};
