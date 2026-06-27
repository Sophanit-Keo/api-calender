<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\FormatsCalendarRecords;
use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NoteController extends Controller
{
    use FormatsCalendarRecords;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $notes = Note::query()
            ->when($validated['date'] ?? null, fn ($query, string $date) => $query->whereDate('date', $date))
            ->when($validated['from'] ?? null, fn ($query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, string $to) => $query->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Note $note): array => $this->formatNote($note))
            ->values();

        return response()->json(['data' => $notes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'text' => ['required', 'string', 'max:10000'],
        ]);

        $note = Note::query()->create($validated);

        return response()->json(['data' => $this->formatNote($note)], Response::HTTP_CREATED);
    }

    public function show(Note $note): JsonResponse
    {
        return response()->json(['data' => $this->formatNote($note)]);
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'text' => ['sometimes', 'required', 'string', 'max:10000'],
        ]);

        $note->fill($validated)->save();

        return response()->json(['data' => $this->formatNote($note->refresh())]);
    }

    public function destroy(Note $note): Response
    {
        $note->delete();

        return response()->noContent();
    }
}
