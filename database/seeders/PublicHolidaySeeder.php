<?php

namespace Database\Seeders;

use App\Models\PublicHoliday;
use Illuminate\Database\Seeder;

class PublicHolidaySeeder extends Seeder
{
    private const COUNTRY_CODE = 'KH';

    private const COUNTRY = 'Cambodia';

    /** @var array<int, array{source:string, source_url:string}> */
    private const SOURCES = [
        2020 => [
            'source' => 'Office Holidays Cambodia national holidays 2020 / IBC Cambodia public holidays 2020',
            'source_url' => 'https://www.officeholidays.com/countries/cambodia/2020',
        ],
        2021 => [
            'source' => 'International Business Chamber of Cambodia public holidays 2021',
            'source_url' => 'https://ibccambodia.com/wp-content/uploads/2020/08/List-of-Public-Holidays-in-2021.pdf',
        ],
        2022 => [
            'source' => 'Cambodia Post Bank public holidays 2022',
            'source_url' => 'https://www.cambodiapostbank.com.kh/our-public-holidays/',
        ],
        2023 => [
            'source' => 'Cambodia Post Bank public holidays 2023',
            'source_url' => 'https://www.cambodiapostbank.com.kh/our-public-holidays/',
        ],
        2024 => [
            'source' => 'Cambodia Post Bank public holidays 2024',
            'source_url' => 'https://www.cambodiapostbank.com.kh/our-public-holidays/',
        ],
        2025 => [
            'source' => 'Cambodia Post Bank public holidays 2025',
            'source_url' => 'https://www.cambodiapostbank.com.kh/our-public-holidays/',
        ],
        2026 => [
            'source' => 'Cambodia Post Bank public holidays 2026 / NBC official holidays 2026',
            'source_url' => 'https://www.cambodiapostbank.com.kh/our-public-holidays/',
        ],
    ];

