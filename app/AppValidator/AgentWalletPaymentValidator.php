<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentWalletPaymentValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'user_id' => 'required',
            'user_name' => 'required',
            'busId' => 'required',
            'sourceId' => 'required',
            'destinationId' => 'required',
            'applied_comission' => 'required',    
            'transaction_id' => 'required',    
            //'amount' => 'required',    
            'seatIds' => 'required|array|min:1',    
            'entry_date' => 'required|date_format:d-m-Y',    
        ];      
      
        $agentDetailsValidator = Validator::make($data, $rules);
        return $agentDetailsValidator;
    }

}