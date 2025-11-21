<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\BusSchedule;

class BusScheduleDate extends Model
{
    use HasFactory;
    protected $table = 'bus_schedule_date';
    protected $fillable = ['bus_schedule_id','entry_date'];
    public function busSchedule()
    {
    	return $this->belongsTo(BusSchedule::class);
    }
}