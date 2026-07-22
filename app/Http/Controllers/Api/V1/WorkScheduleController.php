<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WorkScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkScheduleController extends Controller
{
    public function __construct(
        private readonly WorkScheduleService $workSchedule,
    ) {}

    public function settings(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->workSchedule->settingsPayload($this->targetUserId($request)),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'system_type' => ['sometimes', 'integer', 'in:2,3'],
            'remind' => ['sometimes', 'boolean'],
            'reminder_minutes_before' => ['sometimes', 'integer', 'min:0', 'max:1440'],
            'shift_templates' => ['sometimes', 'array'],
            'shift_templates.*.code' => ['required_with:shift_templates', 'string', 'max:50'],
            'shift_templates.*.name' => ['required_with:shift_templates', 'string', 'max:255'],
            'shift_templates.*.start_time' => ['required_with:shift_templates', 'date_format:H:i'],
            'shift_templates.*.end_time' => ['required_with:shift_templates', 'date_format:H:i'],
            'shift_templates.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->updateSettings($this->targetUserId($request), $validated),
        ]);
    }

    public function cycle(Request $request, string $cycle_start_date): JsonResponse
    {
        return response()->json([
            'data' => $this->workSchedule->cyclePayload($this->targetUserId($request), $cycle_start_date),
        ]);
    }

    public function updateCycle(Request $request, string $cycle_start_date): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assignments' => ['required', 'array', 'min:1', 'max:31'],
            'assignments.*' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->saveCycle($this->targetUserId($request), $cycle_start_date, $validated['assignments']),
        ]);
    }

    public function days(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->materializeDays($this->targetUserId($request), $validated['from'], $validated['to']),
        ]);
    }

    public function roster(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'required_with:from'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (! empty($validated['from']) && ! empty($validated['to'])) {
            $from = $validated['from'];
            $to = $validated['to'];
        } else {
            $cycleStart = $this->workSchedule->cycleStartFor($validated['date'] ?? now()->toDateString());
            $from = $cycleStart->toDateString();
            $to = $this->workSchedule->cycleEndForStart($cycleStart)->toDateString();
        }

        return response()->json([
            'data' => $this->workSchedule->rosterPayload($from, $to),
        ]);
    }

    public function updateRosterCell(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'work_shift_template_id' => ['nullable', 'integer', 'exists:work_shift_templates,id'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->updateRosterCell(
                $validated['user_id'],
                $validated['work_date'],
                $validated['work_shift_template_id'] ?? null,
            ),
        ]);
    }

    public function clearRosterStaff(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $this->workSchedule->clearRosterRange($user->id, $validated['from'], $validated['to']);

        return response()->noContent();
    }

    public function updateRosterStaff(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => ['nullable', 'string', 'max:255', Rule::unique('users', 'staff_id')->ignore($user->id)],
            'position' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->updateStaffProfile($user, $validated),
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->workingOnDate($validated['date'] ?? now()->toDateString()),
        ]);
    }

    public function exportRoster(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['week', 'month'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $period = $validated['period'] ?? 'month';
        $reference = $validated['date'] ?? now()->toDateString();

        if ($period === 'week') {
            $anchor = CarbonImmutable::parse($reference, config('app.timezone'))->startOfDay();
            $from = $anchor->startOfWeek(CarbonImmutable::MONDAY);
            $to = $anchor->endOfWeek(CarbonImmutable::SUNDAY);
        } else {
            $from = $this->workSchedule->cycleStartFor($reference);
            $to = $this->workSchedule->cycleEndForStart($from);
        }

        $payload = $this->workSchedule->rosterPayload($from->toDateString(), $to->toDateString());

        $dates = [];
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return response()->streamDownload(function () use ($payload, $dates): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_merge(['ID', 'Group', 'Name', 'Position'], $dates));

            foreach ($payload['staff'] as $staff) {
                $row = [$staff['staff_id'], $staff['group'], $staff['name'], $staff['position']];

                foreach ($dates as $date) {
                    $row[] = $staff['entries'][$date]['code'] ?? null;
                }

                fputcsv($output, $row);
            }

            fclose($output);
        }, "roster-{$from->toDateString()}-to-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importRoster(Request $request): JsonResponse
    {
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($validated['file']->getRealPath(), 'rb');
        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($header): string => strtolower(trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF")), $headers);

        if (array_slice($headers, 0, 4) !== ['id', 'group', 'name', 'position']) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must start with ID, Group, Name, Position columns.']);
        }

        $dateColumns = array_slice($headers, 4);

        foreach ($dateColumns as $dateColumn) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateColumn)) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => "Invalid date column header: {$dateColumn}"]);
            }
        }

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($handle, $dateColumns, &$imported, &$errors): void {
            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if (count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $values = array_pad($values, 4 + count($dateColumns), null);
                $staffId = trim((string) ($values[0] ?? ''));

                if ($staffId === '') {
                    $errors[] = "Line {$line}: missing staff ID.";

                    continue;
                }

                $user = User::query()->where('staff_id', $staffId)->first();

                if ($user === null) {
                    $errors[] = "Line {$line}: unknown staff ID [{$staffId}].";

                    continue;
                }

                foreach ($dateColumns as $index => $date) {
                    $code = trim((string) ($values[4 + $index] ?? ''));

                    if ($code === '') {
                        continue;
                    }

                    $shift = $this->workSchedule->findShiftByCode($code);

                    if ($shift === null) {
                        $errors[] = "Line {$line}: unknown shift code [{$code}] for {$date}.";

                        continue;
                    }

                    $this->workSchedule->updateRosterCell($user->id, $date, $shift->id);
                    $imported++;
                }
            }
        });

        fclose($handle);

        return response()->json(['data' => ['imported' => $imported, 'errors' => $errors]], Response::HTTP_CREATED);
    }

    private function targetUserId(Request $request): int
    {
        return (int) ($request->input('user_id') ?? $request->user()->id);
    }
}
