<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ApiReferenceService;
use App\Traits\ApiResponser;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ApiReferenceController extends Controller

{

    use ApiResponser;

    protected $apiReferenceService;
   
    
    public function __construct(ApiReferenceService $apiReferenceService)
    {
        $this->apiReferenceService = $apiReferenceService;
       
    }
    
 

    public function apiReference(Request $request)
    {
        $response =  $this->apiReferenceService->apiReference($request); 
        
        return $this->successResponse($response,Config::get('constants.RECORD_ADDED'),Response::HTTP_OK);
    }




}