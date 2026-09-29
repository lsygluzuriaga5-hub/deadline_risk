<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'professor',
        'grading_components',
    ];

    protected function casts(): array
    {
        return [
            'grading_components' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function currentGrade(): ?float
    {
        $gradedComponents = collect($this->grading_components ?? [])
            ->filter(fn (array $component): bool => $component['score'] !== null && $component['score'] !== '')
            ->values();

        $gradedWeight = $gradedComponents->sum('weight');

        if ($gradedWeight <= 0) {
            return null;
        }

        $weightedScore = $gradedComponents->sum(
            fn (array $component): float => (float) $component['score'] * (float) $component['weight']
        );

        return round($weightedScore / $gradedWeight, 2);
    }
}
