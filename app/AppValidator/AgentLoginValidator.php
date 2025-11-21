<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentLoginValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'email' => 'required',
            'password' => 'required'
        ];      
      
        $agentValidator = Validator::make($data, $rules);
        return $agentValidator;
    }

}