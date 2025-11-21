<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Bus;

class AssocAssignBus extends Model
{
    use HasFactory;

    protected $table = 'assign_bus';    

    protected $fillable = ['user_id','bus_id','created_at','updated_at','created_by'];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

}


