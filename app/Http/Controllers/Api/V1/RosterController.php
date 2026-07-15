<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RosterCode;
use App\Models\RosterEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RosterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from']);
        $to = CarbonImmutable::parse($validated['to']);

        if ($from->diffInDays($to) > 62) {
            throw ValidationException::withMessages([
                'to' => 'The date range cannot exceed 62 days.',
            ]);
        }

        $codes = RosterCode::query()->orderBy('sort_order')->orderBy('id')->get();

        $entries = RosterEntry::query()
            ->with('rosterCode')
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('user_id');

        $staff = User::query()
            ->select(['id', 'name', 'email', 'staff_id', 'position'])
            ->orderByRaw('staff_id IS NULL')
            ->orderBy('staff_id')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($entries): array {
                $cells = [];

                foreach ($entries->get($user->id, collect()) as $entry) {
                    $cells[$entry->work_date->format('Y-m-d')] = $entry->rosterCode
                        ? $this->formatCode($entry->rosterCode)
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
            });

        return response()->json([
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'codes' => $codes->map(fn (RosterCode $code): array => $this->formatCode($code))->values(),
                'staff' => $staff->values(),
            ],
        ]);
    }

    public function updateCell(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'roster_code_id' => ['nullable', 'integer', Rule::exists('roster_codes', 'id')],
        ]);

        if (($validated['roster_code_id'] ?? null) === null) {
            RosterEntry::query()
                ->where('user_id', $validated['user_id'])
                ->where('work_date', $validated['work_date'])
                ->delete();

            return response()->json(['data' => null]);
        }

        $entry = RosterEntry::query()->updateOrCreate(
            ['user_id' => $validated['user_id'], 'work_date' => $validated['work_date']],
            ['roster_code_id' => $validated['roster_code_id']],
        );

        return response()->json([
            'data' => $this->formatCode($entry->load('rosterCode')->rosterCode),
        ]);
    }

    public function clearStaff(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        RosterEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$validated['from'], $validated['to']])
            ->delete();

        return response()->noContent();
    }

    private function formatCode(?RosterCode $code): ?array
    {
        if ($code === null) {
            return null;
        }

        return [
            'id' => $code->id,
            'code' => $code->code,
            'label' => $code->label,
            'category' => $code->category,
            'start_time' => $code->start_time ? substr((string) $code->start_time, 0, 5) : null,
            'end_time' => $code->end_time ? substr((string) $code->end_time, 0, 5) : null,
            'color' => $code->color,
        ];
    }
}
