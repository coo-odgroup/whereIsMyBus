<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;

class BusSpecialFare extends Model
{
    use HasFactory;
    protected $table = 'bus_special_fare';

    public function busSpecialFare()
    {
        return $this->belongsToMany(BusSpecialFare::class,'bus_id','spcial_fare_id');
    }
}