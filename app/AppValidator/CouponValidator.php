<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponValidator 
{   
    public function validate($data) { 
        
        $rules = [
            'bus_id' => 'required|numeric',
            'source_id' => 'required|numeric',
            'destination_id' => 'required|numeric',
            'journey_date' => 'required|date_format:Y-m-d',
            'transaction_id' => 'required|numeric',
        ];      
      
        $couponValidator = Validator::make($data, $rules);
        return $couponValidator;
    }

}