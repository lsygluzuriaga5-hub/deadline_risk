<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = $request->user()->courses()->withCount('tasks')->orderBy('name')->get();

        return view('courses.index', [
            'courses' => $courses,
            'notifications' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCourse($request);
        $course = $request->user()->courses()->create($validated);
        $this->linkMatchingTasks($request, $course);

        return redirect()->route('courses.index')->with('success', 'Course added successfully.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwnership($request, $course);
        $validated = $this->validateCourse($request, $course);

        $course->tasks()->update(['course' => $validated['name']]);
        $course->update($validated);
        $this->linkMatchingTasks($request, $course);

        return redirect()->route('courses.index')->with('success', 'Course details and grades updated.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwnership($request, $course);
        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course removed. Its tasks keep their existing course labels.');
    }

    /**
     * @return array{name: string, professor: ?string, grading_components: ?array}
     */
    private function validateCourse(Request $request, ?Course $course = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('courses', 'name')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->ignore($course?->id),
            ],
            'professor' => ['nullable', 'string', 'max:120'],
            'grading_components' => ['nullable', 'array', 'max:12'],
            'grading_components.*.name' => ['required', 'string', 'max:60'],
            'grading_components.*.weight' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'grading_components.*.score' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $components = array_values($validated['grading_components'] ?? []);

        if ($components !== [] && abs(array_sum(array_column($components, 'weight')) - 100) > 0.01) {
            throw ValidationException::withMessages([
                'grading_components' => 'The grading weights must add up to 100%.',
            ]);
        }

        $validated['grading_components'] = $components === [] ? null : $components;

        return $validated;
    }

    private function ensureCourseOwnership(Request $request, Course $course): void
    {
        abort_unless($course->user_id === $request->user()->id, 404);
    }

    private function linkMatchingTasks(Request $request, Course $course): void
    {
        $request->user()->tasks()
            ->where('course', $course->name)
            ->whereNull('course_id')
            ->update(['course_id' => $course->id]);
    }
}
