<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtendedBusClosingHours extends Model
{
    use HasFactory;
    protected $table = 'extended_bus_closing_hours';
    protected $fillable = ['bus_id', 'city_id','dep_time', 'closing_hours'];
    
}
