<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;
use App\Models\BookingSequence;

class BookingSequence extends Model
{
    use HasFactory;
    protected $table = 'booking_sequence';
    protected $fillable = ['booking_id','sequence_start_no', 'sequence_end_no'];
                            
      public function booking()
      {
            return $this->belongsTo(Booking::class);
      }
}
