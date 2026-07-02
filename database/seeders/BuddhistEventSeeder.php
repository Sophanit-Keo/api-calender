<?php

namespace Database\Seeders;

use App\Models\BuddhistEvent;
use App\Models\PublicHoliday;
use App\Services\KhmerCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BuddhistEventSeeder extends Seeder
{
    private const COUNTRY_CODE = 'KH';

    private const COUNTRY = 'Cambodia';

    private const TRADITION = 'theravada';

    private const SOURCE = 'Generated from Khmer lunar calendar rules and Cambodia public holiday data';

    private const SOURCE_URL = 'https://www.accesstoinsight.org/ptf/dhamma/sila/uposatha.html';

    private const VASSA_SOURCE = 'Generated from Theravada Vassa and Kathina lunar calendar rules';

    private const VASSA_SOURCE_URL = 'https://www.atamma.org/events-2/upcoming-events/upcoming-events/asalha-puja-buddhist-lent-day/';

    private const KATHINA_SOURCE_URL = 'https://en.wikipedia.org/wiki/Kathina';

    /** @var list<int> */
    private const YEARS = [2020, 2021, 2022, 2023, 2024, 2025, 2026];

    /** @var array<string, array{code:string, name_en:string, type:string}> */
    private const PUBLIC_HOLIDAY_EVENT_MAP = [
        'khmer-new-year' => [
            'code' => 'khmer-new-year',
            'name_en' => 'Khmer New Year Day',
            'type' => 'traditional_festival',
        ],
        'visak-bochea-day' => [
            'code' => 'visak-bochea',
            'name_en' => 'Visak Bochea Day',
            'type' => 'festival',
        ],
        'international-labor-day-visak-bochea-day' => [
            'code' => 'visak-bochea',
            'name_en' => 'Visak Bochea Day',
            'type' => 'festival',
        ],
        'pchum-ben-festival' => [
            'code' => 'pchum-ben',
            'name_en' => 'Pchum Ben Festival',
            'type' => 'festival',
        ],
        'water-festival' => [
            'code' => 'water-festival',
            'name_en' => 'Water Festival',
            'type' => 'traditional_festival',
        ],
    ];

    public function run(): void
    {
        if (Schema::hasTable('public_holidays') && PublicHoliday::query()->where('country_code', self::COUNTRY_CODE)->doesntExist()) {
            $this->call(PublicHolidaySeeder::class);
        }

        $khmerCalendar = app(KhmerCalendarService::class);

        foreach (self::YEARS as $year) {
            $this->seedUposathaDays($khmerCalendar, $year);
            $this->seedMeakBochea($khmerCalendar, $year);
            $this->seedVassaAndKathina($khmerCalendar, $year);
        }

        $this->seedPublicHolidayEvents($khmerCalendar);
    }

    private function seedUposathaDays(KhmerCalendarService $khmerCalendar, int $year): void
    {
        for ($date = $this->startOfYear($year); $date->year === $year; $date = $date->addDay()) {
            $calendar = $khmerCalendar->getKhmerDate($date);
            $nextCalendar = $khmerCalendar->getKhmerDate($date->addDay());
            $uposatha = $this->uposathaFor($calendar, $nextCalendar);

            if ($uposatha === null) {
                continue;
            }

            $this->upsertEvent(
                date: $date,
                calendar: $calendar,
                code: $uposatha['code'],
                nameEn: $uposatha['name_en'],
                type: 'uposatha',
                source: self::SOURCE,
                sourceUrl: self::SOURCE_URL,
            );
        }
    }

    private function seedMeakBochea(KhmerCalendarService $khmerCalendar, int $year): void
    {
        for ($date = $this->startOfYear($year); $date->year === $year; $date = $date->addDay()) {
            $calendar = $khmerCalendar->getKhmerDate($date);

            if (! str_contains((string) ($calendar['holiday'] ?? ''), 'Meak Bochea')) {
                continue;
            }

            $this->upsertEvent(
                date: $date,
                calendar: $calendar,
                code: 'meak-bochea',
                nameEn: 'Meak Bochea Day',
                type: 'festival',
                source: self::SOURCE,
                sourceUrl: 'https://en.wikipedia.org/wiki/Magha_Puja',
            );
        }
    }

    private function seedVassaAndKathina(KhmerCalendarService $khmerCalendar, int $year): void
    {
        $asalhaFullMoon = $this->firstFullMoonBetween($khmerCalendar, "{$year}-07-01", "{$year}-08-15");

        if ($asalhaFullMoon !== null) {
            $this->upsertEvent(
                date: $asalhaFullMoon,
                calendar: $khmerCalendar->getKhmerDate($asalhaFullMoon),
                code: 'asalha-bochea',
                nameEn: 'Asalha Bochea Day',
                type: 'festival',
                source: self::VASSA_SOURCE,
                sourceUrl: self::VASSA_SOURCE_URL,
            );

            $cholVossa = $asalhaFullMoon->addDay();
            $this->upsertEvent(
                date: $cholVossa,
                calendar: $khmerCalendar->getKhmerDate($cholVossa),
                code: 'chol-vossa',
                nameEn: 'Chol Vossa / Beginning of Vassa',
                type: 'vassa',
                source: self::VASSA_SOURCE,
                sourceUrl: self::VASSA_SOURCE_URL,
            );
        }

        $endOfVassa = $this->firstFullMoonBetween($khmerCalendar, "{$year}-10-01", "{$year}-10-31");

        if ($endOfVassa === null) {
            return;
        }

        $this->upsertEvent(
            date: $endOfVassa,
            calendar: $khmerCalendar->getKhmerDate($endOfVassa),
            code: 'chegn-vossa',
            nameEn: 'Chegn Vossa / End of Vassa',
            type: 'vassa',
            source: self::VASSA_SOURCE,
            sourceUrl: self::VASSA_SOURCE_URL,
        );

        $kathinaStart = $endOfVassa->addDay();
        $kathinaEnd = $kathinaStart->addDays(29);

        for ($date = $kathinaStart, $dayNumber = 1; $date->lte($kathinaEnd); $date = $date->addDay(), $dayNumber++) {
            $this->upsertEvent(
                date: $date,
                calendar: $khmerCalendar->getKhmerDate($date),
                code: 'kathina-season',
                nameEn: 'Kathina Season',
                type: 'kathina',
                startDate: $kathinaStart,
                endDate: $kathinaEnd,
                dayNumber: $dayNumber,
                durationDays: 30,
                source: self::VASSA_SOURCE,
                sourceUrl: self::KATHINA_SOURCE_URL,
            );
        }
    }

    private function seedPublicHolidayEvents(KhmerCalendarService $khmerCalendar): void
    {
        PublicHoliday::query()
            ->where('country_code', self::COUNTRY_CODE)
            ->whereIn('code', array_keys(self::PUBLIC_HOLIDAY_EVENT_MAP))
            ->orderBy('date')
            ->get()
            ->each(function (PublicHoliday $holiday) use ($khmerCalendar): void {
                $event = self::PUBLIC_HOLIDAY_EVENT_MAP[$holiday->code];
                $calendar = $khmerCalendar->getKhmerDate($holiday->date->toDateString());

                $this->upsertEvent(
                    date: CarbonImmutable::parse($holiday->date->toDateString(), config('app.timezone')),
                    calendar: $calendar,
                    code: $event['code'],
                    nameEn: $event['name_en'],
                    type: $event['type'],
                    isPublicHoliday: true,
                    startDate: CarbonImmutable::parse($holiday->start_date->toDateString(), config('app.timezone')),
                    endDate: CarbonImmutable::parse($holiday->end_date->toDateString(), config('app.timezone')),
                    dayNumber: $holiday->day_number,
                    durationDays: $holiday->duration_days,
                    source: $holiday->source,
                    sourceUrl: $holiday->source_url,
                );
            });
    }

    private function firstFullMoonBetween(KhmerCalendarService $khmerCalendar, string $from, string $to): ?CarbonImmutable
    {
        $date = CarbonImmutable::parse($from, config('app.timezone'))->startOfDay();
        $end = CarbonImmutable::parse($to, config('app.timezone'))->startOfDay();

        for (; $date->lte($end); $date = $date->addDay()) {
            $calendar = $khmerCalendar->getKhmerDate($date);

            if ($calendar['is_waxing'] && $calendar['lunar_day'] === 15) {
                return $date;
            }
        }

        return null;
    }

    /** @return array{code:string, name_en:string}|null */
    private function uposathaFor(array $calendar, array $nextCalendar): ?array
    {
        if ($calendar['is_waxing'] && $calendar['lunar_day'] === 8) {
            return [
                'code' => 'uposatha-waxing-eighth',
                'name_en' => 'Thngai Sil / Uposatha Day (Waxing 8th)',
            ];
        }

        if ($calendar['is_waxing'] && $calendar['lunar_day'] === 15) {
            return [
                'code' => 'uposatha-full-moon',
                'name_en' => 'Thngai Sil / Uposatha Day (Full Moon)',
            ];
        }

        if (! $calendar['is_waxing'] && $calendar['lunar_day'] === 8) {
            return [
                'code' => 'uposatha-waning-eighth',
                'name_en' => 'Thngai Sil / Uposatha Day (Waning 8th)',
            ];
        }

        if (
            ! $calendar['is_waxing']
            && $nextCalendar['is_waxing']
            && $nextCalendar['lunar_day'] === 1
        ) {
            return [
                'code' => 'uposatha-new-moon',
                'name_en' => 'Thngai Sil / Uposatha Day (New Moon)',
            ];
        }

        return null;
    }

    private function upsertEvent(
        CarbonImmutable $date,
        array $calendar,
        string $code,
        string $nameEn,
        string $type,
        bool $isPublicHoliday = false,
        ?CarbonImmutable $startDate = null,
        ?CarbonImmutable $endDate = null,
        int $dayNumber = 1,
        int $durationDays = 1,
        ?string $source = null,
        ?string $sourceUrl = null,
    ): void {
        $startDate ??= $date;
        $endDate ??= $date;

        BuddhistEvent::query()->updateOrCreate(
            [
                'country_code' => self::COUNTRY_CODE,
                'date' => $date->toDateString(),
                'code' => $code,
                'day_number' => $dayNumber,
            ],
            [
                'country' => self::COUNTRY,
                'name_km' => null,
                'name_en' => $nameEn,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'type' => $type,
                'tradition' => self::TRADITION,
                'is_public_holiday' => $isPublicHoliday,
                'lunar_month_name' => $calendar['lunar_month_name'] ?? null,
                'lunar_day' => $calendar['lunar_day'] ?? null,
                'is_waxing' => $calendar['is_waxing'] ?? null,
                'buddhist_era' => $calendar['buddhist_era'] ?? null,
                'duration_days' => $durationDays,
                'source' => $source,
                'source_url' => $sourceUrl,
            ],
        );
    }

    private function startOfYear(int $year): CarbonImmutable
    {
        return CarbonImmutable::create($year, 1, 1, 0, 0, 0, config('app.timezone'));
    }
}
