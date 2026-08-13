<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CvAnalysis extends Model
{
    protected $fillable = [
        'cv_id', 'job_id', 'ats_score', 'headline',
        'strengths', 'improvements', 'suggestions', 'keyword_gaps',
    ];

    protected $casts = [
        'strengths' => 'array',
        'improvements' => 'array',
        'suggestions' => 'array',
        'keyword_gaps' => 'array',
    ];
}