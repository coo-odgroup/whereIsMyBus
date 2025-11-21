<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Bus;
use App\Models\User;
use App\Models\PreBookingDetail;
class PreBooking extends Model
{
    use HasFactory;
    protected $table = 'pre_booking';
    protected $fillable = ['transaction_id','user_id','bus_id','j_day','journey_dt','bus_info',
                            'customer_info','total_fare','is_coupon','coupon_code','coupon_discount',
                            'discounted_fare','customer_id','created_by'];
                  

     public function bus()
      {
            return $this->belongsTo(Bus::class);
      }

      public function user()
      {
            return $this->belongsTo(User::class);
      }

      public function preBookingDetail()
    {
        return $this->hasMany(PreBookingDetail::class);   
    } 

 }



