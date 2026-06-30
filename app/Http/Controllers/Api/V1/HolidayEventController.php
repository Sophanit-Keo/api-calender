<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\FormatsCalendarRecords;
use App\Http\Controllers\Controller;
use App\Models\HolidayEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HolidayEventController extends Controller
{
    use FormatsCalendarRecords;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $holidays = HolidayEvent::query()
            ->where('user_id', $request->user()->id)
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('date')
            ->get();

        if ($validated['date'] ?? null) {
            $from = $to = CarbonImmutable::parse($validated['date'], config('app.timezone'))->startOfDay();
            $holidays = $holidays->filter(fn (HolidayEvent $holiday): bool => $this->occursInRange($holiday, $from, $to));
        } elseif (($validated['from'] ?? null) || ($validated['to'] ?? null)) {
            $from = CarbonImmutable::parse($validated['from'] ?? $validated['to'], config('app.timezone'))->startOfDay();
            $to = CarbonImmutable::parse($validated['to'] ?? $validated['from'], config('app.timezone'))->startOfDay();
            $holidays = $holidays->filter(fn (HolidayEvent $holiday): bool => $this->occursInRange($holiday, $from, $to));
        }

        return response()->json([
            'data' => $holidays
                ->map(fn (HolidayEvent $holiday): array => $this->formatHolidayEvent($holiday))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $holiday = HolidayEvent::query()->create($this->validatedHoliday($request) + [
            'user_id' => $request->user()->id,
            'type' => $request->input('type', 'custom'),
            'source' => $request->input('source', 'manual'),
            'is_fixed' => $request->boolean('is_fixed'),
            'is_recurring_yearly' => $request->boolean('is_recurring_yearly'),
        ]);

        return response()->json(['data' => $this->formatHolidayEvent($holiday)], Response::HTTP_CREATED);
    }

    public function show(Request $request, HolidayEvent $holidayEvent): JsonResponse
    {
        $this->abortIfNotOwner($request, $holidayEvent);

        return response()->json(['data' => $this->formatHolidayEvent($holidayEvent)]);
    }

    public function update(Request $request, HolidayEvent $holidayEvent): JsonResponse
    {
        $this->abortIfNotOwner($request, $holidayEvent);

        $data = $this->validatedHoliday($request, updating: true);

        foreach (['is_fixed', 'is_recurring_yearly'] as $booleanField) {
            if ($request->has($booleanField)) {
                $data[$booleanField] = $request->boolean($booleanField);
            }
        }

        $holidayEvent->fill($data)->save();

        return response()->json(['data' => $this->formatHolidayEvent($holidayEvent->refresh())]);
    }

    public function destroy(Request $request, HolidayEvent $holidayEvent): Response
    {
        $this->abortIfNotOwner($request, $holidayEvent);

        $holidayEvent->delete();

        return response()->noContent();
    }

    private function validatedHoliday(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';
        $nameKmRules = $updating
            ? ['nullable', 'string', 'max:255']
            : ['nullable', 'required_without:name_en', 'string', 'max:255'];
        $nameEnRules = $updating
            ? ['nullable', 'string', 'max:255']
            : ['nullable', 'required_without:name_km', 'string', 'max:255'];

        return $request->validate([
            'name_km' => $nameKmRules,
            'name_en' => $nameEnRules,
            'date' => [$required, 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'type' => ['sometimes', 'string', 'max:50'],
            'source' => ['sometimes', 'string', 'max:50'],
            'is_fixed' => ['sometimes', 'boolean'],
            'is_recurring_yearly' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function occursInRange(HolidayEvent $holiday, CarbonImmutable $from, CarbonImmutable $to): bool
    {
        if (! $holiday->is_recurring_yearly) {
            $start = CarbonImmutable::parse($holiday->date->toDateString(), config('app.timezone'))->startOfDay();
            $end = CarbonImmutable::parse(($holiday->end_date ?? $holiday->date)->toDateString(), config('app.timezone'))->startOfDay();

            return $start->lte($to) && $end->gte($from);
        }

        for ($year = $from->year; $year <= $to->year; $year++) {
            $start = CarbonImmutable::create($year, $holiday->date->month, $holiday->date->day, 0, 0, 0, config('app.timezone'));
            $endSource = $holiday->end_date ?? $holiday->date;
            $end = CarbonImmutable::create($year, $endSource->month, $endSource->day, 0, 0, 0, config('app.timezone'));

            if ($end->lt($start)) {
                $end = $end->addYear();
            }

            if ($start->lte($to) && $end->gte($from)) {
                return true;
            }
        }

        return false;
    }

    private function abortIfNotOwner(Request $request, HolidayEvent $holidayEvent): void
    {
        abort_if($holidayEvent->user_id !== $request->user()->id, Response::HTTP_NOT_FOUND);
    }
}
