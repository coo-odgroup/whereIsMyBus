<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\BusCancelled;

class BusCancelledDate extends Model
{
    use HasFactory;
    protected $table = 'bus_cancelled_date';
    protected $fillable = ['bus_cancelled_id','cancelled_date'];

    public function busCancelled()
    {
    	return $this->belongsTo(BusCancelled::class);
    }
}