<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteMaster extends Model
{
    use HasFactory;
    protected $table = 'site_master';
    protected $fillable = ['site_live','live_at','extra_price','calender_days','service_charge',
    'per_transaction','max_seat_booked','support_email','booking_email','request_email','other_email',
    'contact_no1','contact_no2','contact_no3','contact_no4','facebook_url','twitter_url','linkedin_url',
    'instagram_url','googleplus_url','min_fare_amt','earned_pts'];
}
