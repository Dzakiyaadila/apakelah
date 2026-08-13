<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'job_listings';

    protected $fillable = [
        'title', 'company', 'location', 'type', 'work_mode',
        'description', 'required_skills', 'salary_range', 'logo_url', 'posted_at',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'posted_at' => 'datetime',
    ];
}