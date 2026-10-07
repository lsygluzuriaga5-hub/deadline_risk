<div>
    <!-- You must be the change you wish to see in the world. - Mahatma Gandhi -->
</div>
@extends('layouts.risk')

@section('title', 'Courses')
@section('eyebrow', 'Academic workspace')
@section('heading', 'Courses')
@section('subtitle', 'Keep professor details and calculate your weighted grade for each course.')

@section('content')
    <style>
        .course-page { display: grid; gap: 18px; }
        .course-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 420px), 1fr)); gap: 16px; }
        .course-card { min-width: 0; padding: 20px; border: 1px solid var(--panel-line); border-radius: 10px; background: var(--panel); }
        .course-card h2, .course-create h2 { margin: 0; font-family: 'Raleway', sans-serif; font-size: 18px; }
        .course-card > p, .course-create > p { margin: 6px 0 16px; color: var(--muted); font-size: 11px; line-height: 1.6; }
        .course-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .course-fields .field { margin: 0; }
        .course-fields .field input { min-height: 40px; }
        .grade-summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 12px 0 16px; padding: 12px; border: 1px solid var(--panel-line); border-radius: 8px; background: color-mix(in srgb, var(--teal) 7%, var(--panel)); }
        .grade-summary strong { color: var(--teal); font-family: 'Raleway', sans-serif; font-size: 22px; }
        .grade-summary small { display: block; margin-top: 3px; color: var(--muted); font-size: 10px; }
        .course-task-count { color: var(--muted); font-size: 11px; text-align: right; }
        .grade-component-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 16px 0 8px; }
        .grade-component-heading strong { font-size: 12px; }
        .grade-component-list { display: grid; gap: 8px; }
        .grade-component { display: grid; grid-template-columns: minmax(90px, 1fr) 74px 74px auto; align-items: end; gap: 8px; padding: 10px; border: 1px solid var(--panel-line); border-radius: 7px; }
        .grade-component .field { margin: 0; }
        .grade-component .field label { font-size: 10px; }
        .grade-component .field input { min-height: 36px; padding: 7px 8px; }
        .component-remove { min-height: 36px; padding: 0 9px; border: 1px solid var(--panel-line); border-radius: 6px; background: transparent; color: var(--red); cursor: pointer; font-size: 10px; }
        .weight-total { display: flex; justify-content: space-between; margin: 8px 0 4px; color: var(--muted); font-size: 10px; }
        .weight-total.is-valid { color: var(--green); }
        .course-form-actions { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; margin-top: 14px; }
        .course-form-actions form { margin: 0; }
        .course-empty { padding: 24px; border: 1px dashed var(--panel-line); border-radius: 9px; color: var(--muted); font-size: 12px; text-align: center; }
        .course-create { padding: 20px; border: 1px solid var(--panel-line); border-radius: 10px; background: var(--panel); }
        .course-help { margin-top: 10px; color: var(--muted); font-size: 10px; line-height: 1.55; }
        @media (max-width: 560px) { .course-fields { grid-template-columns: 1fr; } .grade-component { grid-template-columns: minmax(0, 1fr) 65px 65px auto; gap: 5px; padding: 7px; } .component-remove { padding: 0 5px; } }
    </style>

    <div class="course-page">
        @if ($courses->isEmpty())
            <div class="course-empty">No courses added yet. Add a course below to record its professor and set up your grading weights.</div>
        @else
            <div class="course-grid">
                @foreach ($courses as $course)
                    @php($currentGrade = $course->currentGrade())
                    <article class="course-card">
                        <form method="POST" action="{{ route('courses.update', $course) }}" data-course-form>
                            @csrf
                            @method('PUT')
                            <div class="course-fields">
                                <div class="field"><label for="course-name-{{ $course->id }}">Course name</label><input id="course-name-{{ $course->id }}" name="name" maxlength="100" value="{{ $course->name }}" required></div>
                                <div class="field"><label for="course-professor-{{ $course->id }}">Professor</label><input id="course-professor-{{ $course->id }}" name="professor" maxlength="120" value="{{ $course->professor }}" placeholder="Professor name"></div>
                            </div>

                            <div class="grade-summary">
                                <div><strong>{{ $currentGrade !== null ? number_format($currentGrade, 2).'%' : '—' }}</strong><small>{{ $currentGrade !== null ? 'Current weighted grade from scored categories' : 'Add scores to calculate your current grade' }}</small></div>
                                <div class="course-task-count">{{ $course->tasks_count }} {{ Str::plural('task', $course->tasks_count) }}</div>
                            </div>

                            <div class="grade-component-heading"><strong>Grading breakdown</strong><button class="settings-action" type="button" data-add-component>Add category</button></div>
                            <div class="grade-component-list" data-component-list data-next-index="{{ count($course->grading_components ?? []) }}">
                                @foreach ($course->grading_components ?? [] as $index => $component)
                                    <div class="grade-component" data-component>
                                        <div class="field"><label>Category</label><input name="grading_components[{{ $index }}][name]" value="{{ $component['name'] }}" maxlength="60" required></div>
                                        <div class="field"><label>Weight %</label><input name="grading_components[{{ $index }}][weight]" type="number" min="0.01" max="100" step="0.01" value="{{ $component['weight'] }}" required data-weight></div>
                                        <div class="field"><label>Score %</label><input name="grading_components[{{ $index }}][score]" type="number" min="0" max="100" step="0.01" value="{{ $component['score'] }}" placeholder="—"></div>
                                        <button class="component-remove" type="button" data-remove-component aria-label="Remove category">Remove</button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="weight-total" data-weight-total><span>Weights must total 100%</span><span data-weight-value>0%</span></div>
                            <p class="course-help">Enter each grading category’s weight. Add a score when it is available; the current grade is calculated from scored categories only.</p>

                            <div class="course-form-actions">
                                <button class="button small" type="submit">Save course &amp; grades</button>
                        </form>
                                <form method="POST" action="{{ route('courses.destroy', $course) }}" onsubmit="return confirm('Remove this course? Existing tasks will keep their course labels.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button secondary small" type="submit">Remove course</button>
                                </form>
                            </div>
                    </article>
                @endforeach
            </div>
        @endif

        <section class="course-create" id="new-course" aria-labelledby="new-course-title">
            <h2 id="new-course-title">Add a course</h2>
            <p>Import your Canvas courses and professor details, or create a course manually.</p>
            <form method="POST" action="{{ route('courses.canvas-sync') }}" style="margin-bottom:14px;">@csrf<button class="button secondary" type="submit">Import from Canvas</button></form>
            <form method="POST" action="{{ route('courses.store') }}" data-course-form>
                @csrf
                <div class="course-fields">
                    <div class="field"><label for="new-course-name">Course name</label><input id="new-course-name" name="name" maxlength="100" value="{{ old('name') }}" placeholder="e.g. Web Developer" required></div>
                    <div class="field"><label for="new-course-professor">Professor</label><input id="new-course-professor" name="professor" maxlength="120" value="{{ old('professor') }}" placeholder="Professor name"></div>
                </div>
                <div class="grade-component-heading"><strong>Grading breakdown</strong><button class="settings-action" type="button" data-add-component>Add category</button></div>
                <div class="grade-component-list" data-component-list data-next-index="0"></div>
                <div class="weight-total" data-weight-total><span>Weights must total 100% when categories are added</span><span data-weight-value>0%</span></div>
                <p class="course-help">For example: Assignments 30%, Quizzes 20%, Midterm 20%, Final 30%. Weights must add up to 100%. Scores are entered as percentages.</p>
                <button class="button small" type="submit">Add course</button>
            </form>
        </section>
    </div>

    <template id="grade-component-template">
        <div class="grade-component" data-component>
            <div class="field"><label>Category</label><input data-name-field maxlength="60" required placeholder="e.g. Quizzes"></div>
            <div class="field"><label>Weight %</label><input data-weight-field type="number" min="0.01" max="100" step="0.01" required placeholder="25" data-weight></div>
            <div class="field"><label>Score %</label><input data-score-field type="number" min="0" max="100" step="0.01" placeholder="—"></div>
            <button class="component-remove" type="button" data-remove-component aria-label="Remove category">Remove</button>
        </div>
    </template>

    <script>
        (() => {
            const template = document.getElementById('grade-component-template');

            document.querySelectorAll('[data-course-form]').forEach((form) => {
                const list = form.querySelector('[data-component-list]');
                const totalRow = form.querySelector('[data-weight-total]');
                const totalValue = totalRow.querySelector('[data-weight-value]');
                let nextIndex = Number(list.dataset.nextIndex || 0);

                const updateWeightTotal = () => {
                    const total = [...list.querySelectorAll('[data-weight]')]
                        .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
                    totalValue.textContent = `${total.toFixed(2).replace(/\.00$/, '')}%`;
                    totalRow.classList.toggle('is-valid', list.children.length > 0 && Math.abs(total - 100) < 0.01);
                };

                form.querySelector('[data-add-component]').addEventListener('click', () => {
                    const component = template.content.firstElementChild.cloneNode(true);
                    const index = nextIndex++;
                    component.querySelector('[data-name-field]').name = `grading_components[${index}][name]`;
                    component.querySelector('[data-weight-field]').name = `grading_components[${index}][weight]`;
                    component.querySelector('[data-score-field]').name = `grading_components[${index}][score]`;
                    list.append(component);
                    updateWeightTotal();
                    component.querySelector('[data-name-field]').focus();
                });

                list.addEventListener('input', updateWeightTotal);
                list.addEventListener('click', (event) => {
                    if (event.target.closest('[data-remove-component]')) {
                        event.target.closest('[data-component]').remove();
                        updateWeightTotal();
                    }
                });

                updateWeightTotal();
            });
        })();
    </script>
@endsection
