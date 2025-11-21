<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BoardingDroppingValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'busId' => 'required|numeric',
            'sourceId' => 'required|numeric',
            'destinationId' => 'required|numeric'
        ];      
      
        $boardDropValidation = Validator::make($data, $rules);
        return $boardDropValidation;
    }

}