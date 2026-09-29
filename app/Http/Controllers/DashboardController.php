<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskRiskAssessment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** @var array<string, array{name: string, category: string}> */
    private const HOLIDAYS_2026 = [
        '2026-01-01' => ['name' => "New Year's Day", 'category' => 'regular'],
        '2026-02-17' => ['name' => 'Chinese New Year', 'category' => 'special'],
        '2026-02-25' => ['name' => 'EDSA People Power Revolution Anniversary', 'category' => 'working'],
        '2026-04-02' => ['name' => 'Maundy Thursday', 'category' => 'regular'],
        '2026-04-03' => ['name' => 'Good Friday', 'category' => 'regular'],
        '2026-04-04' => ['name' => 'Black Saturday', 'category' => 'special'],
        '2026-04-09' => ['name' => 'Araw ng Kagitingan', 'category' => 'regular'],
        '2026-05-01' => ['name' => 'Labor Day', 'category' => 'regular'],
        '2026-06-12' => ['name' => 'Independence Day', 'category' => 'regular'],
        '2026-08-21' => ['name' => 'Ninoy Aquino Day', 'category' => 'special'],
        '2026-08-31' => ['name' => 'National Heroes Day', 'category' => 'regular'],
        '2026-11-01' => ['name' => "All Saints' Day", 'category' => 'special'],
        '2026-11-02' => ['name' => "All Souls' Day", 'category' => 'special'],
        '2026-11-30' => ['name' => 'Bonifacio Day', 'category' => 'regular'],
        '2026-12-08' => ['name' => 'Feast of the Immaculate Conception of Mary', 'category' => 'special'],
        '2026-12-24' => ['name' => 'Christmas Eve', 'category' => 'special'],
        '2026-12-25' => ['name' => 'Christmas Day', 'category' => 'regular'],
        '2026-12-30' => ['name' => 'Rizal Day', 'category' => 'regular'],
        '2026-12-31' => ['name' => 'Last Day of the Year', 'category' => 'special'],
    ];

    public function index(Request $request, TaskRiskAssessment $riskAssessment): View
    {
        $tasks = Task::where('user_id', $request->user()->id)->orderBy('deadline')->get();
        $summary = $riskAssessment->summarize($tasks);
        $activeTasks = $tasks->where('status', '!=', 'completed');
        $focusTasks = $this->prioritize($activeTasks, $summary['assessments'])->take(3);
        $insightTask = $focusTasks->first();
        $todayTasks = $activeTasks->filter(fn (Task $task): bool => $summary['assessments'][$task->id]['days_remaining'] <= 0)->values();
        $upcomingTasks = $activeTasks
            ->filter(fn (Task $task): bool => $summary['assessments'][$task->id]['days_remaining'] > 0 && $summary['assessments'][$task->id]['days_remaining'] <= 14)
            ->sortBy(fn (Task $task): int => $task->deadline?->timestamp ?? PHP_INT_MAX)
            ->values();
        $dueTodayCount = $activeTasks->filter(fn (Task $task): bool => $summary['assessments'][$task->id]['days_remaining'] === 0)->count();
        $dueDateCounts = $activeTasks->countBy(fn (Task $task): string => $task->deadline?->toDateString() ?? '');
        $attentionTasks = $activeTasks->filter(function (Task $task) use ($summary): bool {
            $assessment = $summary['assessments'][$task->id];

            return $assessment['overdue']
                || $assessment['level'] === 'high'
                || $assessment['days_remaining'] <= 3
                || ((int) $task->progress === 0 && $assessment['days_remaining'] <= 10)
                || ($assessment['remaining_hours'] >= 8 && $assessment['days_remaining'] <= 7);
        })->values();
        $attentionAlerts = $attentionTasks->map(function (Task $task) use ($dueDateCounts, $summary): array {
            $assessment = $summary['assessments'][$task->id];
            $reasons = [];

            if ($assessment['overdue']) {
                $reasons[] = 'Overdue';
            } elseif ($assessment['days_remaining'] <= 3) {
                $reasons[] = 'Deadline approaching';
            }

            if ((int) $task->progress === 0) {
                $reasons[] = 'Task not started';
            }

            if ($assessment['remaining_hours'] >= 8) {
                $reasons[] = 'Large workload';
            }

            if (($dueDateCounts[$task->deadline?->toDateString() ?? ''] ?? 0) > 1) {
                $reasons[] = 'Multiple assignments due the same day';
            }

            return ['task' => $task, 'assessment' => $assessment, 'reasons' => $reasons];
        })->take(4)->values();
        $courseDetails = $request->user()->courses()->get()->keyBy('name');
        $courseProgress = $tasks
            ->filter(fn (Task $task): bool => filled($task->course))
            ->groupBy(fn (Task $task): string => trim($task->course))
            ->map(function ($courseTasks, string $course) use ($courseDetails): array {
                $courseRecord = $courseDetails->get($course);

                return [
                    'name' => $course,
                    'count' => $courseTasks->count(),
                    'progress' => (int) round($courseTasks->avg('progress')),
                    'completed' => $courseTasks->where('status', 'completed')->count(),
                    'professor' => $courseRecord?->professor,
                    'grade' => $courseRecord?->currentGrade(),
                ];
            })
            ->sortKeys()
            ->values();
        $copilotTasks = $activeTasks->map(fn (Task $task): array => [
            'title' => $task->title,
            'course' => $task->course,
            'deadline' => $task->deadline?->format('M j'),
            'days_remaining' => $summary['assessments'][$task->id]['days_remaining'],
            'progress' => (int) $task->progress,
            'priority' => $task->priority,
            'risk' => $summary['assessments'][$task->id]['level'],
            'overdue' => $summary['assessments'][$task->id]['overdue'],
            'remaining_hours' => $summary['assessments'][$task->id]['remaining_hours'],
            'reason' => $summary['assessments'][$task->id]['reason'],
            'recommendation' => $summary['assessments'][$task->id]['recommendation'],
        ])->values();
        $productivitySummary = match (true) {
            $activeTasks->isEmpty() => 'You’re clear for now. Add an assignment when something new lands.',
            $dueTodayCount > 0 => $dueTodayCount.' '.($dueTodayCount === 1 ? 'task is' : 'tasks are').' due today. Start with '.$focusTasks->first()->title.'.',
            $attentionTasks->isNotEmpty() => $attentionTasks->count().' '.($attentionTasks->count() === 1 ? 'assignment needs' : 'assignments need').' attention. '.$focusTasks->first()->title.' is your best next step.',
            default => 'You’re in a steady spot. Keep momentum on '.$focusTasks->first()->title.'.',
        };
        $activity = collect(range(0, 6))->map(function (int $offset) use ($tasks, $summary): array {
            $date = today()->addDays($offset);
            $dueTasks = $tasks->filter(fn (Task $task): bool => $task->deadline?->isSameDay($date));
            $levels = ['high' => 0, 'medium' => 0, 'low' => 0, 'completed' => 0];

            foreach ($dueTasks as $task) {
                $key = $task->status === 'completed' ? 'completed' : $summary['assessments'][$task->id]['level'];
                $levels[$key]++;
            }

            return [
                'date' => $date,
                'label' => $date->format('M j'),
                'tasks' => $dueTasks,
                'levels' => $levels,
                'total' => $dueTasks->count(),
            ];
        });

        return view('dashboard.overview', [
            'tasks' => $tasks,
            'summary' => $summary,
            'focusTasks' => $focusTasks,
            'insightTask' => $insightTask,
            'insight' => $insightTask ? $summary['assessments'][$insightTask->id] : null,
            'todayTasks' => $todayTasks,
            'upcomingTasks' => $upcomingTasks,
            'dueTodayCount' => $dueTodayCount,
            'attentionAlerts' => $attentionAlerts,
            'courseProgress' => $courseProgress,
            'copilotTasks' => $copilotTasks,
            'productivitySummary' => $productivitySummary,
            'activity' => $activity,
            'chartMax' => max(1, $activity->max('total')),
            'notifications' => $summary['notifications'],
        ]);
    }

    public function calendar(Request $request, TaskRiskAssessment $riskAssessment): View
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'date_format:Y-m'],
        ]);
        $month = Carbon::createFromFormat('!Y-m', $validated['month'] ?? today()->format('Y-m'));
        $calendarStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $tasks = Task::where('user_id', $request->user()->id)
            ->whereBetween('deadline', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('deadline')
            ->get();
        $summary = $riskAssessment->summarize($tasks);
        $tasksByDate = $tasks->groupBy(fn (Task $task): string => $task->deadline->toDateString());
        $days = collect();

        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date->addDay()) {
            $days->push([
                'date' => $date->copy(),
                'tasks' => $tasksByDate->get($date->toDateString(), collect()),
                'holiday' => self::HOLIDAYS_2026[$date->toDateString()] ?? null,
            ]);
        }

        return view('calendar', [
            'month' => $month,
            'days' => $days->chunk(7),
            'assessments' => $summary['assessments'],
            'notifications' => $summary['notifications'],
        ]);
    }

    public function report(Request $request, TaskRiskAssessment $riskAssessment): View
    {
        $tasks = Task::where('user_id', $request->user()->id)->orderBy('deadline')->get();
        $summary = $riskAssessment->summarize($tasks);
        $activeTasks = $tasks->where('status', '!=', 'completed');
        $assessments = $summary['assessments'];
        $dueSoon = $activeTasks->filter(fn (Task $task): bool => $assessments[$task->id]['days_remaining'] >= 0 && $assessments[$task->id]['days_remaining'] <= 3)->count();
        $highBelowHalf = $activeTasks->filter(fn (Task $task): bool => $assessments[$task->id]['level'] === 'high' && (int) $task->progress < 50)->count();
        $topRiskTask = $this->prioritize($activeTasks, $assessments)->first();
        $recommendations = [];

        if ($dueSoon > 0) {
            $recommendations[] = $dueSoon.' '.($dueSoon === 1 ? 'task has' : 'tasks have').' a deadline within the next 3 days.';
        }

        if ($highBelowHalf > 0) {
            $recommendations[] = $highBelowHalf.' high-risk '.($highBelowHalf === 1 ? 'task has' : 'tasks have').' less than 50% completion.';
        }

        if ($topRiskTask) {
            $recommendations[] = 'Prioritize '.$topRiskTask->title.' next; it has the highest current deadline risk.';
        }

        if ($recommendations === []) {
            $recommendations[] = 'No immediate deadline pressure found. Keep progress estimates up to date.';
        }

        return view('risk-report', [
            'tasks' => $tasks,
            'summary' => $summary,
            'assessments' => $assessments,
            'recommendations' => $recommendations,
            'notifications' => $summary['notifications'],
        ]);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  array<int, array>  $assessments
     * @return Collection<int, Task>
     */
    private function prioritize(Collection $tasks, array $assessments): Collection
    {
        return $tasks->sortBy(fn (Task $task): array => [
            ['high' => 0, 'medium' => 1, 'low' => 2][$assessments[$task->id]['level']],
            ['high' => 0, 'normal' => 1, 'low' => 2][$task->priority],
            $task->deadline?->timestamp ?? PHP_INT_MAX,
        ])->values();
    }
}
