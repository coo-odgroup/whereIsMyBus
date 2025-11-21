<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'notification.title' => 'required',
            'notification.body' => 'required',
        ];      
      
        $notificationValidation = Validator::make($data, $rules);
        return $notificationValidation;
    }

}