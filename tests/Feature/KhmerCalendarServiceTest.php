<?php

use App\Services\KhmerCalendarService;

it('converts known Gregorian dates to Khmer calendar data', function (): void {
    $service = app(KhmerCalendarService::class);

    expect($service->getKhmerDate('2026-05-25')['day_of_week_en'])->toBe('Monday')
        ->and($service->getKhmerDate('2024-02-29')['date'])->toBe('2024-02-29')
        ->and($service->getKhmerDate('2026-04-14')['holiday'])->toContain('ចូលឆ្នាំ')
        ->and($service->getKhmerDate('2026-11-09')['holiday'])->toContain('ឯករាជ្យ');
});

it('keeps serial days continuous across month and year boundaries', function (): void {
    $service = app(KhmerCalendarService::class);

    expect($service->getSerialDay(2026, 6, 1) - $service->getSerialDay(2026, 5, 31))->toBe(1)
        ->and($service->getSerialDay(2026, 1, 1) - $service->getSerialDay(2025, 12, 31))->toBe(1)
        ->and($service->getSerialDay(2024, 3, 1) - $service->getSerialDay(2024, 2, 29))->toBe(1);
});

it('detects split Asadha months in the 2027 Khmer leap lunar year', function (): void {
    $service = app(KhmerCalendarService::class);
    $monthNames = collect(range(1, 12))
        ->flatMap(fn (int $month): array => $service->getGregorianMonthDays(2027, $month))
        ->pluck('lunar_month_name')
        ->unique()
        ->values();

    expect($monthNames)->toContain('អាសាឍ ១')
        ->and($monthNames)->toContain('អាសាឍ ២');
});
