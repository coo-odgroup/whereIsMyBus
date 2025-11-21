<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponType extends Model
{
    use HasFactory;
    protected $table = 'coupon_type';
    protected $fillable = [  'coupon_type_name','created_by'];
 
    public function coupon()
     {
        return $this->hasMany(Coupon::class);        
     }
}
