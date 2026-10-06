<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskRiskAssessment
{
    /**
     * @return array{level: string, score: int, overdue: bool, days_remaining: int|null, remaining_hours: float, reason: string, recommendation: string}
     */
    public function assess(Task $task): array
    {
        $daysRemaining = $task->deadline === null
            ? null
            : (int) today()->diffInDays($task->deadline, false);
        $remainingHours = round((float) ($task->estimated_hours ?? 0) * (100 - (int) $task->progress) / 100, 1);
        $overdue = $daysRemaining < 0 && $task->status !== 'completed';

        if ($task->status === 'completed') {
            return [
                'level' => 'low',
                'score' => 0,
                'overdue' => false,
                'days_remaining' => $daysRemaining,
                'remaining_hours' => 0.0,
                'reason' => 'This task is complete, so it no longer carries deadline risk.',
                'recommendation' => 'No action needed. Keep the completed work for reference.',
            ];
        }

        if ($overdue) {
            return [
                'level' => 'high',
                'score' => 100,
                'overdue' => true,
                'days_remaining' => $daysRemaining,
                'remaining_hours' => $remainingHours,
                'reason' => 'The deadline passed '.abs($daysRemaining).' '.(abs($daysRemaining) === 1 ? 'day' : 'days').' ago and the task is still open.',
                'recommendation' => 'Review the deadline and complete the next unfinished step today.',
            ];
        }

        $score = match (true) {
            $daysRemaining !== null && $daysRemaining <= 1 => 35,
            $daysRemaining !== null && $daysRemaining <= 3 => 25,
            $daysRemaining !== null && $daysRemaining <= 7 => 15,
            default => 0,
        };
        $score += match (true) {
            (int) $task->progress < 25 => 30,
            (int) $task->progress < 50 => 20,
            (int) $task->progress < 75 => 10,
            default => 0,
        };
        $score += match (true) {
            $remainingHours >= 8 => 20,
            $remainingHours >= 4 => 12,
            $remainingHours >= 2 => 6,
            default => 0,
        };
        $score += match ($task->priority) {
            'high' => 15,
            'low' => -10,
            default => 0,
        };

        $level = $score >= 55 ? 'high' : ($score >= 30 ? 'medium' : 'low');
        $deadlinePhrase = $daysRemaining === null
            ? 'no deadline set'
            : ($daysRemaining === 0
                ? 'due today'
                : 'due in '.$daysRemaining.' '.($daysRemaining === 1 ? 'day' : 'days'));
        $reason = ucfirst($deadlinePhrase).', with '.(int) $task->progress.'% complete and about '.$remainingHours.' hours of work remaining.';

        if ($task->priority === 'high') {
            $reason .= ' This task is marked high priority.';
        }

        $hoursToday = max(1, (int) ceil(min(3, $remainingHours)));
        $recommendation = $remainingHours > 0
            ? 'Start today and reserve about '.$hoursToday.' '.($hoursToday === 1 ? 'hour' : 'hours').' for the next work session.'
            : 'Review the task and update its progress to keep the estimate accurate.';

        return [
            'level' => $level,
            'score' => max(0, min(100, $score)),
            'overdue' => false,
            'days_remaining' => $daysRemaining,
            'remaining_hours' => $remainingHours,
            'reason' => $reason,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array{assessments: array<int, array>, counts: array{high: int, medium: int, low: int, overdue: int, completed: int, open: int}, exposure: int, notifications: array<int, array>}
     */
    public function summarize(Collection $tasks): array
    {
        $assessments = $tasks->mapWithKeys(fn (Task $task): array => [
            $task->id => $this->assess($task),
        ])->all();
        $openTasks = $tasks->where('status', '!=', 'completed');
        $counts = [
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'overdue' => 0,
            'completed' => $tasks->where('status', 'completed')->count(),
            'open' => $openTasks->count(),
        ];
        $exposureScores = ['high' => 100, 'medium' => 60, 'low' => 20];
        $exposureTotal = 0;
        $notifications = [];

        foreach ($tasks as $task) {
            $assessment = $assessments[$task->id];

            if ($task->status !== 'completed') {
                $counts[$assessment['level']]++;
                $counts['overdue'] += (int) $assessment['overdue'];
                $exposureTotal += $exposureScores[$assessment['level']];
            }

            if ($task->status === 'completed' && $task->updated_at?->isToday()) {
                $notifications[] = [
                    'id' => 'completed-'.$task->id,
                    'level' => 'low',
                    'message' => $task->title.' was completed.',
                    'task_id' => $task->id,
                ];
            } elseif ($assessment['overdue']) {
                $notifications[] = [
                    'id' => 'overdue-'.$task->id,
                    'level' => 'high',
                    'message' => $task->title.' is overdue.',
                    'task_id' => $task->id,
                ];
            } elseif ($assessment['days_remaining'] !== null && $assessment['days_remaining'] <= 3) {
                $notifications[] = [
                    'id' => 'due-'.$task->id,
                    'level' => $assessment['level'],
                    'message' => $task->title.' is due '.($assessment['days_remaining'] === 0 ? 'today' : 'in '.$assessment['days_remaining'].' '.($assessment['days_remaining'] === 1 ? 'day' : 'days')).'.',
                    'task_id' => $task->id,
                ];
            }
        }

        return [
            'assessments' => $assessments,
            'counts' => $counts,
            'exposure' => $counts['open'] === 0 ? 0 : (int) round($exposureTotal / $counts['open']),
            'notifications' => array_slice($notifications, 0, 8),
        ];
    }
}
