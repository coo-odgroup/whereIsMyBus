<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecentSearchValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'users_id' => 'required|numeric',
            'source' => 'required',
            'destination' => 'required',
            'journey_date' => 'required|date_format:d-m-Y',
        ];      
      
        $searchValidator = Validator::make($data, $rules);
        return $searchValidator;
    }

}