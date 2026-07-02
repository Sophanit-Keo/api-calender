<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PublicHolidayService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function __construct(
        private readonly PublicHolidayService $publicHolidays,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        if ($validated['date'] ?? null) {
            $holidays = $this->publicHolidays->holidaysForDate($validated['date']);
        } elseif (($validated['from'] ?? null) || ($validated['to'] ?? null)) {
            $from = $validated['from'] ?? $validated['to'];
            $to = $validated['to'] ?? $validated['from'];
            $holidays = $this->publicHolidays->holidaysForRange($from, $to);
        } else {
            $year = (int) ($validated['year'] ?? CarbonImmutable::now(config('app.timezone'))->year);
            $holidays = $this->publicHolidays->holidaysForYear($year);
        }

        return response()->json([
            'data' => $holidays,
            'meta' => $this->publicHolidays->meta(),
        ]);
    }
}
