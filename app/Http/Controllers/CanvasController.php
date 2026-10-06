<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Task;
use App\Services\CanvasService;
use App\Services\GeminiService;
use App\Services\TaskRiskAssessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class CanvasController extends Controller
{
    public function aiTest(GeminiService $gemini): JsonResponse
    {
        try {
            $result = $gemini->analyzeDeadlineRisk([
                'title' => 'Database Assignment',
                'course' => 'Database Management',
                'deadline' => '2026-10-15',
                'estimated_hours' => 8,
                'priority' => 'high',
            ]);

            return response()->json([
                'status' => 'success',
                'result' => $result,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 503);
        }
    }

    public function connect(Request $request, CanvasService $canvas): RedirectResponse
    {
        $state = Str::random(64);
        $request->session()->put('canvas_oauth_state', $state);

        try {
            return redirect()->away($canvas->authorizationUrl($this->redirectUri(), $state));
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.index', 'canvas')
                ->with('error', $exception->getMessage());
        }
    }

    public function callback(Request $request, CanvasService $canvas): RedirectResponse
    {
        $expectedState = $request->session()->pull('canvas_oauth_state');
        $receivedState = (string) $request->query('state', '');

        if (! is_string($expectedState) || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('settings.index', 'canvas')
                ->with('error', 'Canvas sign-in could not be verified. Please try connecting again.');
        }

        if ($request->filled('error')) {
            return redirect()->route('settings.index', 'canvas')
                ->with('error', 'Canvas access was not granted.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $canvas->exchangeAuthorizationCode(
                $request->user(),
                $validated['code'],
                $this->redirectUri()
            );

            return redirect()->route('settings.index', 'canvas')
                ->with('success', 'Canvas account connected successfully.');
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.index', 'canvas')
                ->with('error', $exception->getMessage());
        }
    }

    public function disconnect(Request $request, CanvasService $canvas): RedirectResponse
    {
        $canvas->disconnect($request->user());

        return redirect()->route('settings.index', 'canvas')
            ->with('success', 'Canvas account disconnected.');
    }

    public function test(Request $request, CanvasService $canvas): JsonResponse
    {
        try {
            $canvasUser = $canvas->getCurrentUser($request->user());

            return response()->json([
                'status' => 'success',
                'message' => 'Canvas is connected successfully.',
                'canvas_user' => $canvasUser,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function assignments(Request $request, CanvasService $canvas): JsonResponse
    {
        try {
            $courses = $canvas->getCourses($request->user());

            $assignments = [];

            foreach ($courses as $course) {
                if (empty($course['id'])) {
                    continue;
                }

                $courseAssignments = $canvas->getAssignments(
                    $request->user(),
                    $course['id']
                );

                foreach ($courseAssignments as $assignment) {
                    $assignments[] = [
                        'canvas_course_id' => $course['id'],
                        'course_name' => $course['name'] ?? 'Unknown Course',
                        'canvas_assignment_id' => $assignment['id'] ?? null,
                        'name' => $assignment['name'] ?? null,
                        'description' => $assignment['description'] ?? null,
                        'due_at' => $assignment['due_at'] ?? null,
                        'html_url' => $assignment['html_url'] ?? null,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'count' => count($assignments),
                'assignments' => $assignments,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function sync(
        Request $request,
        CanvasService $canvas,
        TaskRiskAssessment $riskAssessment
    ): RedirectResponse {
        $user = $request->user();

        try {
            $courses = $canvas->getCourses($user);

            $created = 0;
            $updated = 0;
            foreach ($courses as $canvasCourse) {
                if (empty($canvasCourse['id'])) {
                    continue;
                }

                $canvasCourseId = (string) $canvasCourse['id'];
                $courseName = trim(
                    (string) ($canvasCourse['name'] ?? 'Unnamed Course')
                );

                $course = Course::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $courseName,
                    ],
                    [
                        'professor' => null,
                        'grading_components' => null,
                    ]
                );

                $assignments = $canvas->getAssignments(
                    $user,
                    $canvasCourse['id']
                );

                foreach ($assignments as $assignment) {
                    if (empty($assignment['id']) || empty($assignment['name'])) {
                        continue;
                    }

                    $dueAt = filled($assignment['due_at'] ?? null)
                        ? Carbon::parse($assignment['due_at'])
                        : null;

                    $existingTask = Task::where('user_id', $user->id)
                        ->where(
                            'canvas_assignment_id',
                            (string) $assignment['id']
                        )
                        ->first();

                    $taskData = [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'title' => $assignment['name'],
                        'description' => $this->cleanDescription(
                            $assignment['description'] ?? null
                        ),
                        'deadline' => $dueAt?->toDateString(),
                        'status' => $existingTask?->status ?? 'pending',
                        'progress' => $existingTask?->progress ?? 0,
                        'estimated_hours' => $existingTask?->estimated_hours,
                        'priority' => $existingTask?->priority ?? 'normal',
                        'course' => $courseName,
                        'source' => 'canvas',
                        'canvas_assignment_id' => (string) $assignment['id'],
                        'canvas_course_id' => $canvasCourseId,
                        'canvas_due_at' => $dueAt,
                        'canvas_url' => $assignment['html_url'] ?? null,
                    ];

                    $task = Task::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'canvas_assignment_id' => (string) $assignment['id'],
                        ],
                        $taskData
                    );

                    $task->risk_level = $riskAssessment->assess($task)['level'];
                    $task->save();

                    if ($existingTask) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }
            }

            return redirect()
                ->route('tasks.index')
                ->with(
                    'success',
                    "Canvas sync complete. {$created} new task(s), {$updated} updated task(s)."
                );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('tasks.index')
                ->with(
                    'error',
                    'Canvas sync failed: '.$exception->getMessage()
                );
        }
    }

    private function cleanDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        return trim(
            html_entity_decode(
                strip_tags($description)
            )
        );
    }

    private function redirectUri(): string
    {
        return (string) config('services.canvas.redirect_uri') ?: route('canvas.callback');
    }
}
