<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TicketCancelation;

class TicketCancelationRule extends Model
{
    use HasFactory;
    protected $table = 'ticket_cancelation_rule';
    protected $fillable = ['ticket_cancelation_id','hour_lag_start','hour_lag_end','cancelation_percentage',
                             'created_by'];
    public function ticketCancelation()
    {
    	return $this->belongsTo(TicketCancelation::class);
    }
}
