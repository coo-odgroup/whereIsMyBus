<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;


class CityClosing extends Model
{
    use HasFactory;
    protected $table = 'city_closing';
    protected $fillable = ['bus_id', 'location_id','closing_hours','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
}
