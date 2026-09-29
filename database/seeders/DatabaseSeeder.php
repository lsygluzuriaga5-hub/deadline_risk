<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $tasks = [
            ['title' => 'Finalize project brief', 'description' => 'Review scope and send the final brief.', 'days' => 2, 'status' => 'in_progress', 'progress' => 40, 'estimated_hours' => 8, 'priority' => 'high', 'course' => 'Web Development'],
            ['title' => 'Prepare client presentation', 'description' => 'Assemble the slides and supporting notes.', 'days' => 3, 'status' => 'pending', 'progress' => 25, 'estimated_hours' => 5, 'priority' => 'normal', 'course' => 'Communications'],
            ['title' => 'Review campaign assets', 'description' => 'Check the final images and copy.', 'days' => 5, 'status' => 'pending', 'progress' => 80, 'estimated_hours' => 2, 'priority' => 'low', 'course' => 'Design Studio'],
            ['title' => 'Publish weekly report', 'description' => 'Publish the approved performance report.', 'days' => 7, 'status' => 'in_progress', 'progress' => 55, 'estimated_hours' => 4, 'priority' => 'normal', 'course' => 'Data Analysis'],
            ['title' => 'Update contract archive', 'description' => 'Move signed contracts into the archive.', 'days' => 9, 'status' => 'pending', 'progress' => 10, 'estimated_hours' => 3, 'priority' => 'normal', 'course' => 'Business'],
            ['title' => 'Close outstanding tickets', 'description' => 'Resolve the remaining support tickets.', 'days' => 10, 'status' => 'pending', 'progress' => 35, 'estimated_hours' => 6, 'priority' => 'high', 'course' => 'Project Lab'],
            ['title' => 'Complete onboarding checklist', 'description' => 'Finish the onboarding tasks for the new hire.', 'days' => -1, 'status' => 'completed', 'progress' => 100, 'estimated_hours' => 2, 'priority' => 'low', 'course' => 'Orientation'],
        ];

        foreach ($tasks as $task) {
            $deadline = now()->addDays($task['days'])->toDateString();
            $task['user_id'] = $user->id;
            unset($task['days']);
            Task::updateOrCreate(
                ['user_id' => $user->id, 'title' => $task['title']],
                [
                    ...$task,
                    'deadline' => $deadline,
                ],
            );
        }
    }
}
