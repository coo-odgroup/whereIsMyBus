<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\BusClass;

class BusType extends Model
{
    use HasFactory;
    protected $table = 'bus_type';
    protected $fillable = [
        'bus_class_id','name', 
    ];

    public function Bus()
    {
    	return $this->hasMany(Bus::class);
    }
    public function busClass()
    {
    	return $this->belongsTo(BusClass::class);
    } 
}
