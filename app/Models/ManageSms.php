<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;

class ManageSms extends Model
{
    use HasFactory;
    protected $table = 'manage_sms';
    protected $fillable = ['pnr','booking_id','sms_engine','type','status',
                            'from','to','contents','response ','message_id',
                            'is_engine_verification','engine_verification_sts','engine_verification_info'];

      

      public function manageSms()
      {
            return $this->hasMany(ManageSms::class);   
      } 
      

}
