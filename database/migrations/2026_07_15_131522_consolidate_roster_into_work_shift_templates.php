<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_shift_templates', function (Blueprint $table) {
            $table->enum('category', ['shift', 'leave'])->default('shift')->after('name');
            $table->string('color')->default('blue')->after('sort_order');
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
        });

        Schema::dropIfExists('roster_entries');
        Schema::dropIfExists('roster_codes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_shift_templates', function (Blueprint $table) {
            $table->dropColumn(['category', 'color']);
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
        });

        Schema::create('roster_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->enum('category', ['shift', 'leave'])->default('shift');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('color')->default('blue');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('roster_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('roster_code_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
            $table->index('work_date');
        });
    }
};
