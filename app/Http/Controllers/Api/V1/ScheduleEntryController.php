<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ScheduleEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $entries = $this->filteredQuery($request, $filters)
            ->with(['owner:id,name,email', 'assignee:id,name,email'])
            ->orderBy('scheduled_date')
            ->orderByRaw('CASE WHEN start_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get()
            ->map(fn (ScheduleEntry $entry): array => $this->format($entry));

        return response()->json(['data' => $entries]);
    }

    public function store(Request $request): JsonResponse
    {
        $entry = ScheduleEntry::query()->create($this->validatedEntry($request) + [
            'owner_id' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $this->format($entry->load(['owner:id,name,email', 'assignee:id,name,email'])),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, ScheduleEntry $scheduleEntry): JsonResponse
    {
        return response()->json([
            'data' => $this->format($scheduleEntry->load(['owner:id,name,email', 'assignee:id,name,email'])),
        ]);
    }

    public function update(Request $request, ScheduleEntry $scheduleEntry): JsonResponse
    {
        $scheduleEntry->fill($this->validatedEntry($request, true))->save();

        return response()->json([
            'data' => $this->format($scheduleEntry->refresh()->load(['owner:id,name,email', 'assignee:id,name,email'])),
        ]);
    }

    public function destroy(Request $request, ScheduleEntry $scheduleEntry): Response
    {
        $scheduleEntry->delete();

        return response()->noContent();
    }

    public function summary(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $entries = $this->filteredQuery($request, $filters)->get();
        $now = CarbonImmutable::now(config('app.timezone'));
        $counts = ['total' => $entries->count(), 'upcoming' => 0, 'completed' => 0, 'overdue' => 0];

        foreach ($entries as $entry) {
            if ($entry->status === 'completed') {
                $counts['completed']++;
            } elseif (! in_array($entry->status, ['completed', 'cancelled'], true)) {
                $this->entryAt($entry)->lt($now) ? $counts['overdue']++ : $counts['upcoming']++;
            }
        }

        return response()->json(['data' => $counts]);
    }

    public function users(Request $request): JsonResponse
    {
        $users = User::query()->select(['id', 'name', 'email'])->orderBy('name')->get();

        return response()->json(['data' => $users]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $entries = $this->filteredQuery($request, $filters)
            ->with(['owner:id,name,email', 'assignee:id,name,email'])
            ->orderBy('scheduled_date')->orderBy('start_time')->get();

        return response()->streamDownload(function () use ($entries): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Date', 'Start Time', 'End Time', 'Task', 'Description', 'Priority', 'Status', 'Assignee Email', 'Owner Email']);
            foreach ($entries as $entry) {
                fputcsv($output, [
                    $entry->scheduled_date->format('Y-m-d'),
                    $this->time($entry->start_time),
                    $this->time($entry->end_time),
                    $entry->task,
                    $entry->description,
                    $entry->priority,
                    $entry->status,
                    $entry->assignee?->email,
                    $entry->owner?->email,
                ]);
            }
            fclose($output);
        }, 'schedules-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($validated['file']->getRealPath(), 'rb');
        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($header): string => strtolower(trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF")), $headers);
        $required = ['date', 'task'];

        if (array_diff($required, $headers)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV must contain Date and Task columns.']);
        }

        $created = 0;
        $errors = [];
        DB::transaction(function () use ($handle, $headers, $request, &$created, &$errors): void {
            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if (count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                $values = array_pad($values, count($headers), null);
                $row = array_combine($headers, array_slice($values, 0, count($headers)));
                try {
                    $assignee = empty($row['assignee email']) ? null : User::query()->where('email', trim($row['assignee email']))->value('id');
                    ScheduleEntry::query()->create([
                        'owner_id' => $request->user()->id,
                        'assignee_id' => $assignee,
                        'scheduled_date' => CarbonImmutable::parse($row['date'])->toDateString(),
                        'start_time' => $this->importTime($row['start time'] ?? null),
                        'end_time' => $this->importTime($row['end time'] ?? null),
                        'task' => trim((string) $row['task']),
                        'description' => $row['description'] ?? null,
                        'priority' => in_array($row['priority'] ?? '', ScheduleEntry::PRIORITIES, true) ? $row['priority'] : 'medium',
                        'status' => in_array($row['status'] ?? '', ScheduleEntry::STATUSES, true) ? $row['status'] : 'scheduled',
                    ]);
                    $created++;
                } catch (\Throwable $exception) {
                    $errors[] = "Line {$line}: invalid schedule data.";
                }
            }
        });
        fclose($handle);

        return response()->json(['data' => ['imported' => $created, 'errors' => $errors]], Response::HTTP_CREATED);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(ScheduleEntry::PRIORITIES)],
            'status' => ['nullable', Rule::in(ScheduleEntry::STATUSES)],
        ]);
    }

    private function filteredQuery(Request $request, array $filters): Builder
    {
        $query = ScheduleEntry::query();
        if ($filters['date'] ?? null) {
            $query->whereDate('scheduled_date', $filters['date']);
        }
        if ($filters['from'] ?? null) {
            $query->whereDate('scheduled_date', '>=', $filters['from']);
        }
        if ($filters['to'] ?? null) {
            $query->whereDate('scheduled_date', '<=', $filters['to']);
        }
        if ($filters['user_id'] ?? null) {
            $query->where(fn (Builder $q) => $q->where('owner_id', $filters['user_id'])->orWhere('assignee_id', $filters['user_id']));
        }
        if ($filters['search'] ?? null) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(fn (Builder $q) => $q->where('task', 'like', $search)->orWhere('description', 'like', $search));
        }
        if ($filters['priority'] ?? null) {
            $query->where('priority', $filters['priority']);
        }
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    private function validatedEntry(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'scheduled_date' => [$required, 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'task' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => [$required, Rule::in(ScheduleEntry::PRIORITIES)],
            'status' => [$required, Rule::in(ScheduleEntry::STATUSES)],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function format(ScheduleEntry $entry): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $timing = $entry->status === 'completed' ? 'completed'
            : ((! in_array($entry->status, ['completed', 'cancelled'], true) && $this->entryAt($entry)->lt($now)) ? 'overdue' : 'upcoming');

        return [
            'id' => $entry->id,
            'owner' => $entry->owner,
            'assignee' => $entry->assignee,
            'scheduled_date' => $entry->scheduled_date->format('Y-m-d'),
            'start_time' => $this->time($entry->start_time),
            'end_time' => $this->time($entry->end_time),
            'task' => $entry->task,
            'description' => $entry->description,
            'priority' => $entry->priority,
            'status' => $entry->status,
            'timing' => $timing,
            'can_edit' => true,
            'created_at' => $entry->created_at?->toIso8601String(),
            'updated_at' => $entry->updated_at?->toIso8601String(),
        ];
    }

    private function entryAt(ScheduleEntry $entry): CarbonImmutable
    {
        return CarbonImmutable::parse($entry->scheduled_date->format('Y-m-d').' '.($this->time($entry->end_time) ?: $this->time($entry->start_time) ?: '23:59'), config('app.timezone'));
    }

    private function time(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }

    private function importTime(mixed $time): ?string
    {
        return empty($time) ? null : CarbonImmutable::parse((string) $time)->format('H:i');
    }
}
