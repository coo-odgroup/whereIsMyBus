<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UserNotification extends Model
{
    use HasFactory; 
    protected $table = 'user_notification';
    protected $fillable = ['user_id ','notification_id','read_status'];  
    public function notification()
    {
    	return $this->belongsTo(UserNotification::class);
    }  
    public function user()
    {
    	return $this->belongsTo(User::class);
    }  
}
