<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Bus;

class BusSlots extends Model
{
    use HasFactory;
    protected $table = 'bus_slots';
    protected $fillable = ['bus_id', 'name','type','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
}
