<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeOffRequest extends Model
{
    protected $fillable = [
        'full_id',
        'full_name',
        'date_submitted',
        'time_off_type',
        'day1','day2','day3','day4','day5','day6','day7',
        'acceptance_rejection',
    ];

    protected $casts = [
        'date_submitted' => 'datetime',
        'day1' => 'date',
        'day2' => 'date',
        'day3' => 'date',
        'day4' => 'date',
        'day5' => 'date',
        'day6' => 'date',
        'day7' => 'date',
    ];
}
