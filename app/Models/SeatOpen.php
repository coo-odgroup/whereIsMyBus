<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SeatOpenSeats;
use App\Models\Bus;
use App\Models\Seats;


class SeatOpen extends Model
{
    use HasFactory;
    protected $table = 'seat_open';
    protected $fillable = ['operator_id','bus_id','date_applied','reason'];


    public function seatOpenSeats()
    { 
    	 return $this->hasMany(SeatOpenSeats::class);
    }

    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    
}