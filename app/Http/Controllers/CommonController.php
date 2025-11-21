<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\AppValidator\CommonValidator;
use App\Services\CommonService;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    use ApiResponser;
    /**
     * @var cancelTicketService
     */
    protected $commonService;
    protected $commonValidator;
    /**
     * cancelTicketController Constructor
     *
     * @param commonService $commonService
     *
     */
    public function __construct(CommonService $commonService, CommonValidator $commonValidator)
    {
        $this->commonService = $commonService;      
        $this->commonValidator = $commonValidator;      
    }
    /**
     * @OA\Post(
     *     path="/api/CommonService",
     *     tags={"Common Service"},
     *     description="Get all SEO related things",
     *     summary="Get all SEO related things",
     *     @OA\Parameter(
     *          name="user_id",
     *          description="user Id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              default=1,
     *          )
     *      ),
     *  @OA\Response(response="200", description="Get all Social media links"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={
     *       {"apiAuth": {}}
     *     }
     * )
     * 
     */
    public function getAll(Request $request) {        

        $data = $request->all();
        $commonValidation = $this->commonValidator->validate($data);

        if ($commonValidation->fails()) {
        $errors = $commonValidation->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 

         try {
          $response =  $this->commonService->getAll($request);
           return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
       }
       catch (Exception $e) {
           return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
         }      
   } 
/**
     * @OA\Get(
     *     path="/api/Appversion",
     *     tags={"App Version"},
     *     description="Get App Version",
     *     summary="Get App Version",
     *  @OA\Response(response="200", description="Get app version details"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={
     *       {"apiAuth": {}}
     *     }
     * )
     * 
     */
   public function Appversion(){

         $version = DB::table('app_version')->where("id",1)->get();

         return $this->successResponse($version,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
 

   }
}