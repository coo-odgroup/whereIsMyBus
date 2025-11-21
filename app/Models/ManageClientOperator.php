<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BusOperator;



class ManageClientOperator extends Model
{
    use HasFactory; 
    protected $table = 'manageclientoperator';
    protected $fillable = ['user_id ','bus_operator_id '];


    public function user()
    {
        return $this->belongsTo(User::class);       
    }

    public function busOperator()
    {
        return $this->belongsTo(BusOperator::class);
    }
    
}