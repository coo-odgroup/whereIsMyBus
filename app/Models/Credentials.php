<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credentials extends Model
{
    use HasFactory; 
    protected $table = 'credentials';
    protected $fillable = ['sms_textlocal_key', 'mail_username','mail_password','razorpay_key','razorpay_secret'];
}
