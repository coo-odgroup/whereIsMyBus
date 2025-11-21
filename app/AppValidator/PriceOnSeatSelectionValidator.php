<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PriceOnSeatSelectionValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'busId' => 'required|numeric',
            'sourceId' => 'required|numeric',
            'destinationId' => 'required|numeric',
            //'busOperatorId' => 'required|numeric',
            //'seater' => 'required',
            //'sleeper' => 'required',
            'seater' => 'required_without:sleeper',
            'sleeper' => 'required_without:seater',
            'entry_date' => 'required|date_format:d-m-Y',
        ];      
      
        $priceValidation = Validator::make($data, $rules);
        return $priceValidation;
    }

}