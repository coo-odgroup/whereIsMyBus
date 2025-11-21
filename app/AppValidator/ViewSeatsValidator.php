<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ViewSeatsValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'busId' => 'required|numeric',
            'sourceId' => 'required|numeric',
            'destinationId' => 'required|numeric',
            'entry_date' => 'required|date_format:d-m-Y',
        ];      
      
        $viewSeatsValidation = Validator::make($data, $rules);
        return $viewSeatsValidation;
    }

}