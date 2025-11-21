<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationValidator 
{   

    public function validate($data) { 
        
        $rules = [
            //'locationName' => 'required|alpha|min:3',
        ];      
      
        $locationValidation = Validator::make($data, $rules);
        return $locationValidation;
    }

}