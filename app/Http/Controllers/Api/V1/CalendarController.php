<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\FormatsCalendarRecords;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\HolidayEvent;
use App\Models\Note;
use App\Services\KhmerCalendarService;
use App\Services\WorkScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    use FormatsCalendarRecords;

    public function __construct(
        private readonly KhmerCalendarService $khmerCalendar,
        private readonly WorkScheduleService $workSchedule,
    ) {}

    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'data' => $this->khmerCalendar->getKhmerDate($validated['date']),
        ]);
    }

    public function day(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], config('app.timezone'))->startOfDay();

        return response()->json([
            'data' => $this->dayPayload($request->user()->id, $date),
        ]);
    }

    public function month(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:1900', 'max:2200'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $days = collect($this->khmerCalendar->getGregorianMonthDays((int) $validated['year'], (int) $validated['month']))
            ->map(function (array $calendarDay) use ($request): array {
                $date = CarbonImmutable::parse($calendarDay['date'], config('app.timezone'))->startOfDay();

                return $this->dayPayload($request->user()->id, $date, $calendarDay);
            })
            ->values();

        return response()->json([
            'data' => [
                'year' => (int) $validated['year'],
                'month' => (int) $validated['month'],
                'days' => $days,
            ],
        ]);
    }

    private function dayPayload(int $userId, CarbonImmutable $date, ?array $calendar = null): array
    {
        return [
            'calendar' => $calendar ?? $this->khmerCalendar->getKhmerDate($date),
            'notes' => Note::query()
                ->where('user_id', $userId)
                ->whereDate('date', $date->toDateString())
                ->orderBy('created_at')
                ->get()
                ->map(fn (Note $note): array => $this->formatNote($note))
                ->values(),
            'events' => $this->eventsForDate($userId, $date),
            'holiday_events' => $this->holidayEventsForDate($userId, $date),
            'work_shift' => $this->workSchedule->materializeDays($userId, $date, $date)[0] ?? null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function eventsForDate(int $userId, CarbonImmutable $date): array
    {
        $from = $date->startOfDay();
        $to = $date->endOfDay();

        return CalendarEvent::query()
            ->where('user_id', $userId)
            ->where('starts_at', '<=', $to)
            ->where(function (Builder $query) use ($from): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $from);
            })
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->formatEvent($event))
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function holidayEventsForDate(int $userId, CarbonImmutable $date): array
    {
        return HolidayEvent::query()
            ->where('user_id', $userId)
            ->orderBy('date')
            ->get()
            ->filter(fn (HolidayEvent $holiday): bool => $this->holidayOccursOn($holiday, $date))
            ->map(fn (HolidayEvent $holiday): array => $this->formatHolidayEvent($holiday, $date))
            ->values()
            ->all();
    }

    private function holidayOccursOn(HolidayEvent $holiday, CarbonImmutable $date): bool
    {
        if (! $holiday->is_recurring_yearly) {
            $start = CarbonImmutable::parse($holiday->date->toDateString(), config('app.timezone'))->startOfDay();
            $end = CarbonImmutable::parse(($holiday->end_date ?? $holiday->date)->toDateString(), config('app.timezone'))->startOfDay();

            return $date->betweenIncluded($start, $end);
        }

        if (! checkdate($holiday->date->month, $holiday->date->day, $date->year)) {
            return false;
        }

        $start = CarbonImmutable::create($date->year, $holiday->date->month, $holiday->date->day, 0, 0, 0, config('app.timezone'));
        $endSource = $holiday->end_date ?? $holiday->date;

        if (! checkdate($endSource->month, $endSource->day, $date->year)) {
            return false;
        }

        $end = CarbonImmutable::create($date->year, $endSource->month, $endSource->day, 0, 0, 0, config('app.timezone'));

        if ($end->lt($start)) {
            $end = $end->addYear();
        }

        return $date->betweenIncluded($start, $end);
    }
}
