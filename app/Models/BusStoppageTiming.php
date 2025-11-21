<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BoardingDroping;


class BusStoppageTiming extends Model
{
    use HasFactory;
    protected $table = 'bus_stoppage_timing';
    protected $fillable = ['bus_id','location_id','boarding_droping_id','stoppage_time','created_by'];

    public function bus()
    {
    	return $this->belongsToMany(Bus::class);
    } 
    public function boardingDroping()
    {
    	return $this->belongsTo(BoardingDroping::class);
    } 
}
