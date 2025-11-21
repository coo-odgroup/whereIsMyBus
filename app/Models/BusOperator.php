<?php

namespace App\Models;
use App\Models\Bus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TicketPrice;

class BusOperator extends Model
{
    use HasFactory;
    protected $table = 'bus_operator';
    protected $fillable = [
        'email_id','password','operator_name','operator_info','contact_number','organisation_name','location_name'
    ];
    public function bus()
    {        
        return $this->hasMany(Bus::class);        
    } 
    public function ticketPrice()
    {
    	return $this->hasMany(TicketPrice::class);
    }
    public function coupon()
    {
    	return $this->hasMany(Coupon::class);
    }

}
