<?php

namespace Database\Seeders;

use App\Models\PublicHoliday;
use Illuminate\Database\Seeder;

class PublicHolidaySeeder extends Seeder
{
    private const COUNTRY_CODE = 'KH';

    private const COUNTRY = 'Cambodia';

    private const SOURCE = 'Royal Government of Cambodia public holidays for 2026 / MLVT Prakas No. 216/25';

    private const SOURCE_URL = 'https://www.kbprasacbank.com.kh/en/media/public-holiday/';

    /** @var list<array{date:string, code:string, name_en:string, start_date:string, end_date:string, day_number:int, duration_days:int}> */
    private const CAMBODIA_2026 = [
        ['date' => '2026-01-01', 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day', 'start_date' => '2026-01-01', 'end_date' => '2026-01-01', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-01-07', 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory Day over Genocide', 'start_date' => '2026-01-07', 'end_date' => '2026-01-07', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-03-08', 'code' => 'international-womens-day', 'name_en' => "International Women's Day", 'start_date' => '2026-03-08', 'end_date' => '2026-03-08', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-04-14', 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day', 'start_date' => '2026-04-14', 'end_date' => '2026-04-16', 'day_number' => 1, 'duration_days' => 3],
        ['date' => '2026-04-15', 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day', 'start_date' => '2026-04-14', 'end_date' => '2026-04-16', 'day_number' => 2, 'duration_days' => 3],
        ['date' => '2026-04-16', 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day', 'start_date' => '2026-04-14', 'end_date' => '2026-04-16', 'day_number' => 3, 'duration_days' => 3],
        ['date' => '2026-05-01', 'code' => 'international-labor-day-visak-bochea-day', 'name_en' => 'International Labor Day and Visak Bochea Day', 'start_date' => '2026-05-01', 'end_date' => '2026-05-01', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-05-05', 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony', 'start_date' => '2026-05-05', 'end_date' => '2026-05-05', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-05-14', 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday", 'start_date' => '2026-05-14', 'end_date' => '2026-05-14', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-06-18', 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday", 'start_date' => '2026-06-18', 'end_date' => '2026-06-18', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-09-24', 'code' => 'constitution-day', 'name_en' => 'Constitution Day', 'start_date' => '2026-09-24', 'end_date' => '2026-09-24', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-10-10', 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival', 'start_date' => '2026-10-10', 'end_date' => '2026-10-12', 'day_number' => 1, 'duration_days' => 3],
        ['date' => '2026-10-11', 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival', 'start_date' => '2026-10-10', 'end_date' => '2026-10-12', 'day_number' => 2, 'duration_days' => 3],
        ['date' => '2026-10-12', 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival', 'start_date' => '2026-10-10', 'end_date' => '2026-10-12', 'day_number' => 3, 'duration_days' => 3],
        ['date' => '2026-10-15', 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk', 'start_date' => '2026-10-15', 'end_date' => '2026-10-15', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-10-29', 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day", 'start_date' => '2026-10-29', 'end_date' => '2026-10-29', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-11-09', 'code' => 'national-independence-day', 'name_en' => 'National Independence Day', 'start_date' => '2026-11-09', 'end_date' => '2026-11-09', 'day_number' => 1, 'duration_days' => 1],
        ['date' => '2026-11-23', 'code' => 'water-festival', 'name_en' => 'Water Festival', 'start_date' => '2026-11-23', 'end_date' => '2026-11-25', 'day_number' => 1, 'duration_days' => 3],
        ['date' => '2026-11-24', 'code' => 'water-festival', 'name_en' => 'Water Festival', 'start_date' => '2026-11-23', 'end_date' => '2026-11-25', 'day_number' => 2, 'duration_days' => 3],
        ['date' => '2026-11-25', 'code' => 'water-festival', 'name_en' => 'Water Festival', 'start_date' => '2026-11-23', 'end_date' => '2026-11-25', 'day_number' => 3, 'duration_days' => 3],
        ['date' => '2026-12-29', 'code' => 'peace-day-in-cambodia', 'name_en' => 'Peace Day in Cambodia', 'start_date' => '2026-12-29', 'end_date' => '2026-12-29', 'day_number' => 1, 'duration_days' => 1],
    ];

    public function run(): void
    {
        foreach (self::CAMBODIA_2026 as $holiday) {
            PublicHoliday::query()->updateOrCreate(
                [
                    'country_code' => self::COUNTRY_CODE,
                    'date' => $holiday['date'],
                    'code' => $holiday['code'],
                    'day_number' => $holiday['day_number'],
                ],
                [
                    'country' => self::COUNTRY,
                    'name_km' => null,
                    'name_en' => $holiday['name_en'],
                    'start_date' => $holiday['start_date'],
                    'end_date' => $holiday['end_date'],
                    'type' => 'public_national',
                    'is_public' => true,
                    'is_national' => true,
                    'duration_days' => $holiday['duration_days'],
                    'source' => self::SOURCE,
                    'source_url' => self::SOURCE_URL,
                ],
            );
        }
    }
}
