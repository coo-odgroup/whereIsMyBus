<?php
namespace App\Repositories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ApiReference;
use Razorpay\Api\Api;
use App\Jobs\SendApiEmailJob;

use Carbon\Carbon;
use DateTime;

class ApiReferenceRepository
{
    protected $apiReference;

    public function __construct(ApiReference $apiReference)
    {
       
        $this->apiReference = $apiReference;

    }   
    

    public function ApiReference($request){
      
        $apiReference = new ApiReference();
        $apiReference->name = $request['name'];
        $apiReference->email = $request['email'];
        $apiReference->phone = $request['phone'];
        $apiReference->company_name = $request['company_name'];
        $apiReference->save();

        $sendEmailTicket = $this->sendApiEmail($request['name'],$request['email'],$request['phone']); 

        return $apiReference; 
        
    }

    public function sendApiEmail($name,$email,$phone){

        return  SendApiEmailJob::dispatch($name,$email,$phone);



    }


   

}