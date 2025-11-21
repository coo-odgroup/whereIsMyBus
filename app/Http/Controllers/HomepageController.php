<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\HomepageService;

class HomepageController extends Controller
{

    use ApiResponser;
    
    protected $homepageService;
   
    public function __construct(HomepageService $homepageService)
    {
        $this->homepageService = $homepageService;  
    }
    
    public function homePage(Request $request) {
       
        $homepage = $this->homepageService->homePage($request);
        return $this->successResponse($homepage,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }    
}