    /** @var list<array{dates:list<string>, code:string, name_en:string}> */
    private const HOLIDAYS = [
        ['dates' => ['2020-01-01'], 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day'],
        ['dates' => ['2020-01-07'], 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory over Genocide Day'],
        ['dates' => ['2020-03-08'], 'code' => 'international-womens-day', 'name_en' => "International Women's Day"],
        ['dates' => ['2020-05-01'], 'code' => 'international-labor-day', 'name_en' => 'International Labor Day'],
        ['dates' => ['2020-05-06'], 'code' => 'visak-bochea-day', 'name_en' => 'Visak Bochea Day'],
        ['dates' => ['2020-05-10'], 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony'],
        ['dates' => ['2020-05-14'], 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday"],
        ['dates' => ['2020-06-18'], 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday"],
        ['dates' => ['2020-08-17', '2020-08-18', '2020-08-19', '2020-08-20', '2020-08-21'], 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Holiday'],
        ['dates' => ['2020-09-16', '2020-09-17', '2020-09-18'], 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival'],
        ['dates' => ['2020-09-24'], 'code' => 'constitution-day', 'name_en' => 'Constitution Day'],
        ['dates' => ['2020-10-15'], 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk'],
        ['dates' => ['2020-10-29'], 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day"],
        ['dates' => ['2020-10-30', '2020-10-31', '2020-11-01'], 'code' => 'water-festival', 'name_en' => 'Water Festival'],
        ['dates' => ['2020-11-09'], 'code' => 'national-independence-day', 'name_en' => 'National Independence Day'],

        ['dates' => ['2021-01-01'], 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day'],
        ['dates' => ['2021-01-07'], 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory over Genocide Day'],
        ['dates' => ['2021-03-08'], 'code' => 'international-womens-day', 'name_en' => "International Women's Day"],
        ['dates' => ['2021-04-14', '2021-04-15', '2021-04-16'], 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day'],
        ['dates' => ['2021-04-26'], 'code' => 'visak-bochea-day', 'name_en' => 'Visak Bochea Day'],
        ['dates' => ['2021-04-30'], 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony'],
        ['dates' => ['2021-05-01'], 'code' => 'international-labor-day', 'name_en' => 'International Labor Day'],
        ['dates' => ['2021-05-14'], 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday"],
        ['dates' => ['2021-06-18'], 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday"],
        ['dates' => ['2021-09-24'], 'code' => 'constitution-day', 'name_en' => 'Constitution Day'],
        ['dates' => ['2021-10-05', '2021-10-06', '2021-10-07'], 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival'],
        ['dates' => ['2021-10-15'], 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk'],
        ['dates' => ['2021-10-29'], 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day"],
        ['dates' => ['2021-11-09'], 'code' => 'national-independence-day', 'name_en' => 'National Independence Day'],
        ['dates' => ['2021-11-18', '2021-11-19', '2021-11-20'], 'code' => 'water-festival', 'name_en' => 'Water Festival'],

        ['dates' => ['2022-01-01'], 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day'],
        ['dates' => ['2022-01-07'], 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory over Genocide Day'],
        ['dates' => ['2022-03-08'], 'code' => 'international-womens-day', 'name_en' => "International Women's Day"],
        ['dates' => ['2022-04-14', '2022-04-15', '2022-04-16'], 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day'],
        ['dates' => ['2022-05-01'], 'code' => 'international-labor-day', 'name_en' => 'International Labor Day'],
        ['dates' => ['2022-05-14'], 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday"],
        ['dates' => ['2022-05-15'], 'code' => 'visak-bochea-day', 'name_en' => 'Visak Bochea Day'],
        ['dates' => ['2022-05-19'], 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony'],
        ['dates' => ['2022-06-18'], 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday"],
        ['dates' => ['2022-09-24'], 'code' => 'constitution-day', 'name_en' => 'Constitution Day'],
        ['dates' => ['2022-09-24', '2022-09-25', '2022-09-26'], 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival'],
        ['dates' => ['2022-10-15'], 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk'],
        ['dates' => ['2022-10-29'], 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day"],
        ['dates' => ['2022-11-07', '2022-11-08', '2022-11-09'], 'code' => 'water-festival', 'name_en' => 'Water Festival'],
        ['dates' => ['2022-11-09'], 'code' => 'national-independence-day', 'name_en' => 'National Independence Day'],

        ['dates' => ['2023-01-01'], 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day'],
        ['dates' => ['2023-01-07'], 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory over Genocide Day'],
        ['dates' => ['2023-03-08'], 'code' => 'international-womens-day', 'name_en' => "International Women's Day"],
        ['dates' => ['2023-04-14', '2023-04-15', '2023-04-16'], 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day'],
        ['dates' => ['2023-05-01'], 'code' => 'international-labor-day', 'name_en' => 'International Labor Day'],
        ['dates' => ['2023-05-04'], 'code' => 'visak-bochea-day', 'name_en' => 'Visak Bochea Day'],
        ['dates' => ['2023-05-08'], 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony'],
        ['dates' => ['2023-05-14'], 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday"],
        ['dates' => ['2023-06-18'], 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday"],
        ['dates' => ['2023-09-24'], 'code' => 'constitution-day', 'name_en' => 'Constitution Day'],
        ['dates' => ['2023-10-13', '2023-10-14', '2023-10-15'], 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival'],
        ['dates' => ['2023-10-15'], 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk'],
        ['dates' => ['2023-10-29'], 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day"],
        ['dates' => ['2023-11-09'], 'code' => 'national-independence-day', 'name_en' => 'National Independence Day'],
        ['dates' => ['2023-11-26', '2023-11-27', '2023-11-28'], 'code' => 'water-festival', 'name_en' => 'Water Festival'],

        ['dates' => ['2024-01-01'], 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day'],
        ['dates' => ['2024-01-07'], 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory over Genocide Day'],
        ['dates' => ['2024-03-08'], 'code' => 'international-womens-day', 'name_en' => "International Women's Day"],
        ['dates' => ['2024-04-13', '2024-04-14', '2024-04-15', '2024-04-16'], 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day'],
        ['dates' => ['2024-05-01'], 'code' => 'international-labor-day', 'name_en' => 'International Labor Day'],
        ['dates' => ['2024-05-14'], 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday"],
        ['dates' => ['2024-05-22'], 'code' => 'visak-bochea-day', 'name_en' => 'Visak Bochea Day'],
        ['dates' => ['2024-05-26'], 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony'],
        ['dates' => ['2024-06-18'], 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday"],
        ['dates' => ['2024-09-24'], 'code' => 'constitution-day', 'name_en' => 'Constitution Day'],
        ['dates' => ['2024-10-01', '2024-10-02', '2024-10-03'], 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival'],
        ['dates' => ['2024-10-15'], 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk'],
        ['dates' => ['2024-10-29'], 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day"],
        ['dates' => ['2024-11-09'], 'code' => 'national-independence-day', 'name_en' => 'National Independence Day'],
        ['dates' => ['2024-11-14', '2024-11-15', '2024-11-16'], 'code' => 'water-festival', 'name_en' => 'Water Festival'],
        ['dates' => ['2024-12-29'], 'code' => 'peace-day-in-cambodia', 'name_en' => 'Peace Day in Cambodia'],

        ['dates' => ['2025-01-01'], 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day'],
        ['dates' => ['2025-01-07'], 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory over Genocide Day'],
        ['dates' => ['2025-03-08'], 'code' => 'international-womens-day', 'name_en' => "International Women's Day"],
        ['dates' => ['2025-04-14', '2025-04-15', '2025-04-16'], 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day'],
        ['dates' => ['2025-05-01'], 'code' => 'international-labor-day', 'name_en' => 'International Labor Day'],
        ['dates' => ['2025-05-11'], 'code' => 'visak-bochea-day', 'name_en' => 'Visak Bochea Day'],
        ['dates' => ['2025-05-14'], 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday"],
        ['dates' => ['2025-05-15'], 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony'],
        ['dates' => ['2025-06-18'], 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday"],
        ['dates' => ['2025-09-21', '2025-09-22', '2025-09-23'], 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival'],
        ['dates' => ['2025-09-24'], 'code' => 'constitution-day', 'name_en' => 'Constitution Day'],
        ['dates' => ['2025-10-15'], 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk'],
        ['dates' => ['2025-10-29'], 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day"],
        ['dates' => ['2025-11-04', '2025-11-05', '2025-11-06'], 'code' => 'water-festival', 'name_en' => 'Water Festival'],
        ['dates' => ['2025-11-09'], 'code' => 'national-independence-day', 'name_en' => 'National Independence Day'],
        ['dates' => ['2025-12-29'], 'code' => 'peace-day-in-cambodia', 'name_en' => 'Peace Day in Cambodia'],

        ['dates' => ['2026-01-01'], 'code' => 'international-new-year-day', 'name_en' => 'International New Year Day'],
        ['dates' => ['2026-01-07'], 'code' => 'victory-over-genocide-day', 'name_en' => 'Victory over Genocide Day'],
        ['dates' => ['2026-03-08'], 'code' => 'international-womens-day', 'name_en' => "International Women's Day"],
        ['dates' => ['2026-04-14', '2026-04-15', '2026-04-16'], 'code' => 'khmer-new-year', 'name_en' => 'Khmer New Year Day'],
        ['dates' => ['2026-05-01'], 'code' => 'international-labor-day-visak-bochea-day', 'name_en' => 'International Labor Day and Visak Bochea Day'],
        ['dates' => ['2026-05-05'], 'code' => 'royal-plowing-ceremony', 'name_en' => 'Royal Plowing Ceremony'],
        ['dates' => ['2026-05-14'], 'code' => 'king-norodom-sihamoni-birthday', 'name_en' => "King Norodom Sihamoni's Birthday"],
        ['dates' => ['2026-06-18'], 'code' => 'queen-mother-birthday', 'name_en' => "Queen Mother Norodom Monineath Sihanouk's Birthday"],
        ['dates' => ['2026-09-24'], 'code' => 'constitution-day', 'name_en' => 'Constitution Day'],
        ['dates' => ['2026-10-10', '2026-10-11', '2026-10-12'], 'code' => 'pchum-ben-festival', 'name_en' => 'Pchum Ben Festival'],
        ['dates' => ['2026-10-15'], 'code' => 'late-king-father-commemoration-day', 'name_en' => 'Commemoration Day of Late King Father Norodom Sihanouk'],
        ['dates' => ['2026-10-29'], 'code' => 'king-norodom-sihamoni-coronation-day', 'name_en' => "King Norodom Sihamoni's Coronation Day"],
        ['dates' => ['2026-11-09'], 'code' => 'national-independence-day', 'name_en' => 'National Independence Day'],
        ['dates' => ['2026-11-23', '2026-11-24', '2026-11-25'], 'code' => 'water-festival', 'name_en' => 'Water Festival'],
        ['dates' => ['2026-12-29'], 'code' => 'peace-day-in-cambodia', 'name_en' => 'Peace Day in Cambodia'],
    ];

    public function run(): void
    {
        foreach (self::HOLIDAYS as $holiday) {
            $durationDays = count($holiday['dates']);
            $startDate = $holiday['dates'][0];
            $endDate = $holiday['dates'][$durationDays - 1];

            foreach ($holiday['dates'] as $index => $date) {
                $year = (int) substr($date, 0, 4);
                $source = self::SOURCES[$year];

                PublicHoliday::query()->updateOrCreate(
                    [
                        'country_code' => self::COUNTRY_CODE,
                        'date' => $date,
                        'code' => $holiday['code'],
                        'day_number' => $index + 1,
                    ],
                    [
                        'country' => self::COUNTRY,
                        'name_km' => null,
                        'name_en' => $holiday['name_en'],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'type' => 'public_national',
                        'is_public' => true,
                        'is_national' => true,
                        'duration_days' => $durationDays,
                        'source' => $source['source'],
                        'source_url' => $source['source_url'],
                    ],
                );
            }
        }
    }
}
