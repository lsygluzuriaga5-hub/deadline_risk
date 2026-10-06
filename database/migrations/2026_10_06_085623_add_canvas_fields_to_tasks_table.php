<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('tasks', 'source')) {
                $table->string('source')->default('manual')->after('course');
            }

            if (! Schema::hasColumn('tasks', 'canvas_assignment_id')) {
                $table->string('canvas_assignment_id')->nullable()->after('source');
            }

            if (! Schema::hasColumn('tasks', 'canvas_course_id')) {
                $table->string('canvas_course_id')->nullable()->after('canvas_assignment_id');
            }

            if (! Schema::hasColumn('tasks', 'canvas_due_at')) {
                $table->dateTime('canvas_due_at')->nullable()->after('canvas_course_id');
            }

            if (! Schema::hasColumn('tasks', 'canvas_url')) {
                $table->text('canvas_url')->nullable()->after('canvas_due_at');
            }

            $table->index(['user_id', 'canvas_assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_user_id_canvas_assignment_id_index');
            $table->dropColumn([
                'source',
                'canvas_assignment_id',
                'canvas_course_id',
                'canvas_due_at',
                'canvas_url',
            ]);
        });
    }
};
