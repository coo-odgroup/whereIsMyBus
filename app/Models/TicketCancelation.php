<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCancelation extends Model
{
    use HasFactory;
    protected $table = 'ticket_cancelation';
    protected $fillable = ['name','created_by'];

public function ticketCancelationRule()
    {
        return $this->hasMany(TicketCancelationRule::class);
        
    } 
}