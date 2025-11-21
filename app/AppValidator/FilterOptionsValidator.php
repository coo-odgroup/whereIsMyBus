<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilterOptionsValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'sourceID' => 'required|numeric',
            'destinationID' => 'required|numeric',
        ];      
      
        $FilterOptionsValidation = Validator::make($data, $rules);
        return $FilterOptionsValidation;
    }

}