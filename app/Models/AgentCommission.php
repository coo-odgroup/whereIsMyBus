<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCommission extends Model
{
    use HasFactory; 
    protected $table = 'agent_commission_slab';
    protected $fillable = ['range_from','range_to','comission_per_seat','created_by','status'];
    
}
