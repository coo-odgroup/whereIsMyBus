<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentPaymentStatusValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'transaction_id' => 'required'
        ];      
      
        $agentPayemntStatusValidator = Validator::make($data, $rules);
        return $agentPayemntStatusValidator;
    }
}





