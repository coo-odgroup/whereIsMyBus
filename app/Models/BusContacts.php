<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;


class BusContacts extends Model
{
    use HasFactory;
    protected $table = 'bus_contacts';
    protected $fillable = ['bus_id', 'type','phone','booking_sms_send','cancel_sms_send','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
}
