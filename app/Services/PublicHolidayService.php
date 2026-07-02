<?php

namespace App\Services;

use App\Models\PublicHoliday;
use Carbon\CarbonImmutable;

class PublicHolidayService
{
    public const COUNTRY_CODE = 'KH';

    public const COUNTRY_NAME = 'Cambodia';

    /** @return list<int> */
    public function supportedYears(): array
    {
        return PublicHoliday::query()
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
        $firstHoliday = PublicHoliday::query()
            ->where('country_code', self::COUNTRY_CODE)
            ->orderBy('date')
            ->first(['source', 'source_url']);

        return [
            'country_code' => self::COUNTRY_CODE,
            'country' => self::COUNTRY_NAME,
            'source' => $firstHoliday?->source,
            'source_url' => $firstHoliday?->source_url,
            'supported_years' => $this->supportedYears(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function holidaysForYear(int $year): array
    {
        return $this->holidaysForRange("{$year}-01-01", "{$year}-12-31");
    }

    /** @return list<array<string, mixed>> */
    public function holidaysForDate(CarbonImmutable|string $date): array
    {
        $date = $this->date($date)->toDateString();

        return PublicHoliday::query()
            ->where('country_code', self::COUNTRY_CODE)
            ->where('date', $date)
            ->orderBy('code')
            ->orderBy('day_number')
            ->get()
            ->map(fn (PublicHoliday $holiday): array => $this->formatHoliday($holiday))
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function holidaysForRange(CarbonImmutable|string $from, CarbonImmutable|string $to): array
    {
        $from = $this->date($from);
        $to = $this->date($to);

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return PublicHoliday::query()
            ->where('country_code', self::COUNTRY_CODE)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->orderBy('code')
            ->orderBy('day_number')
            ->get()
            ->map(fn (PublicHoliday $holiday): array => $this->formatHoliday($holiday))
            ->values()
            ->all();
    }

    private function formatHoliday(PublicHoliday $holiday): array
    {
        return [
            'id' => sprintf(
                '%s-%d-%s-%d',
                strtolower($holiday->country_code),
                $holiday->date->year,
                $holiday->code,
                $holiday->day_number,
            ),
            'country_code' => $holiday->country_code,
            'country' => $holiday->country,
            'date' => $holiday->date->toDateString(),
            'name_km' => $holiday->name_km,
            'name_en' => $holiday->name_en,
            'type' => $holiday->type,
            'is_public' => $holiday->is_public,
            'is_national' => $holiday->is_national,
            'start_date' => $holiday->start_date->toDateString(),
            'end_date' => $holiday->end_date->toDateString(),
            'day_number' => $holiday->day_number,
            'duration_days' => $holiday->duration_days,
            'source' => $holiday->source,
            'source_url' => $holiday->source_url,
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
