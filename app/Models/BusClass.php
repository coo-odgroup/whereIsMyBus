<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusType;

class BusClass extends Model
{
    use HasFactory;
    protected $table = 'bus_class';
    protected $fillable = [
        'class_name', 
    ];

    public function busType()
    {
    	return $this->hasMany(BusType::class);
    }
}