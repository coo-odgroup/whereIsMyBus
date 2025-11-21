<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Safety;


class BusSafety extends Model
{
    use HasFactory;
    protected $table = 'bus_safety';
    protected $fillable = ['bus_id', 'safety_id','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    public function safety()
    {
    	return $this->belongsTo(Safety::class);
    }
}
