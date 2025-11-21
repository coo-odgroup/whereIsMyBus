<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seats;

class SeatClass extends Model
{
    use HasFactory;
    protected $table = 'seat_class';
    protected $fillable = [
        'name', 
    ];
    public function seats()
    {
    	return $this->hasMany(Seats::class);
    }

}