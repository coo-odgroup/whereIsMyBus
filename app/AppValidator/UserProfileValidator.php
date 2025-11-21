<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserProfileValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'userId' => 'required|numeric',
            'token' => 'required',
        ];      
      
        $userProfileValidation = Validator::make($data, $rules);
        return $userProfileValidation;
    }

}
