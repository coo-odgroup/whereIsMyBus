<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PreBooking;
use App\Models\Bus;

class PreBookingDetail extends Model
{
    use HasFactory;
    protected $table = 'pre_booking_detail';
    protected $fillable = ['pre_booking_id','journey_date','j_day','bus_id','seat_name','created_by'];
    public function PreBooking()
      {
            return $this->belongsTo(PreBooking::class);
      }
      public function Bus()
      {
            return $this->belongsTo(Bus::class);
      } 
       
                            
}
