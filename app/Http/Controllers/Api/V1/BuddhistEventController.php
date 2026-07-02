<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BuddhistEventService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuddhistEventController extends Controller
{
    public function __construct(
        private readonly BuddhistEventService $buddhistEvents,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $type = $validated['type'] ?? null;

        if ($validated['date'] ?? null) {
            $events = $this->buddhistEvents->eventsForDate($validated['date'], $type);
        } elseif (($validated['from'] ?? null) || ($validated['to'] ?? null)) {
            $from = $validated['from'] ?? $validated['to'];
            $to = $validated['to'] ?? $validated['from'];
            $events = $this->buddhistEvents->eventsForRange($from, $to, $type);
        } else {
            $year = (int) ($validated['year'] ?? CarbonImmutable::now(config('app.timezone'))->year);
            $events = $this->buddhistEvents->eventsForYear($year, $type);
        }

        return response()->json([
            'data' => $events,
            'meta' => $this->buddhistEvents->meta(),
        ]);
    }
}
