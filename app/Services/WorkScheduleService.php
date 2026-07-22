<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkScheduleCycle;
use App\Models\WorkScheduleDay;
use App\Models\WorkScheduleSetting;
use App\Models\WorkShiftTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkScheduleService
{
    public const CYCLE_SLOTS = 31;

    private const ANCHOR_DAY = 26;

    public function settingsPayload(int $userId): array
    {
        $setting = $this->setting($userId);
        $this->ensureDefaultShiftTemplates($userId);

        return [
            'settings' => [
                'id' => $setting->id,
                'system_type' => $setting->system_type,
                'remind' => $setting->remind,
                'reminder_minutes_before' => $setting->reminder_minutes_before,
            ],
            'shift_templates' => WorkShiftTemplate::query()
                ->where('user_id', $userId)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(fn (WorkShiftTemplate $shift): array => $this->formatShiftTemplate($shift))
                ->values(),
        ];
    }

    public function updateSettings(int $userId, array $data): array
    {
        $setting = $this->setting($userId);

        $setting->fill([
            'system_type' => $data['system_type'] ?? $setting->system_type,
            'remind' => $data['remind'] ?? $setting->remind,
            'reminder_minutes_before' => $data['reminder_minutes_before'] ?? $setting->reminder_minutes_before,
        ])->save();

        foreach ($data['shift_templates'] ?? [] as $index => $shiftData) {
            WorkShiftTemplate::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'code' => $shiftData['code'],
                ],
                [
                    'name' => $shiftData['name'],
                    'start_time' => $this->normalizeTime($shiftData['start_time']),
                    'end_time' => $this->normalizeTime($shiftData['end_time']),
                    'sort_order' => $shiftData['sort_order'] ?? $index,
                ],
            );
        }

        return $this->settingsPayload($userId);
    }

    public function cycleStartFor(CarbonImmutable|string $date): CarbonImmutable
    {
        $date = $this->date($date);
        $cycleStart = $date->setDay(self::ANCHOR_DAY);

        return $date->day >= self::ANCHOR_DAY
            ? $cycleStart
            : $cycleStart->subMonthNoOverflow();
    }

    public function cycleEndForStart(CarbonImmutable|string $cycleStart): CarbonImmutable
    {
        return $this->date($cycleStart)->addMonthNoOverflow()->subDay();
    }

    public function cyclePayload(int $userId, CarbonImmutable|string $cycleStartDate): array
    {
        $cycleStart = $this->parseCycleStartDate($cycleStartDate);
        $cycleEnd = $this->cycleEndForStart($cycleStart);
        $cycle = WorkScheduleCycle::query()
            ->with(['days.shiftTemplate'])
            ->where('user_id', $userId)
            ->whereDate('cycle_start_date', $cycleStart->toDateString())
            ->first();

        $assignments = array_fill(0, self::CYCLE_SLOTS, null);
        $days = [];

        if ($cycle !== null) {
            foreach ($cycle->days as $day) {
                $assignments[$day->day_offset] = $day->shiftTemplate?->code;
            }
        }

        for ($date = $cycleStart, $offset = 0; $date->lte($cycleEnd); $date = $date->addDay(), $offset++) {
            $shift = $cycle?->days->firstWhere('day_offset', $offset)?->shiftTemplate;
            $days[] = [
                'date' => $date->toDateString(),
                'day_offset' => $offset,
                'shift_template' => $shift ? $this->formatShiftTemplate($shift) : null,
            ];
        }

        return [
            'cycle_start_date' => $cycleStart->toDateString(),
            'cycle_end_date' => $cycleEnd->toDateString(),
            'assignments' => $assignments,
            'days' => $days,
        ];
    }

    public function saveCycle(int $userId, CarbonImmutable|string $cycleStartDate, array $assignments): array
    {
        $this->ensureDefaultShiftTemplates($userId);

        $cycleStart = $this->parseCycleStartDate($cycleStartDate);
        $cycleEnd = $this->cycleEndForStart($cycleStart);
        $assignments = array_pad(array_slice($assignments, 0, self::CYCLE_SLOTS), self::CYCLE_SLOTS, null);
        $templates = WorkShiftTemplate::query()
            ->where('user_id', $userId)
            ->get();
        $templatesByCode = $templates->keyBy('code');
        $templatesById = $templates->keyBy(fn (WorkShiftTemplate $shift): string => (string) $shift->id);

        DB::transaction(function () use ($userId, $cycleStart, $cycleEnd, $assignments, $templatesByCode, $templatesById): void {
            $cycle = WorkScheduleCycle::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'cycle_start_date' => $cycleStart->toDateString(),
                ],
                ['cycle_end_date' => $cycleEnd->toDateString()],
            );

            $cycle->days()->delete();

            for ($date = $cycleStart, $offset = 0; $date->lte($cycleEnd); $date = $date->addDay(), $offset++) {
                $assignment = $assignments[$offset] ?? null;
                $shift = null;

                if ($assignment !== null && $assignment !== '') {
                    $assignment = (string) $assignment;
                    $shift = $templatesByCode->get($assignment) ?? $templatesById->get($assignment);

                    if ($shift === null) {
                        throw ValidationException::withMessages([
                            "assignments.$offset" => "The selected shift template [$assignment] does not exist.",
                        ]);
                    }
                }

                WorkScheduleDay::query()->create([
                    'user_id' => $userId,
                    'work_schedule_cycle_id' => $cycle->id,
                    'work_date' => $date->toDateString(),
                    'day_offset' => $offset,
                    'work_shift_template_id' => $shift?->id,
                ]);
            }
        });

        return $this->cyclePayload($userId, $cycleStart);
    }

    /** @return array<int, array<string, mixed>> */
    public function materializeDays(int $userId, CarbonImmutable|string $from, CarbonImmutable|string $to): array
    {
        $from = $this->date($from);
        $to = $this->date($to);

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'to' => 'The to date must be after or equal to the from date.',
            ]);
        }

        $scanFrom = $from->subDays(2);
        $rows = WorkScheduleDay::query()
            ->with('shiftTemplate')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$scanFrom->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (WorkScheduleDay $day): string => $day->work_date->toDateString());

        $payload = [];
        $previousEndAt = null;

        for ($date = $scanFrom; $date->lte($to); $date = $date->addDay()) {
            $row = $rows->get($date->toDateString());
            $shift = $row?->shiftTemplate;
            $shift = in_array($shift?->user_id, [null, $userId], true) ? $shift : null;
            $entry = [
                'date' => $date->toDateString(),
                'day_offset' => $row?->day_offset,
                'shift_template' => null,
                'starts_at' => null,
                'ends_at' => null,
                'blocked' => false,
            ];

            if ($shift !== null) {
                $startsAt = $this->shiftStartAt($date, $shift);
                $endsAt = $this->shiftEndAt($date, $shift);
                $blocked = $previousEndAt !== null && $startsAt->lte($previousEndAt);

                $entry = [
                    'date' => $date->toDateString(),
                    'day_offset' => $row->day_offset,
                    'shift_template' => $this->formatShiftTemplate($shift),
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt->toIso8601String(),
                    'blocked' => $blocked,
                ];

                if (! $blocked) {
                    $previousEndAt = $endsAt;
                }
            }

            if ($date->gte($from)) {
                $payload[] = $entry;
            }
        }

        return $payload;
    }

    public function formatShiftTemplate(WorkShiftTemplate $shift): array
    {
        return [
            'id' => $shift->id,
            'code' => $shift->code,
            'name' => $shift->name,
            'category' => $shift->category,
            'color' => $shift->color,
            'start_time' => $shift->start_time ? substr((string) $shift->start_time, 0, 5) : null,
            'end_time' => $shift->end_time ? substr((string) $shift->end_time, 0, 5) : null,
            'sort_order' => $shift->sort_order,
            'is_overnight' => $shift->start_time && $shift->end_time
                && $this->timeToMinutes((string) $shift->end_time) <= $this->timeToMinutes((string) $shift->start_time),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function globalTemplates(): array
    {
        return WorkShiftTemplate::query()
            ->whereNull('user_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (WorkShiftTemplate $shift): array => $this->formatShiftTemplate($shift))
            ->values()
            ->all();
    }

    public function rosterPayload(CarbonImmutable|string $from, CarbonImmutable|string $to): array
    {
        $from = $this->date($from);
        $to = $this->date($to);

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'to' => 'The to date must be after or equal to the from date.',
            ]);
        }

        if ($from->diffInDays($to) > 62) {
            throw ValidationException::withMessages([
                'to' => 'The date range cannot exceed 62 days.',
            ]);
        }

        $days = WorkScheduleDay::query()
            ->with('shiftTemplate')
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('user_id');

        $staff = User::query()
            ->select(['id', 'name', 'email', 'staff_id', 'position'])
            ->orderByRaw('staff_id IS NULL')
            ->orderBy('staff_id', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function (User $user) use ($days): array {
                $cells = [];

                foreach ($days->get($user->id, collect()) as $day) {
                    $cells[$day->work_date->format('Y-m-d')] = $day->shiftTemplate
                        ? $this->formatShiftTemplate($day->shiftTemplate)
                        : null;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'staff_id' => $user->staff_id,
                    'position' => $user->position,
                    'entries' => $cells,
                ];
            })
            ->values()
            ->all();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'codes' => $this->globalTemplates(),
            'staff' => $staff,
        ];
    }

    public function updateRosterCell(int $userId, CarbonImmutable|string $workDate, ?int $shiftTemplateId): ?array
    {
        $date = $this->date($workDate);

        if ($shiftTemplateId === null) {
            WorkScheduleDay::query()
                ->where('user_id', $userId)
                ->where('work_date', $date->toDateString())
                ->delete();

            return null;
        }

        $template = WorkShiftTemplate::query()->findOrFail($shiftTemplateId);
        $cycleStart = $this->cycleStartFor($date);
        $cycleEnd = $this->cycleEndForStart($cycleStart);
        $cycle = WorkScheduleCycle::query()->updateOrCreate(
            ['user_id' => $userId, 'cycle_start_date' => $cycleStart->toDateString()],
            ['cycle_end_date' => $cycleEnd->toDateString()],
        );

        WorkScheduleDay::query()->updateOrCreate(
            ['user_id' => $userId, 'work_date' => $date->toDateString()],
            [
                'work_schedule_cycle_id' => $cycle->id,
                'day_offset' => $cycleStart->diffInDays($date),
                'work_shift_template_id' => $template->id,
            ],
        );

        return $this->formatShiftTemplate($template);
    }

    public function clearRosterRange(int $userId, CarbonImmutable|string $from, CarbonImmutable|string $to): void
    {
        WorkScheduleDay::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$this->date($from)->toDateString(), $this->date($to)->toDateString()], 'and')
            ->delete();
    }

    private function setting(int $userId): WorkScheduleSetting
    {
        return WorkScheduleSetting::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'system_type' => 2,
                'remind' => true,
                'reminder_minutes_before' => 30,
            ],
        );
    }

    private function ensureDefaultShiftTemplates(int $userId): void
    {
        if (WorkShiftTemplate::query()->where('user_id', $userId)->exists()) {
            return;
        }

        WorkShiftTemplate::query()->create([
            'user_id' => $userId,
            'code' => 'day',
            'name' => 'Day',
            'start_time' => '07:30:00',
            'end_time' => '19:30:00',
            'sort_order' => 1,
        ]);

        WorkShiftTemplate::query()->create([
            'user_id' => $userId,
            'code' => 'night',
            'name' => 'Night',
            'start_time' => '19:30:00',
            'end_time' => '07:30:00',
            'sort_order' => 2,
        ]);
    }

    private function parseCycleStartDate(CarbonImmutable|string $cycleStartDate): CarbonImmutable
    {
        $cycleStart = $this->date($cycleStartDate);

        if ($cycleStart->day !== self::ANCHOR_DAY) {
            throw ValidationException::withMessages([
                'cycle_start_date' => 'The cycle start date must be the 26th day of a month.',
            ]);
        }

        return $cycleStart;
    }

    private function shiftStartAt(CarbonImmutable $date, WorkShiftTemplate $shift): CarbonImmutable
    {
        [$hour, $minute] = $this->parseTime((string) $shift->start_time);

        return $date->setTime($hour, $minute);
    }

    private function shiftEndAt(CarbonImmutable $date, WorkShiftTemplate $shift): CarbonImmutable
    {
        [$hour, $minute] = $this->parseTime((string) $shift->end_time);
        $endsAt = $date->setTime($hour, $minute);

        return $this->timeToMinutes((string) $shift->end_time) <= $this->timeToMinutes((string) $shift->start_time)
            ? $endsAt->addDay()
            : $endsAt;
    }

    private function normalizeTime(string $time): string
    {
        return substr($time, 0, 5).':00';
    }

    /** @return array{0:int, 1:int} */
    private function parseTime(string $time): array
    {
        [$hour, $minute] = explode(':', substr($time, 0, 5));

        return [(int) $hour, (int) $minute];
    }

    private function timeToMinutes(string $time): int
    {
        [$hour, $minute] = $this->parseTime($time);

        return $hour * 60 + $minute;
    }

    private function date(CarbonImmutable|string $date): CarbonImmutable
    {
        if ($date instanceof CarbonImmutable) {
            return $date->setTimezone(config('app.timezone'))->startOfDay();
        }

        return CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
    }
}
