<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\CalendarEvent;
use App\Models\HolidayEvent;
use App\Models\Note;
use Carbon\CarbonInterface;

trait FormatsCalendarRecords
{
    protected function formatNote(Note $note): array
    {
        return [
            'id' => $note->id,
            'date' => $note->date->toDateString(),
            'text' => $note->text,
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
        ];
    }

    protected function formatEvent(CalendarEvent $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'starts_at' => $event->starts_at?->setTimezone(config('app.timezone'))->toIso8601String(),
            'ends_at' => $event->ends_at?->setTimezone(config('app.timezone'))->toIso8601String(),
            'all_day' => $event->all_day,
            'location' => $event->location,
            'color' => $event->color,
            'reminder_minutes_before' => $event->reminder_minutes_before,
            'created_at' => $event->created_at?->toIso8601String(),
            'updated_at' => $event->updated_at?->toIso8601String(),
        ];
    }

    protected function formatHolidayEvent(HolidayEvent $holidayEvent, ?CarbonInterface $occurrenceDate = null): array
    {
        return [
            'id' => $holidayEvent->id,
            'name_km' => $holidayEvent->name_km,
            'name_en' => $holidayEvent->name_en,
            'date' => $holidayEvent->date->toDateString(),
            'end_date' => $holidayEvent->end_date?->toDateString(),
            'occurrence_date' => $occurrenceDate?->toDateString(),
            'type' => $holidayEvent->type,
            'source' => $holidayEvent->source,
            'is_fixed' => $holidayEvent->is_fixed,
            'is_recurring_yearly' => $holidayEvent->is_recurring_yearly,
            'description' => $holidayEvent->description,
            'notes' => $holidayEvent->notes,
            'created_at' => $holidayEvent->created_at?->toIso8601String(),
            'updated_at' => $holidayEvent->updated_at?->toIso8601String(),
        ];
    }
}
