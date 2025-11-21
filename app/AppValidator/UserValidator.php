<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'phone' => 'required',
        ];      
      
        $userValidator = Validator::make($data, $rules);
        return $userValidator;
    }

}