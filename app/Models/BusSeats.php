<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Seats;
use App\Models\TicketPrice;


class BusSeats extends Model
{
    use HasFactory;
    protected $table = 'bus_seats';
    protected $fillable = ['bus_id','ticket_price_id','seats_id','category','bookStatus','duration','newfare','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    public function seats()
    {
    	return $this->belongsTo(Seats::class);
    }

    public function TicketPrice()
    {
    	return $this->belongsTo(TicketPrice::class);
    }
    
}
