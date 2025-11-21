<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'service' => 'required',
            'message' => 'required',
            'user_id' => 'required'
        ];      
      
        $ContactValidation = Validator::make($data, $rules);
        return $ContactValidation;
    }

}