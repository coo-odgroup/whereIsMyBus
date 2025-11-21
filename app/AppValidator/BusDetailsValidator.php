<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusDetailsValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'bus_id' => 'required|numeric',
            'source_id' => 'required|numeric',
            'destination_id' => 'required|numeric',
            'journey_date' => 'required|date_format:d-m-Y'
        ];      
      
        $busDetailsValidation = Validator::make($data, $rules);
        return $busDetailsValidation;
    }

}