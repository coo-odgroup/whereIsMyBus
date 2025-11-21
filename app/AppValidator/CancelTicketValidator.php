<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CancelTicketValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'pnr' => 'required',
            'phone' => 'required|digits:10'

        ];      
      
        $cancelTicketValidator = Validator::make($data, $rules);
        return $cancelTicketValidator;
    }

}