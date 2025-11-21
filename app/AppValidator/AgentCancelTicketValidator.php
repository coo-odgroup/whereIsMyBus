<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentCancelTicketValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'pnr' => 'required',
            'mobile' => 'required|digits:10',
            'otp' => 'required|numeric'
            

        ];      
      
        $agentCancelTicketValidator = Validator::make($data, $rules);
        return $agentCancelTicketValidator;
    }

}