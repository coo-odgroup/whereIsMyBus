<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $table = 'coupon';
    protected $fillable = ['bus_operator_id','coupon_title','coupon_code','type','amount', 
                            'max_discount_price','min_tran_amount','max_redeem','apply_once',
                            'max_use_limit','category','from_date','to_date','short_desc','full_desc',
                            'created_by'];
 
    public function couponAssignedBus()
     {
        return $this->hasMany(CouponAssignedBus::class);        
     }
     public function busOperator()
     {
        return $this->belongsTo(BusOperator::class);        
     }
     public function couponRoute()
     {
        return $this->hasMany(CouponRoute::class);        
     }
     public function couponType()
	{        
		return $this->belongsTo(CouponType::class);        
	}
   public function slider()
	{        
		return $this->hasMany(Slider::class);        
	}

}
