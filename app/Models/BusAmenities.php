<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Amenities;


class BusAmenities extends Model
{
    use HasFactory;
    protected $table = 'bus_amenities';
    protected $fillable = ['bus_id', 'amenities_id','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    public function amenities()
    {
    	return $this->belongsTo(Amenities::class);
    }
}
