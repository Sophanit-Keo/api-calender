<?php

namespace App\Services;

use App\Models\BuddhistEvent;
use Carbon\CarbonImmutable;

class BuddhistEventService
{
    public const COUNTRY_CODE = 'KH';

    public const COUNTRY_NAME = 'Cambodia';

    /** @return list<int> */
    public function supportedYears(): array
    {
        return BuddhistEvent::query()
            ->where('country_code', self::COUNTRY_CODE)
            ->orderBy('date')
            ->pluck('date')
            ->map(fn (mixed $date): int => CarbonImmutable::parse((string) $date, config('app.timezone'))->year)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array{country_code:string, country:string, source:?string, source_url:?string, supported_years:list<int>} */
    public function meta(): array
    {
        $firstEvent = BuddhistEvent::query()
            ->where('country_code', self::COUNTRY_CODE)
            ->orderBy('date')
            ->first(['source', 'source_url']);

        return [
            'country_code' => self::COUNTRY_CODE,
            'country' => self::COUNTRY_NAME,
            'source' => $firstEvent?->source,
            'source_url' => $firstEvent?->source_url,
            'supported_years' => $this->supportedYears(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function eventsForYear(int $year, ?string $type = null): array
    {
        return $this->eventsForRange("{$year}-01-01", "{$year}-12-31", $type);
    }

    /** @return list<array<string, mixed>> */
    public function eventsForDate(CarbonImmutable|string $date, ?string $type = null): array
    {
        $date = $this->date($date)->toDateString();

        return $this->query($type)
            ->where('date', $date)
            ->get()
            ->map(fn (BuddhistEvent $event): array => $this->formatEvent($event))
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function eventsForRange(CarbonImmutable|string $from, CarbonImmutable|string $to, ?string $type = null): array
    {
        $from = $this->date($from);
        $to = $this->date($to);

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return $this->query($type)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn (BuddhistEvent $event): array => $this->formatEvent($event))
            ->values()
            ->all();
    }

    private function query(?string $type = null)
    {
        return BuddhistEvent::query()
            ->where('country_code', self::COUNTRY_CODE)
            ->when($type, fn ($query, string $eventType) => $query->where('type', $eventType))
            ->orderBy('date')
            ->orderBy('type')
            ->orderBy('code')
            ->orderBy('day_number');
    }

    private function formatEvent(BuddhistEvent $event): array
    {
        return [
            'id' => sprintf(
                '%s-buddhist-%d-%s-%d',
                strtolower($event->country_code),
                $event->date->year,
                $event->code,
                $event->day_number,
            ),
            'country_code' => $event->country_code,
            'country' => $event->country,
            'date' => $event->date->toDateString(),
            'name_km' => $event->name_km,
            'name_en' => $event->name_en,
            'type' => $event->type,
            'tradition' => $event->tradition,
            'is_public_holiday' => $event->is_public_holiday,
            'lunar_month_name' => $event->lunar_month_name,
            'lunar_day' => $event->lunar_day,
            'is_waxing' => $event->is_waxing,
            'buddhist_era' => $event->buddhist_era,
            'start_date' => $event->start_date->toDateString(),
            'end_date' => $event->end_date->toDateString(),
            'day_number' => $event->day_number,
            'duration_days' => $event->duration_days,
            'source' => $event->source,
            'source_url' => $event->source_url,
        ];
    }

    private function date(CarbonImmutable|string $date): CarbonImmutable
    {
        if ($date instanceof CarbonImmutable) {
            return $date->setTimezone(config('app.timezone'))->startOfDay();
        }

        return CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
    }
}
