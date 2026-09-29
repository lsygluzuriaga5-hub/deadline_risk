<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
        });

        $courseLabels = DB::table('tasks')
            ->select('user_id', 'course')
            ->whereNotNull('user_id')
            ->whereNotNull('course')
            ->distinct()
            ->get();

        foreach ($courseLabels as $courseLabel) {
            DB::table('courses')->insertOrIgnore([
                'user_id' => $courseLabel->user_id,
                'name' => $courseLabel->course,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $courseId = DB::table('courses')
                ->where('user_id', $courseLabel->user_id)
                ->where('name', $courseLabel->course)
                ->value('id');

            if ($courseId !== null) {
                DB::table('tasks')
                    ->where('user_id', $courseLabel->user_id)
                    ->where('course', $courseLabel->course)
                    ->update(['course_id' => $courseId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
