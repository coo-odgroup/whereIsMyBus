<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'client_id' => 'required',
            'password' => 'required',
        ];      
      
        $clientValidator = Validator::make($data, $rules);
        return $clientValidator;
    }

}