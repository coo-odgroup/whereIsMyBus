<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResendotpValidator 
{   

    public function validate($data) { 

        $rules = [
            'isMobile' => 'required',
            'source' => 'required',
            'isLogin' => 'required',
        ];      

        
        $Validation = Validator::make($data, $rules);
        return $Validation;
    }

}