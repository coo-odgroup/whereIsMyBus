<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoginValidator 
{   

    public function validate($data) { 

        $rules = [
            'phone' => 'required_without:email|exists:users',
            'email' => 'required_without:phone|exists:users',
            //'phone' => 'required_without:email',
            //'password' => 'required|alpha_num|min:5'
        ];      
      
        $LoginValidation = Validator::make($data, $rules);
        return $LoginValidation;
    }

}