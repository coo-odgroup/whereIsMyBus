<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserNotification;

class Notification extends Model
{
    use HasFactory; 
    protected $table = 'notification';
    protected $fillable = ['notification_heading','notification_details'];  

    public function userNotification()
    {
    	 return $this->hasMany(UserNotification::class);        
    }  
}
