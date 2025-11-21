<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSitting extends Model
{
    use HasFactory;
    protected $table = 'bus_sitting';
    // public $timestamps = false;
    protected $fillable = [
        'name', 'created_date', 'created_by',
    ];
    public function Bus()
    {
    	return $this->hasMany(Bus::class);
    }
}
