<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'professional_id',
        'available_date',
        'start_time',
        'end_time',
        'is_booked',
    ];
}
