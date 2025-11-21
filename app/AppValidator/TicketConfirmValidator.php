<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketConfirmValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'transaction_id' => 'required'
        ];      
      
        $validate = Validator::make($data, $rules);
        return $validate;
    }
}





