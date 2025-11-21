<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientCancelTicketValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'pnr' => 'required',
            'user_id' => 'required'

        ];      
      
        $cancelTicketValidator = Validator::make($data, $rules);
        return $cancelTicketValidator;
    }

}