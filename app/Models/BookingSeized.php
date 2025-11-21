<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Location;
use App\Models\TicketPrice;
use App\Models\Bus;


class BookingSeized extends Model
{
    use HasFactory;
    protected $table = 'daywise_booking_seized';
    protected $fillable = ['bus_id','ticket_price_id','seize_booking_minute','seized_date'];


    public function ticketPrice()
    {
    	 return $this->belongsTo(TicketPrice::class);
    }

    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    
}