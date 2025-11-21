<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommonValidator 
{   
    public function validate($data) { 
        
        $rules = [
            'user_id' => 'required'
        ];      
      
        $commonValidator = Validator::make($data, $rules);
        return $commonValidator;
    }

}