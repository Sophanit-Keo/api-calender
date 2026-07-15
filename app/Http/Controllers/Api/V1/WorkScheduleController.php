<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WorkScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->rosterPayload($validated['from'], $validated['to']),
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

    private function targetUserId(Request $request): int
    {
        return (int) ($request->input('user_id') ?? $request->user()->id);
    }
}
