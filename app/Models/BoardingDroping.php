<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Location;
use App\Models\BusStoppageTiming;

class BoardingDroping extends Model
{
    use HasFactory;
    protected $table = 'boarding_droping';
    protected $fillable = ['boarding_point','location_id','created_by'];

    public function location()
    {
    	return $this->belongsTo(Location::class);
    } 
    public function busStoppageTiming()
    {        
        return $this->hasMany(BusStoppageTiming::class);        
    }
}
