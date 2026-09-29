<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'title',
        'description',
        'deadline',
        'status',
        'risk_level',
        'progress',
        'estimated_hours',
        'priority',
        'course',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'progress' => 'integer',
            'estimated_hours' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courseRecord(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
