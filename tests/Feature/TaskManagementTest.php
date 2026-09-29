<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_task(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/tasks', [
                'title' => 'Launch homepage update',
                'description' => 'Refresh the landing page content',
                'deadline' => '2026-10-10',
                'status' => 'pending',
                'progress' => 20,
                'estimated_hours' => 8,
                'priority' => 'high',
                'course' => 'Web Development',
            ])
            ->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Launch homepage update',
            'status' => 'pending',
            'progress' => 20,
            'risk_level' => 'high',
        ]);
    }

    public function test_dashboard_explains_risk_from_deadline_progress_and_effort(): void
    {
        $this->travelTo('2026-09-28 09:00:00');
        $user = User::factory()->create();
        Task::create([
            'user_id' => $user->id,
            'title' => 'Project draft',
            'deadline' => today()->addDays(2),
            'status' => 'in_progress',
            'progress' => 40,
            'estimated_hours' => 8,
            'priority' => 'normal',
            'course' => 'Web Development',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('AI Risk Alert')
            ->assertSee('AI Copilot')
            ->assertSee('Deadline approaching')
            ->assertSee('Web Development');
    }

    public function test_dashboard_prioritizes_due_today_tasks_and_ai_copilot(): void
    {
        $this->travelTo('2026-09-28 09:00:00');
        $user = User::factory()->create();
        Task::create([
            'user_id' => $user->id,
            'title' => 'Submit lab report',
            'deadline' => today(),
            'status' => 'pending',
            'progress' => 0,
            'estimated_hours' => 6,
            'priority' => 'high',
            'course' => 'Biology',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('Today’s tasks')
            ->assertSee('Submit lab report')
            ->assertSee('AI Risk Alert')
            ->assertSee('AI Copilot')
            ->assertSee('Task not started')
            ->assertSee('Course progress');
    }

    public function test_calendar_shows_only_the_signed_in_students_tasks(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        Task::create([
            'user_id' => $user->id,
            'title' => 'My calendar task',
            'deadline' => '2026-10-03',
            'status' => 'pending',
            'progress' => 20,
            'priority' => 'normal',
        ]);
        Task::create([
            'user_id' => $anotherUser->id,
            'title' => 'Private calendar task',
            'deadline' => '2026-10-03',
            'status' => 'pending',
            'progress' => 20,
            'priority' => 'normal',
        ]);

        $this->actingAs($user)
            ->get(route('calendar'))
            ->assertSee('My calendar task')
            ->assertDontSee('Private calendar task');
    }

    public function test_user_can_mark_a_task_complete_and_progress_updates(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Finish reading notes',
            'deadline' => '2026-10-10',
            'status' => 'in_progress',
            'progress' => 65,
            'estimated_hours' => 2,
            'priority' => 'normal',
        ]);

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertRedirect(route('tasks.show', $task));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
            'progress' => 100,
            'risk_level' => 'low',
        ]);
    }

    public function test_task_filters_and_risk_report_use_calculated_risk(): void
    {
        $this->travelTo('2026-09-28 09:00:00');
        $user = User::factory()->create();
        Task::create([
            'user_id' => $user->id,
            'title' => 'Urgent research paper',
            'deadline' => today()->addDays(2),
            'status' => 'in_progress',
            'progress' => 20,
            'estimated_hours' => 8,
            'priority' => 'high',
        ]);
        Task::create([
            'user_id' => $user->id,
            'title' => 'Later reading list',
            'deadline' => today()->addDays(20),
            'status' => 'pending',
            'progress' => 90,
            'estimated_hours' => 1,
            'priority' => 'low',
        ]);

        $this->actingAs($user)
            ->get(route('tasks.index', ['risk' => 'high']))
            ->assertSee('Urgent research paper')
            ->assertDontSee('Later reading list');

        $this->get(route('risk-report'))
            ->assertSee('Risk distribution')
            ->assertSee('1 task has a deadline within the next 3 days.')
            ->assertSee('1 high-risk task has less than 50% completion.');
    }

    public function test_open_overdue_task_is_high_risk_and_explains_its_deadline(): void
    {
        $this->travelTo('2026-09-28 09:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Overdue lab report',
                'deadline' => '2026-09-27',
                'status' => 'in_progress',
                'progress' => 50,
                'estimated_hours' => 4,
                'priority' => 'normal',
            ])
            ->assertRedirect(route('tasks.index'));

        $task = Task::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'risk_level' => 'high',
        ]);

        $this->get(route('tasks.show', $task))
            ->assertSee('The deadline passed 1 day ago')
            ->assertSee('Review the deadline and complete the next unfinished step today.');
    }

    public function test_updating_task_progress_recalculates_its_risk_level(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Research notes',
            'deadline' => '2026-10-10',
            'status' => 'in_progress',
            'progress' => 20,
            'estimated_hours' => 8,
            'priority' => 'high',
        ]);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), [
                'title' => $task->title,
                'deadline' => '2026-10-10',
                'status' => 'in_progress',
                'progress' => 100,
                'estimated_hours' => 8,
                'priority' => 'high',
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'progress' => 100,
            'risk_level' => 'low',
        ]);
    }

    public function test_user_cannot_view_another_students_task(): void
    {
        $owner = User::factory()->create();
        $anotherStudent = User::factory()->create();
        $task = Task::create([
            'user_id' => $owner->id,
            'title' => 'Private coursework',
            'deadline' => '2026-10-10',
            'status' => 'pending',
            'progress' => 0,
            'priority' => 'normal',
        ]);

        $this->actingAs($anotherStudent)
            ->get('/dashboard')
            ->assertDontSee('Private coursework');

        $this->actingAs($anotherStudent)
            ->get(route('tasks.show', $task))
            ->assertNotFound();
    }
}
