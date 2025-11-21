<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class OdbusCharges extends Model
{
    use HasFactory;
    protected $table = 'odbus_charges';
    protected $fillable = ['payment_gateway_charges','email_sms_charges','odbus_gst_charges','created_by'];
 
    public function user()
    {
    	 return $this->belongsTo(User::class);        
    }                     
}
