<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

class Locationcode extends Model
{
    use HasFactory;
    protected $table = 'locationcode';
    //public $timestamps = false;
    protected $fillable = ['location_id','type','providerid','created_by','status'];
    public function location()
    {
    	return $this->belongsTo(Location::class);
    }

    protected $hidden = [
        'created_at',
        'updated_at',
        'created_by',
        'status',
    ];

}
