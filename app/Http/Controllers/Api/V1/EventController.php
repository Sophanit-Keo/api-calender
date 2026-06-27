<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\FormatsCalendarRecords;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventController extends Controller
{
    use FormatsCalendarRecords;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = CalendarEvent::query();

        if ($date = $validated['date'] ?? null) {
            $day = CarbonImmutable::parse($date, config('app.timezone'));
            $this->whereOverlaps($query, $day->startOfDay(), $day->endOfDay());
        } elseif (($validated['from'] ?? null) || ($validated['to'] ?? null)) {
            $from = CarbonImmutable::parse($validated['from'] ?? $validated['to'], config('app.timezone'))->startOfDay();
            $to = CarbonImmutable::parse($validated['to'] ?? $validated['from'], config('app.timezone'))->endOfDay();
            $this->whereOverlaps($query, $from, $to);
        }

        $events = $query
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->formatEvent($event))
            ->values();

        return response()->json(['data' => $events]);
    }

    public function store(Request $request): JsonResponse
    {
        $event = CalendarEvent::query()->create($this->validatedEvent($request) + [
            'all_day' => $request->boolean('all_day'),
        ]);

        return response()->json(['data' => $this->formatEvent($event)], Response::HTTP_CREATED);
    }

    public function show(CalendarEvent $event): JsonResponse
    {
        return response()->json(['data' => $this->formatEvent($event)]);
    }

    public function update(Request $request, CalendarEvent $event): JsonResponse
    {
        $data = $this->validatedEvent($request, updating: true);

        if ($request->has('all_day')) {
            $data['all_day'] = $request->boolean('all_day');
        }

        $event->fill($data)->save();

        return response()->json(['data' => $this->formatEvent($event->refresh())]);
    }

    public function destroy(CalendarEvent $event): Response
    {
        $event->delete();

        return response()->noContent();
    }

    private function validatedEvent(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => [$required, 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['sometimes', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ]);
    }

    private function whereOverlaps(Builder $query, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $query->where('starts_at', '<=', $to)
            ->where(function (Builder $query) use ($from): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $from);
            });
    }
}
