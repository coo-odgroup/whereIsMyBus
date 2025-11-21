<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletBalanceValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'ClientId' => 'required|numeric',
        ];      
      
        $res = Validator::make($data, $rules);
        return $res;
    }

}