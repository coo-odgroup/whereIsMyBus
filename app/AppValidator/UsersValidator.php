<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsersValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'name' => 'required|max:50',
            'created_by' => 'required',
        ];      
      
        $UsersValidation = Validator::make($data, $rules);
        return $UsersValidation;
    }

}
