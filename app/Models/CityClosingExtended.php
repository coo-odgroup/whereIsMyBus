<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Location;

class CityClosingExtended extends Model
{
    use HasFactory;
    protected $table = 'city_closing_extended';
    protected $fillable = ['bus_id', 'location_id','journey_date','closing_hours','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    public function location()
    {
    	return $this->belongsTo(Location::class);
    }
}
