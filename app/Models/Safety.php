<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Safety extends Model
{
    use HasFactory;
    protected $table = 'safety';
    protected $fillable = [
        'name','icon'
    ];
    public function busSafety()
    {
        return $this->hasMany(BusSafety::class);   
    } 
}
