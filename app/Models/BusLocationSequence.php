<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Location;
use App\Models\BusLocationSequence;

class BusLocationSequence extends Model
{
    use HasFactory;
    protected $table = 'bus_location_sequence';
    protected $fillable = ['bus_id','location_id', 'sequence'];
                            
      public function bus()
      {
            return $this->belongsTo(Bus::class);
      }
      public function location()
      {
            return $this->belongsTo(Location::class);
      }
}
