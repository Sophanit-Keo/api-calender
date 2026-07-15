<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class KhmerCalendarService
{
    private const FIRST_YEAR = 1900;

    private const LAST_YEAR = 2200;

    /** @var array<int, array{serial_day:int, khmer_month_name:string, length:int, be:int, zodiac:string}>|null */
    private ?array $milestones = null;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $monthCache = [];

    /** @var array<int, string> */
    private array $khDays = [
        'អាទិត្យ',
        'ចន្ទ',
        'អង្គារ',
        'ពុធ',
        'ព្រហស្បតិ៍',
        'សុក្រ',
        'សៅរ៍',
    ];

    /** @var array<int, string> */
    private array $khDaysShort = ['អា', 'ច', 'អ', 'ព', 'ព្រ', 'សុ', 'ស'];

    /** @var array<int, string> */
    private array $enDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /** @var array<int, string> */
    private array $zodiacNames = [
        'ឆ្នាំជូត',
        'ឆ្នាំឆ្លូវ',
        'ឆ្នាំខាល',
        'ឆ្នាំថោះ',
        'ឆ្នាំរោង',
        'ឆ្នាំម្សាញ់',
        'ឆ្នាំមមី',
        'ឆ្នាំមមែ',
        'ឆ្នាំវក',
        'ឆ្នាំរកា',
        'ឆ្នាំច',
        'ឆ្នាំកុរ',
    ];

    /** @var array<int, string> */
    private array $monthNamesNormal = [
        'ចេត្រ',
        'ពិសាខ',
        'ជេស្ឋ',
        'អាសាឍ',
        'ស្រាពណ៍',
        'ភទ្របទ',
        'អស្សុជ',
        'កត្តិក',
        'មិគសិរ',
        'បុស្ស',
        'មាឃ',
        'ផល្គុន',
    ];

    /** @var array<int, string> */
    private array $monthNamesLeap = [
        'ចេត្រ',
        'ពិសាខ',
        'ជេស្ឋ',
        'អាសាឍ ១',
        'អាសាឍ ២',
        'ស្រាពណ៍',
        'ភទ្របទ',
        'អស្សុជ',
        'កត្តិក',
        'មិគសិរ',
        'បុស្ស',
        'មាឃ',
        'ផល្គុន',
    ];

    /** @var array<string, string> */
    private array $khmerNumerals = [
        '0' => '០',
        '1' => '១',
        '2' => '២',
        '3' => '៣',
        '4' => '៤',
        '5' => '៥',
        '6' => '៦',
        '7' => '៧',
        '8' => '៨',
        '9' => '៩',
    ];

    public function getKhmerDate(CarbonImmutable|string $date): array
    {
        $date = $this->date($date);
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;
        $serialDay = $this->getSerialDay($year, $month, $day);
        $dayOfWeekIndex = (($serialDay + 2) % 7 + 7) % 7;
        $milestone = $this->findMilestone($serialDay);

        $offset = $serialDay - $milestone['serial_day'];
        $offsetMod = (($offset % 30) + 30) % 30;
        $isWaxing = $offsetMod < 15;
        $lunarDay = $isWaxing ? $offsetMod + 1 : $offsetMod - 14;
        $lunarDayName = $this->toKhmerNumeral($lunarDay).' '.($isWaxing ? 'កើត' : 'រោច');

        $holiday = $this->builtInHoliday($month, $day, $milestone['khmer_month_name'], $isWaxing, $lunarDay);
        $isAuspicious = in_array($offsetMod, [2, 6, 10, 11, 18, 25], true);

        return [
            'date' => $date->toDateString(),
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'day_of_week' => $this->khDays[$dayOfWeekIndex],
            'day_of_week_en' => $this->enDays[$dayOfWeekIndex],
            'day_of_week_short' => $this->khDaysShort[$dayOfWeekIndex],
            'lunar_day' => $lunarDay,
            'is_waxing' => $isWaxing,
            'lunar_day_name' => $lunarDayName,
            'lunar_month_name' => $milestone['khmer_month_name'],
            'zodiac' => $milestone['zodiac'],
            'buddhist_era' => $milestone['be'],
            'moon_phase' => $this->moonEmoji($offsetMod),
            'holiday' => $holiday,
            'is_auspicious' => $isAuspicious,
            'auspicious_type' => $isAuspicious ? $this->auspiciousType($lunarDay) : null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function getGregorianMonthDays(int $year, int $month): array
    {
        $key = $year.'-'.$month;

        if (array_key_exists($key, $this->monthCache)) {
            return $this->monthCache[$key];
        }

        $firstDay = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'));
        $days = [];

        for ($day = 1; $day <= $firstDay->daysInMonth; $day++) {
            $days[] = $this->getKhmerDate($firstDay->setDay($day));
        }

        return $this->monthCache[$key] = $days;
    }

    public function getSerialDay(int $year, int $month, int $day): int
    {
        if ($month <= 2) {
            $year--;
            $month += 12;
        }

        return (365 * $year)
            + intdiv($year, 4)
            - intdiv($year, 100)
            + intdiv($year, 400)
            + intdiv(153 * ($month - 3) + 2, 5)
            + $day;
    }

    public function toKhmerNumeral(int $number): string
    {
        return strtr((string) $number, $this->khmerNumerals);
    }

    /** @return array{serial_day:int, khmer_month_name:string, length:int, be:int, zodiac:string} */
    private function findMilestone(int $serialDay): array
    {
        $milestones = $this->milestones();
        $low = 0;
        $high = count($milestones) - 1;

        while ($low < $high) {
            $mid = intdiv($low + $high + 1, 2);

            if ($milestones[$mid]['serial_day'] <= $serialDay) {
                $low = $mid;
            } else {
                $high = $mid - 1;
            }
        }

        return $milestones[$low]['serial_day'] <= $serialDay
            ? $milestones[$low]
            : $this->fallbackMilestone();
    }

    /** @return array<int, array{serial_day:int, khmer_month_name:string, length:int, be:int, zodiac:string}> */
    private function milestones(): array
    {
        return $this->milestones ??= $this->buildMilestones(self::FIRST_YEAR, self::LAST_YEAR);
    }

    /** @return array{serial_day:int, khmer_month_name:string, length:int, be:int, zodiac:string} */
    private function fallbackMilestone(): array
    {
        return [
            'serial_day' => $this->getSerialDay(2026, 5, 11),
            'khmer_month_name' => 'ពិសាខ',
            'length' => 30,
            'be' => 2570,
            'zodiac' => $this->zodiacNames[6],
        ];
    }

    /** @return array<int, array{serial_day:int, khmer_month_name:string, length:int, be:int, zodiac:string}> */
    private function buildMilestones(int $firstGregorianYear, int $lastGregorianYear): array
    {
        $kStart = (int) round(($firstGregorianYear - 2001) * 12.37) - 2;
        $kEnd = (int) round(($lastGregorianYear - 1999) * 12.37) + 2;
        $newMoons = [];

        for ($k = $kStart; $k <= $kEnd; $k++) {
            [$year, $month, $day] = $this->jdeToGregorian($this->newMoonJde((float) $k));

            if ($year >= $firstGregorianYear - 1 && $year <= $lastGregorianYear + 1) {
                $newMoons[] = [
                    'year' => $year,
                    'month' => $month,
                    'day' => $day,
                    'serial_day' => $this->getSerialDay($year, $month, $day),
                ];
            }
        }

        usort($newMoons, fn (array $a, array $b): int => $a['serial_day'] <=> $b['serial_day']);

        $chaitraIndexes = [];
        foreach ($newMoons as $index => $newMoon) {
            if (($newMoon['month'] === 3 && $newMoon['day'] >= 27) || ($newMoon['month'] === 4 && $newMoon['day'] <= 26)) {
                $chaitraIndexes[] = $index;
            }
        }

        $result = [];
        for ($index = 0; $index < count($chaitraIndexes) - 1; $index++) {
            $startIndex = $chaitraIndexes[$index];
            $endIndex = $chaitraIndexes[$index + 1];
            $monthsInYear = $endIndex - $startIndex;
            $names = $monthsInYear === 13 ? $this->monthNamesLeap : $this->monthNamesNormal;
            $be = $newMoons[$startIndex]['year'] + 544;
            $zodiac = $this->zodiacNames[(($be % 12) + 4 + 12) % 12];

            for ($position = 0; $position < $monthsInYear; $position++) {
                $newMoon = $newMoons[$startIndex + $position];
                $nextMoon = $newMoons[$startIndex + $position + 1];
                $result[] = [
                    'serial_day' => $newMoon['serial_day'],
                    'khmer_month_name' => $names[$position] ?? 'ចេត្រ',
                    'length' => $nextMoon['serial_day'] - $newMoon['serial_day'],
                    'be' => $be,
                    'zodiac' => $zodiac,
                ];
            }
        }

        usort($result, fn (array $a, array $b): int => $a['serial_day'] <=> $b['serial_day']);

        return $result;
    }

    private function newMoonJde(float $k): float
    {
        $t = $k / 1236.85;
        $t2 = $t * $t;
        $t3 = $t2 * $t;
        $t4 = $t3 * $t;

        $jde = 2451550.09766 + 29.530588861 * $k + 0.00015437 * $t2 - 0.000000150 * $t3 + 0.00000000073 * $t4;
        $e = 1.0 - 0.002516 * $t - 0.0000074 * $t2;
        $m = deg2rad(2.5534 + 29.10535670 * $k - 0.0000014 * $t2 - 0.00000011 * $t3);
        $mp = deg2rad(201.5643 + 385.81693528 * $k + 0.0107582 * $t2 + 0.00001238 * $t3 - 0.000000058 * $t4);
        $f = deg2rad(160.7108 + 390.67050284 * $k - 0.0016118 * $t2 - 0.00000227 * $t3 + 0.000000011 * $t4);
        $om = deg2rad(124.7746 - 1.56375588 * $k + 0.0020672 * $t2 + 0.00000215 * $t3);

        return $jde
            - 0.40720 * sin($mp)
            + 0.17241 * $e * sin($m)
            + 0.01608 * sin(2.0 * $mp)
            + 0.01039 * sin(2.0 * $f)
            + 0.00739 * $e * sin($mp - $m)
            - 0.00514 * $e * sin($mp + $m)
            + 0.00208 * $e * $e * sin(2.0 * $m)
            - 0.00111 * sin($mp - 2.0 * $f)
            - 0.00057 * sin($mp + 2.0 * $f)
            + 0.00056 * $e * sin(2.0 * $mp + $m)
            - 0.00042 * sin(3.0 * $mp)
            + 0.00042 * $e * sin($m + 2.0 * $f)
            + 0.00038 * $e * sin($m - 2.0 * $f)
            - 0.00024 * $e * sin(2.0 * $mp - $m)
            - 0.00017 * sin($om);
    }

    /** @return array{0:int, 1:int, 2:int} */
    private function jdeToGregorian(float $jde): array
    {
        $jdLocal = $jde + 7.0 / 24.0;
        $z = (int) floor($jdLocal + 0.5);
        $a = $z;

        if ($z >= 2299161) {
            $alpha = (int) floor(($z - 1867216.25) / 36524.25);
            $a = $z + 1 + $alpha - intdiv($alpha, 4);
        }

        $b = $a + 1524;
        $c = (int) floor(($b - 122.1) / 365.25);
        $d = (int) floor(365.25 * $c);
        $e = (int) floor(($b - $d) / 30.6001);
        $day = (int) ($b - $d - floor(30.6001 * $e));
        $month = $e < 14 ? $e - 1 : $e - 13;
        $year = $month > 2 ? $c - 4716 : $c - 4715;

        return [$year, $month, $day];
    }

    private function moonEmoji(int $offsetMod): string
    {
        return match (true) {
            $offsetMod === 0 => '🌑',
            $offsetMod >= 1 && $offsetMod <= 6 => '🌒',
            $offsetMod === 7 => '🌓',
            $offsetMod >= 8 && $offsetMod <= 13 => '🌔',
            $offsetMod === 14 => '🌕',
            $offsetMod >= 15 && $offsetMod <= 21 => '🌖',
            $offsetMod === 22 => '🌗',
            default => '🌘',
        };
    }

    private function builtInHoliday(int $month, int $day, string $khmerMonth, bool $isWaxing, int $lunarDay): ?string
    {
        if ($month === 4 && $day >= 14 && $day <= 16) {
            return 'ចូលឆ្នាំថ្មីប្រពៃណីជាតិ';
        }

        if ($khmerMonth === 'មាឃ' && $isWaxing && $lunarDay === 15) {
            return 'បុណ្យមាឃបូជា (Meak Bochea)';
        }

        if ($khmerMonth === 'ពិសាខ' && $isWaxing && $lunarDay === 15) {
            return 'បុណ្យវិសាខបូជា (Visak Bochea)';
        }

        if ($khmerMonth === 'ភទ្របទ' && ! $isWaxing && $lunarDay === 15) {
            return 'បុណ្យភ្ជុំបិណ្ឌ (Pchum Ben)';
        }

        if ($khmerMonth === 'កត្តិក' && $isWaxing && $lunarDay === 15) {
            return 'បុណ្យអុំទូក (Water Festival)';
        }

        return match (true) {
            $month === 11 && $day === 9 => 'ទិវាបុណ្យឯករាជ្យជាតិ',
            $month === 1 && $day === 7 => 'ទិវាជ័យជម្នះលើរបបប្រល័យពូជសាសន៍',
            $month === 5 && $day === 1 => 'ទិវាពលកម្មអន្តរជាតិ',
            $month === 6 && $day === 18 => 'ព្រះរាជពិធីបុណ្យចម្រើនព្រះជន្ម សម្តេចម៉ែ',
            $month === 9 && $day === 24 => 'ទិវារដ្ឋធម្មនុញ្ញ',
            $month === 10 && $day === 15 => 'ទិវាគោរពព្រះវិញ្ញាណក្ខន្ធ ព្រះបរមរតនកោដ្ឋ',
            $month === 10 && $day === 29 => 'ព្រះរាជពិធីគ្រងព្រះបរមរាជសម្បត្តិ ព្រះមហាក្សត្រ',
            default => null,
        };
    }

    private function auspiciousType(int $lunarDay): string
    {
        return match ($lunarDay % 4) {
            0 => 'ពិធីមង្គលការ (Wedding)',
            1 => 'ឡើងផ្ទះថ្មី (Housewarming)',
            2 => 'បើកអាជីវកម្ម (Business)',
            default => 'ធ្វើដំណើរស្វែងរកលាភ (Travel)',
        };
    }

    private function date(CarbonImmutable|string $date): CarbonImmutable
    {
        if ($date instanceof CarbonImmutable) {
            return $date->setTimezone(config('app.timezone'))->startOfDay();
        }

        return CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
    }
}
