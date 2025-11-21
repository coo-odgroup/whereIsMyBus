<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;

class FestivalFare extends Model
{
    use HasFactory;
    protected $table = 'festival_fare';
    protected $fillable = ['bus_operator_id','source_id','destination_id','date','seater_price','sleeper_price','reason','created_by'];
    public function bus()
    {
    	return $this->belongsToMany(Bus::class);
    }

}
