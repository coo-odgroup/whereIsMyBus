<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilterValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'price' => 'required|numeric',
            'sourceID' => 'required|numeric',
            'destinationID' => 'required|numeric',
            'entry_date' => 'required|date_format:d-m-Y',
        ];      
      
        $FilterValidation = Validator::make($data, $rules);
        return $FilterValidation;
    }

}