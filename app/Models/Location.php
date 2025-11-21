<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BoardingDroping;
use App\Models\BusLocationSequence;


class Location extends Model
{
    use HasFactory;
    protected $table = 'location';
    protected $fillable = ['name','synonym','created_by','status'];
    public function locationcode()
    {
        return $this->hasMany(Locationcode::class);
        
    } 
    public function boardingDropping()
    {
        return $this->hasMany(BoardingDroping::class);
        
    } 
    protected $hidden = [
        'created_at',
        'updated_at',
        'created_by',
    ];
    public function busLocationSequence()
    {
        return $this->hasMany(BusLocationSequence::class);        
    } 
}
