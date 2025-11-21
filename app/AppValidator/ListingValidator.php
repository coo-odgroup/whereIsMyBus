<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListingValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'source' => 'required|max:50',
            'destination' => 'required|max:50',
            'entry_date' => 'required|date_format:d-m-Y'
        ];      
      
        $ListingValidation = Validator::make($data, $rules);
        return $ListingValidation;
    }

}