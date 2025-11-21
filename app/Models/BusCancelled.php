<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\BusCancelledDate;
class BusCancelled extends Model
{
    use HasFactory;
    protected $table = 'bus_cancelled';
    protected $fillable = ['bus_id','bus_operator_id','cancelled_date','reason',
'cancelled_by','status','month','year'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    public function busCancelledDate()
    {        
        return $this->hasMany(BusCancelledDate::class);        
    }
    
}
