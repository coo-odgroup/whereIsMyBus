<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MakePaymentValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'busId' => 'required|numeric',
            'sourceId' => 'required|numeric',
            'destinationId' => 'required|numeric',
            'transaction_id' => 'required|numeric',
            'seatIds' => 'required|array|min:1',
            //'amount' => 'required',
            'entry_date' => 'required|date_format:d-m-Y',
        ];      
      
        $makePaymentValidation = Validator::make($data, $rules);
        return $makePaymentValidation;
    }

}