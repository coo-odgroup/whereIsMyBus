<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seats;
use App\Models\Bus;

class BusSeatLayout extends Model
{
    use HasFactory;
    protected $table = 'bus_seat_layout';
    protected $fillable = [
        'name'
    ];
    public function seats()
    {
        return $this->hasMany(Seats::class);   
    }
    public function bus()
    {
        return $this->hasone(Bus::class);   
    } 
}
