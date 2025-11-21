<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Bus;


class BusEXtraFare extends Model
{
    use HasFactory;
    protected $table = 'bus_extra_fare';
    protected $fillable = ['bus_id', 'type','journey_date','seat_fare','sleeper_fare','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    
}
