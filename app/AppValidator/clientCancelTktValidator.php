<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientCancelTktValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'pnr' => 'required',
        ];      
      
        $cancelTicketValidator = Validator::make($data, $rules);
        return $cancelTicketValidator;
    }

}