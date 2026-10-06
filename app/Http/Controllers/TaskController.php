<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AiTaskAssistant;
use App\Services\CanvasService;
use App\Services\GeminiService;
use App\Services\TaskRiskAssessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function analyzeGeminiRisk(
        Request $request,
        Task $task,
        GeminiService $gemini,
        TaskRiskAssessment $riskAssessment
    ): RedirectResponse {
        $this->ensureTaskOwnership($request, $task);
        $assessment = $riskAssessment->assess($task);

        try {
            $analysis = $gemini->analyzeDeadlineRisk([
                'title' => $task->title,
                'course' => $task->course,
                'deadline' => $task->deadline?->toDateString(),
                'estimated_hours' => $task->estimated_hours,
                'priority' => $task->priority,
                'progress' => $task->progress,
                'remaining_hours' => $assessment['remaining_hours'],
                'risk_level' => $assessment['level'],
                'risk_reason' => $assessment['reason'],
            ]);
        } catch (\RuntimeException $exception) {
            return redirect()->route('tasks.show', $task)
                ->with('gemini_error', $exception->getMessage());
        }

        return redirect()->route('tasks.show', $task)
            ->with('gemini_analysis', $analysis);
    }

    public function assistant(Request $request, Task $task, AiTaskAssistant $assistant): RedirectResponse
    {
        $this->ensureTaskOwnership($request, $task);

        $validated = $request->validate([
            'question' => 'required|string|max:2000',
        ]);

        try {
            $answer = $assistant->answer($task, $validated['question']);
        } catch (\RuntimeException $exception) {
            return redirect()->route('tasks.show', $task)
                ->withInput()
                ->with('assistant_error', $exception->getMessage());
        }

        return redirect()->route('tasks.show', $task)
            ->with('assistant_question', $validated['question'])
            ->with('assistant_answer', $answer);
    }

    public function submitCanvasAnswer(Request $request, Task $task, CanvasService $canvas): RedirectResponse
    {
        $this->ensureTaskOwnership($request, $task);

        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:100000'],
        ]);

        try {
            $canvas->submitTextAnswer(
                $request->user(),
                (string) $task->canvas_course_id,
                (string) $task->canvas_assignment_id,
                $validated['answer']
            );
        } catch (\RuntimeException $exception) {
            return redirect()->route('tasks.show', $task)
                ->withInput()
                ->with('canvas_submit_error', $exception->getMessage());
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Your answer was submitted to Canvas.');
    }

    public function generateAndSubmitCanvasAnswer(
        Request $request,
        Task $task,
        AiTaskAssistant $assistant,
        CanvasService $canvas
    ): RedirectResponse {
        $this->ensureTaskOwnership($request, $task);

        try {
            $answer = $assistant->draftCanvasAssignmentAnswer($task);
            $canvas->submitTextAnswer(
                $request->user(),
                (string) $task->canvas_course_id,
                (string) $task->canvas_assignment_id,
                $answer
            );
        } catch (\RuntimeException $exception) {
            return redirect()->route('tasks.show', $task)
                ->with('canvas_submit_error', $exception->getMessage());
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'The AI-generated answer was submitted to Canvas.')
            ->with('canvas_ai_answer', $answer);
    }

    public function index(Request $request, TaskRiskAssessment $riskAssessment): View
    {
        $allTasks = Task::where('user_id', $request->user()->id)->orderBy('deadline')->get();
        $summary = $riskAssessment->summarize($allTasks);
        $tasks = $allTasks;
        $query = trim((string) $request->query('q', ''));
        $risk = $request->query('risk');
        $status = $request->query('status');
        $course = $request->query('course');
        $sort = $request->query('sort', 'deadline');

        if ($query !== '') {
            $tasks = $tasks->filter(fn (Task $task): bool => str_contains(mb_strtolower($task->title.' '.$task->description), mb_strtolower($query)));
        }

        if (in_array($status, ['pending', 'in_progress', 'completed'], true)) {
            $tasks = $tasks->where('status', $status);
        }

        if ($status === 'overdue') {
            $tasks = $tasks->filter(fn (Task $task): bool => $summary['assessments'][$task->id]['overdue']);
        }

        if (in_array($risk, ['high', 'medium', 'low'], true)) {
            $tasks = $tasks->filter(fn (Task $task): bool => $summary['assessments'][$task->id]['level'] === $risk);
        }

        if (is_string($course) && $course !== '') {
            $tasks = $tasks->where('course', $course);
        }

        if ($sort === 'priority') {
            $tasks = $tasks->sortBy(fn (Task $task): int => ['high' => 0, 'normal' => 1, 'low' => 2][$task->priority]);
        } else {
            $tasks = $tasks->sortBy(fn (Task $task): int => $task->deadline?->timestamp ?? PHP_INT_MAX);
        }

        $tasks = $tasks->values();
        $courses = $allTasks->pluck('course')->filter()->unique()->sort()->values();

        return view('tasks.manage', [
            'tasks' => $tasks,
            'assessments' => $summary['assessments'],
            'notifications' => $summary['notifications'],
            'courses' => $courses,
            'filters' => compact('query', 'risk', 'status', 'course', 'sort'),
        ]);
    }

    public function create(Request $request, TaskRiskAssessment $riskAssessment): View
    {
        return view('tasks.form', [
            'task' => null,
            'notifications' => $riskAssessment->summarize(Task::where('user_id', $request->user()->id)->get())['notifications'],
        ]);
    }

    public function store(Request $request, TaskRiskAssessment $riskAssessment): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed',
            'progress' => 'required|integer|between:0,100',
            'estimated_hours' => 'nullable|numeric|min:0|max:9999',
            'priority' => 'required|in:low,normal,high',
            'course' => 'nullable|string|max:100',
        ]);

        $validated['course'] = filled($validated['course'] ?? null) ? trim($validated['course']) : null;

        if ($validated['status'] === 'completed') {
            $validated['progress'] = 100;
        }

        $task = new Task($validated + ['user_id' => $request->user()->id]);
        $task->course_id = $request->user()->courses()->where('name', $validated['course'] ?? null)->value('id');
        $task->risk_level = $riskAssessment->assess($task)['level'];
        $task->save();

        return redirect()
            ->route($request->input('return_to') === 'dashboard' ? 'dashboard' : 'tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function show(Request $request, Task $task, TaskRiskAssessment $riskAssessment): View
    {
        $this->ensureTaskOwnership($request, $task);

        return view('tasks.show', [
            'task' => $task,
            'assessment' => $riskAssessment->assess($task),
            'notifications' => $riskAssessment->summarize(Task::where('user_id', $request->user()->id)->get())['notifications'],
        ]);
    }

    public function edit(Request $request, Task $task, TaskRiskAssessment $riskAssessment): View
    {
        $this->ensureTaskOwnership($request, $task);

        return view('tasks.form', [
            'task' => $task,
            'notifications' => $riskAssessment->summarize(Task::where('user_id', $request->user()->id)->get())['notifications'],
        ]);
    }

    public function update(Request $request, Task $task, TaskRiskAssessment $riskAssessment): RedirectResponse
    {
        $this->ensureTaskOwnership($request, $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed',
            'progress' => 'required|integer|between:0,100',
            'estimated_hours' => 'nullable|numeric|min:0|max:9999',
            'priority' => 'required|in:low,normal,high',
            'course' => 'nullable|string|max:100',
        ]);

        $validated['course'] = filled($validated['course'] ?? null) ? trim($validated['course']) : null;

        if ($validated['status'] === 'completed') {
            $validated['progress'] = 100;
        }

        $validated['course_id'] = $request->user()->courses()->where('name', $validated['course'] ?? null)->value('id');
        $task->update($validated);
        $task->update(['risk_level' => $riskAssessment->assess($task)['level']]);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->ensureTaskOwnership($request, $task);

        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function complete(Request $request, Task $task, TaskRiskAssessment $riskAssessment): RedirectResponse
    {
        $this->ensureTaskOwnership($request, $task);

        $task->update(['status' => 'completed', 'progress' => 100]);
        $task->update(['risk_level' => $riskAssessment->assess($task)['level']]);

        return redirect()->route('tasks.show', $task)->with('success', 'Task marked complete.');
    }

    private function ensureTaskOwnership(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }
}
