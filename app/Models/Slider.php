<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;
    protected $table = 'slider';
    protected $fillable = [
       'occassion','category','url', 'slider_img','alt_tag','start_date','start_time','end_date','end_time','created_by'
    ];
    public function coupon()
	{        
		//return $this->belongsTo(Coupon::class); 
        return $this->belongsTo(Coupon::class)->withDefault(function () {
            return (object)[];
        });              
	}
}
