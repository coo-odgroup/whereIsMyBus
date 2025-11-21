<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusClosingHours extends Model
{
    use HasFactory;
    protected $table = 'bus_closing_hours';
    protected $fillable = [ 
        'bus_id', 'city_id', 'dep_time','closing_hours'
    ];
}
