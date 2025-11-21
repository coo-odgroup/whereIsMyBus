<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusSeatLayout;
use App\Models\SeatClass;
use App\Models\BusSeats;
use App\Models\SeatOpenSeats;

class Seats extends Model
{
    use HasFactory;
    protected $table = 'seats';
    protected $fillable = ['bus_seat_layout_id','seat_class_id','seatText','rowNumber','colNumber','berthType'];
    public function BusSeatLayout()
    {
    	return $this->belongsTo(BusSeatLayout::class);
    }
    protected $hidden = [
        'created_at',
        'updated_at',
        'created_by',
        'status',
    ];

    public function busSeats()
    {
    	return $this->hasOne(BusSeats::class)->withDefault(function () {
            return (object)[];
            //return new BusSeats();
        });
    }
    public function seatClass()
    {
    	return $this->belongsTo(SeatClass::class);
    }
    public function seatOpenSeats()
    { 
        return $this->hasOne(SeatOpenSeats::class)->withDefault(function () {
            return (object)[];
        });
    }

}
