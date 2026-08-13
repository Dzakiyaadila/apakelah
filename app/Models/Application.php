<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'user_id', 'job_id', 'company', 'position', 'applied_date',
        'deadline', 'type', 'contact', 'location', 'status',
    ];

    protected $casts = [
        'applied_date' => 'date',
        'deadline' => 'date',
    ];
}