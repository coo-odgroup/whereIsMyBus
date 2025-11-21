<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seats;
use App\Models\SeatOpen;

class SeatOpenSeats extends Model
{
    use HasFactory;
    protected $table = 'seat_open_seats';
    protected $fillable = ['seat_open_id','seats_id'];

	public function seats()
    {
    	return $this->belongsTo(Seats::class);
    }
    // public function seatOpen()
    // {
    //     return $this->belongsTo(SeatOpen::class)->withDefault(function () {
    //         return (object)[];
    //     });
    // 	//return $this->belongsTo(SeatOpen::class);
    // }
    public function seatOpen(){
        if(empty($this->seat_open_id)){
            return $this->belongsTo(SeatOpen::class)->withDefault();
        }
    }
   
}

