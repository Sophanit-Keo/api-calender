<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WorkScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    public function __construct(
        private readonly WorkScheduleService $workSchedule,
    ) {}

    public function settings(): JsonResponse
    {
        return response()->json([
            'data' => $this->workSchedule->settingsPayload(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
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
            'data' => $this->workSchedule->updateSettings($validated),
        ]);
    }

    public function cycle(string $cycle_start_date): JsonResponse
    {
        return response()->json([
            'data' => $this->workSchedule->cyclePayload($cycle_start_date),
        ]);
    }

    public function updateCycle(Request $request, string $cycle_start_date): JsonResponse
    {
        $validated = $request->validate([
            'assignments' => ['required', 'array', 'min:1', 'max:31'],
            'assignments.*' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->saveCycle($cycle_start_date, $validated['assignments']),
        ]);
    }

    public function days(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json([
            'data' => $this->workSchedule->materializeDays($validated['from'], $validated['to']),
        ]);
    }
}
