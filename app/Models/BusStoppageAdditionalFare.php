<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TicketPrice;

use App\Models\BusSeats;

class BusStoppageAdditionalFare extends Model
{
    use HasFactory;
    protected $table = 'bus_stoppage_additional_fare';
    protected $fillable = ['bus_stoppage_id','bus_seats_id','additional_fare','created_by'];

    public function BusStoppage()
    {
    	return $this->belongsTo(TicketPrice::class);
    }
    public function BusSeats()
    {
    	return $this->belongsTo(BusSeats::class);
    }
    
}
