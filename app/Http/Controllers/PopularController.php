<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PopularService;
use App\AppValidator\DownloadAppValidator;
use App\Services\ValueFirstService;


class PopularController extends Controller
{
    use ApiResponser;
    
    protected $popularService;
    protected $downloadAppValidator;
  
    public function __construct(PopularService $popularService, DownloadAppValidator $downloadAppValidator)
    {
        $this->popularService = $popularService;       
        $this->downloadAppValidator = $downloadAppValidator;       
    }
/**
 * @OA\Get(
 *     path="/api/PopularRoutes",
 *     tags={"PopularRoutes API"},
 *     description="get all Popular Routes",
 *     summary="get all Popular Routes",
 *  @OA\Response(response="200", description="Get all Popular Routes"),
 *  @OA\Response(response=206, description="validation error"),
 *  @OA\Response(response=400, description="Bad request"),
 *  @OA\Response(response=401, description="Unauthorized access"),
 *  @OA\Response(response=404, description="No record found"),
 *  @OA\Response(response=500, description="Internal server error"),
 *  @OA\Response(response=502, description="Bad gateway"),
 *  @OA\Response(response=503, description="Service unavailable"),
 *  @OA\Response(response=504, description="Gateway timeout"),
 *     security={{ "apiAuth": {} }}
 * )
 * 
 */
    public function getPopularRoutes(Request $request) {
        $popularRoutes = $this->popularService->getPopularRoutes($request);
        return $this->successResponse($popularRoutes,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
/**
 * @OA\Get(
 *     path="/api/TopOperators",
 *     tags={"TopOperators API"},
 *     description="get all Top Operators",
 *     summary="get all Top Operators",
 *  @OA\Response(response="200", description="Get all Top Operators"),
 *  @OA\Response(response=206, description="validation error"),
 *  @OA\Response(response=400, description="Bad request"),
 *  @OA\Response(response=401, description="Unauthorized access"),
 *  @OA\Response(response=404, description="No record found"),
 *  @OA\Response(response=500, description="Internal server error"),
 *  @OA\Response(response=502, description="Bad gateway"),
 *  @OA\Response(response=503, description="Service unavailable"),
 *  @OA\Response(response=504, description="Gateway timeout"),
 *     security={{ "apiAuth": {} }}
 * )
 * 
 */
    public function getTopOperators(Request $request) {
        $topOperators = $this->popularService->getTopOperators($request);
        return $this->successResponse($topOperators,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
/**
 * @OA\Get(
 *     path="/api/AllRoutes",
 *     tags={"All Routes API"},
 *     description="get all Routes of bus running",
 *     summary="get all Routes of bus running",
 *  @OA\Response(response="200", description="Record Fetched Successfully"),
 *  @OA\Response(response=206, description="validation error"),
 *  @OA\Response(response=400, description="Bad request"),
 *  @OA\Response(response=401, description="Unauthorized access"),
 *  @OA\Response(response=404, description="No record found"),
 *  @OA\Response(response=500, description="Internal server error"),
 *  @OA\Response(response=502, description="Bad gateway"),
 *  @OA\Response(response=503, description="Service unavailable"),
 *  @OA\Response(response=504, description="Gateway timeout"),
 *     security={{ "apiAuth": {} }}
 * )
 * 
 */
    public function allRoutes(Request $request) {
        $allRoutes = $this->popularService->allRoutes($request);
        return $this->successResponse($allRoutes,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
/**
 * @OA\Post(
 *     path="/api/AllOperators",
 *     tags={"All Operators API"},
 *     description="get all Operators names",
 *     summary="get all Operators names",
 *     @OA\Parameter(
 *          name="filter params(example:A)",
 *          description="filter operator names alphabetically(example:A, it will filter opertors starting name with A only)",
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="paginate",
 *          description="paginate all operators",
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="400"
 *          )
 *      ),
 *  @OA\Response(response="200", description="Record Fetched Successfully"),
 *  @OA\Response(response=206, description="validation error"),
 *  @OA\Response(response=400, description="Bad request"),
 *  @OA\Response(response=401, description="Unauthorized access"),
 *  @OA\Response(response=404, description="No record found"),
 *  @OA\Response(response=500, description="Internal server error"),
 *  @OA\Response(response=502, description="Bad gateway"),
 *  @OA\Response(response=503, description="Service unavailable"),
 *  @OA\Response(response=504, description="Gateway timeout"),
 *     security={{ "apiAuth": {} }}
 * )
 * 
 */
    public function allOperators(Request $request) {
        $allRoutes = $this->popularService->allOperators($request);
        return $this->successResponse($allRoutes,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
/**
 * @OA\Post(
 *     path="/api/downloadapp",
 *     tags={"sending sms to download ODBUS App"},
 *     description="sending sms to download ODBUS App",
 *     summary="sending sms to download ODBUS App",
 *     @OA\Parameter(
 *          name="phone",
 *          description="user mobile number",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer"
 *          )
 *      ),
 *  @OA\Response(response="200", description="Record Fetched Successfully"),
 *  @OA\Response(response=206, description="validation error"),
 *  @OA\Response(response=400, description="Bad request"),
 *  @OA\Response(response=401, description="Unauthorized access"),
 *  @OA\Response(response=404, description="No record found"),
 *  @OA\Response(response=500, description="Internal server error"),
 *  @OA\Response(response=502, description="Bad gateway"),
 *  @OA\Response(response=503, description="Service unavailable"),
 *  @OA\Response(response=504, description="Gateway timeout"),
 *     security={{ "apiAuth": {} }}
 * )
 * 
 */
    public function downloadApp(Request $request){

        $data = $request->all();
        $downloadAppValidator = $this->downloadAppValidator->validate($data);

        if ($downloadAppValidator->fails()) {
        $errors = $downloadAppValidator->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 

        try {

            $downloadApp = $this->popularService->downloadApp($request);
            return $this->successResponse($downloadApp,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
      
       
        }
         catch (Exception $e) {
             return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
           } 

     

    }
/**
 * @OA\Get(
 *     path="/api/OperatorDetails",
 *     tags={"Operator's Details"},
 *     description="Operator's Details",
 *     summary="Operator's Details",
 *     @OA\Parameter(
 *          name="operator_url",
 *          description="operator url",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              default="iconic-travels",
 *          )
 *      ),
 *  @OA\Response(response="200", description="Record Fetched Successfully"),
 *  @OA\Response(response=206, description="validation error"),
 *  @OA\Response(response=400, description="Bad request"),
 *  @OA\Response(response=401, description="Unauthorized access"),
 *  @OA\Response(response=404, description="No record found"),
 *  @OA\Response(response=500, description="Internal server error"),
 *  @OA\Response(response=502, description="Bad gateway"),
 *  @OA\Response(response=503, description="Service unavailable"),
 *  @OA\Response(response=504, description="Gateway timeout"),
 *     security={{ "apiAuth": {} }}
 * )
 * 
 */
    public function operatorDetails(Request $request) {
        $response = $this->popularService->operatorDetails($request);
      
          switch($response){
                case('operator-not-found'):   //Transaction amount is Less then Minimum Transation
                    return $this->errorResponse(Config::get('constants.OPERATOR_NOT_FOUND'),Response::HTTP_OK);
                break;
          }
            
        return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }

    public function CityPair() {
        $response = $this->popularService->CityPair();      
        return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    } 

     public function ValueFirstSms(Request $request)
    {
        $sms = new ValueFirstService();

        $to= (isset($_REQUEST['phone']) && $_REQUEST['phone']!='') ? $_REQUEST['phone'] : '7978817539';
        $pnr="ODBUS28428";
        $msg='Dear Ali, Your OTP is 123456 to login ODBUS. Thanks - ODBUS';

        $result = $sms->sendSms($to,$msg);

        return response()->json(['result' => $result]);
    }
}