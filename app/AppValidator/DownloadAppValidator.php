<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DownloadAppValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'phone' => 'required|digits:10'
        ];      
      
        $res = Validator::make($data, $rules);
        return $res;
    }

}