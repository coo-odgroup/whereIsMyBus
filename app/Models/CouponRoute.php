<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Coupon;
use App\Models\Location;

class CouponRoute extends Model
{
    use HasFactory;
    protected $casts = [
        'from_location_id' => 'int',
        'to_location_id' => 'int'
    ];
    protected $table = 'coupon_route';
    protected $fillable = ['coupon_id','source_id','destination_id','created_by'];
    public function coupon()
    {
    	return $this->belongsTo(Coupon::class);
    }
    public function from_location()
    {
        return $this->hasOne(Location::class, 'from_location_id');
    }

    public function to_location()
    {
        return $this->hasOne(Location::class, 'to_location_id');
    }
}
