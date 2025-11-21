<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Seats;
use App\Models\TicketPrice;


class Dummy extends Model
{
    use HasFactory;
    protected $table = 'dummy';
    protected $fillable = ['bus_id','ticket_price_id','seats_id','category','bookStatus','duration','newfare','created_by'];
   
    
}
