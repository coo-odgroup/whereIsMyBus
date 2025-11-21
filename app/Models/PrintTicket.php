<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PrintTicket extends Model
{
    use HasFactory;
    protected $table = 'location';
    protected $fillable = ['booking_id','ticketdata'];
   
}
