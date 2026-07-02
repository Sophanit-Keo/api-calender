<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_holidays', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 2)->index();
            $table->string('country', 100);
            $table->string('code', 120);
            $table->string('name_km')->nullable();
            $table->string('name_en');
            $table->date('date')->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('type', 50)->default('public_national')->index();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_national')->default(true);
            $table->unsignedTinyInteger('day_number')->default(1);
            $table->unsignedTinyInteger('duration_days')->default(1);
            $table->string('source')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->timestamps();

            $table->unique(
                ['country_code', 'date', 'code', 'day_number'],
                'public_holidays_country_date_code_day_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
