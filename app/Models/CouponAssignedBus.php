<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Coupon;

class CouponAssignedBus extends Model
{
    use HasFactory;
    protected $table = 'coupon_assigned_bus';
    protected $fillable = ['bus_id','coupon_id','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    public function coupon()
    {
    	return $this->belongsTo(Coupon::class);
    }
}
