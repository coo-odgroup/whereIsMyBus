<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentFee extends Model
{
    use HasFactory; 
    protected $table = 'agent_fee_slab';
    protected $fillable = ['price_from','price_to','max_comission','created_by','status'];
    
}
