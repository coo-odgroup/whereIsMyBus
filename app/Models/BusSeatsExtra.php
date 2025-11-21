<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;

class BusSeatsExtra extends Model
{
    use HasFactory;
    protected $table = 'bus_seats_extra';
    protected $fillable = ['bus_id','journey_dt','type','seat_type','seat_number','created_by'];
    public function Bus()
    {
    	return $this->belongsTo(Bus::class);
    }
}
