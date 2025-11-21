<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenities extends Model
{
    use HasFactory;
    protected $table = 'amenities';
    protected $fillable = [
        'name','icon', 'reason'
    ];
    public function busAmenities()
    {
        return $this->hasOne(BusAmenities::class);   
    } 
}
