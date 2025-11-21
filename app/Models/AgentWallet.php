<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentWallet extends Model
{
    use HasFactory; 
    protected $table = 'agent_wallet';
    protected $fillable = ['transaction_id','reference_id','payment_via','amount','remarks','user_id'];
    
}
