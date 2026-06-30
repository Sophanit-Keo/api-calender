<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HolidayEventController;
use App\Http\Controllers\Api\V1\NoteController;
use App\Http\Controllers\Api\V1\WorkScheduleController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(AuthenticateApiToken::class)->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('calendar/convert', [CalendarController::class, 'convert']);
        Route::get('calendar/day', [CalendarController::class, 'day']);
        Route::get('calendar/month', [CalendarController::class, 'month']);

        Route::apiResource('notes', NoteController::class);
        Route::apiResource('events', EventController::class);
        Route::apiResource('holiday-events', HolidayEventController::class);

        Route::get('work-schedule/settings', [WorkScheduleController::class, 'settings']);
        Route::put('work-schedule/settings', [WorkScheduleController::class, 'updateSettings']);
        Route::get('work-schedule/cycles/{cycle_start_date}', [WorkScheduleController::class, 'cycle']);
        Route::put('work-schedule/cycles/{cycle_start_date}', [WorkScheduleController::class, 'updateCycle']);
        Route::get('work-schedule/days', [WorkScheduleController::class, 'days']);
    });
});
