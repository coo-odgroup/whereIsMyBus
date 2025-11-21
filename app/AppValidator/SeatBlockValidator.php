<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeatBlockValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'transaction_id' => 'required|numeric',
        ];      
      
        $seatBlockValidation = Validator::make($data, $rules);
        return $seatBlockValidation;
    }

}