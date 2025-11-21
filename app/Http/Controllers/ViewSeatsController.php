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
use App\Services\ViewSeatsService;
use App\AppValidator\ViewSeatsValidator;
use App\AppValidator\PriceOnSeatSelectionValidator;
use App\AppValidator\BoardingDroppingValidator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ViewSeatsController extends Controller
{

    use ApiResponser;
    
    protected $viewSeatsService;
    protected $viewSeatsValidator;
    protected $priceOnSeatSelectionValidator;
    protected $boardingDroppingValidator;
  
    /**
     * ViewSeatsController Constructor
     *
     * @param  ViewSeatsService $ viewSeatsService, ViewSeatsValidator $ viewSeatsValidator
     *
     */



    public function __construct(ViewSeatsService $viewSeatsService,ViewSeatsValidator $viewSeatsValidator,PriceOnSeatSelectionValidator $priceOnSeatSelectionValidator,BoardingDroppingValidator $boardingDroppingValidator)
    {
        $this->viewSeatsService = $viewSeatsService;  
        $this->viewSeatsValidator = $viewSeatsValidator;  
        $this->priceOnSeatSelectionValidator = $priceOnSeatSelectionValidator;
        $this->boardingDroppingValidator = $boardingDroppingValidator;    
    }
 
/**
 * @OA\Post(
 *     path="/api/viewSeats",
 *     tags={"viewSeats API"},
 *     description="get all seat Information  for a Bus",
 *     summary="Get seat information for a Bus with seat layout",
 *     @OA\Parameter(
 *          name="entry_date",
 *          description="searching date",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="25-02-2022"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="busId",
 *          description="bus Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example=287
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="sourceId",
 *          description="source Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example=82
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="destinationId",
 *          description="destination Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example=53
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="ReferenceNumber",
 *          description="reference number for Dolphin service",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="origin",
 *          description="Service provider name",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *  @OA\Response(response="200", description=" get all seats information"),
 *  @OA\Response(response="206", description=" validation error"),
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
    public function getAllViewSeats(Request $request) {
        $data = $request->only([
            'busId',
            'sourceId',
            'entry_date',
            'destinationId',
        ]);
        
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $clientId = $user->id;
        $viewSeatsValidation = $this->viewSeatsValidator->validate($data);
        
        if ($viewSeatsValidation->fails()) {
            $errors = $viewSeatsValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        }
        
        $viewSeatsData = $this->viewSeatsService->getAllViewSeats($request, $clientRole,$clientId);
        if($viewSeatsData =='Invalid Origin'){
    
            return $this->errorResponse("Invalid Origin",Response::HTTP_OK);
    
        }if($viewSeatsData =='ReferenceNumber_empty'){
            return $this->errorResponse("Reference Number is required",Response::HTTP_OK);
        }
        else{
            return $this->successResponse($viewSeatsData,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
        }
    
    }
/**
 * @OA\Post(
 *     path="/api/PriceOnSeatsSelection",
 *     tags={"PriceOnSeatsSelection API"},
 *     description="get total price on seat selection",
 *     summary="get total price on seat selection",
 *     @OA\Parameter(
 *          name="busId",
 *          description="bus Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example=1
 *          )
 *      ),
 *      @OA\Parameter(
 *          name="sourceId",
 *          description="source Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *               example=82
 *          )
 *      ),
 *      @OA\Parameter(
 *          name="destinationId",
 *          description="destination Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *               example=434
 *          )
 *      ),
 *      @OA\Parameter(
 *          name="entry_date",
 *          description="entry_date",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *               example="15-01-2022"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="seater[]",
 *          description="seater Ids",
 *          in="query",
 *          required=false,
 *          @OA\Schema(
 *          type="array",
*          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=2694,
 *              )
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="sleeper[]",
 *          description="sleeper Ids",
 *          in="query",
 *          required=false,
 *          @OA\Schema(
 *          type="array",
*          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=2755,
 *              )
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="ReferenceNumber",
 *          description="reference number for Dolphin service",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="origin",
 *          description="Service provider name",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *  @OA\Response(response="200", description=" get Total Price on seats selection"),
 *  @OA\Response(response="206", description=" validation error"),
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
    public function getPriceOnSeatsSelection(Request $request) {
        $data = $request->only([
            'busId',
            'sourceId',
            'destinationId',
            'seater',
            'sleeper',
            'entry_date'
        ]);
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $clientId = $user->id;
        $priveValidation = $this->priceOnSeatSelectionValidator->validate($data);
        
        if ($priveValidation->fails()) {
            $errors = $priveValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        }  
        $priceOnSeats = $this->viewSeatsService->getPriceOnSeatsSelection($request,$clientRole,$clientId);
        

        if($priceOnSeats =='Invalid Origin'){
    
            return $this->errorResponse("Invalid Origin",Response::HTTP_OK);
    
        }if($priceOnSeats =='ReferenceNumber_empty'){
            return $this->errorResponse("Reference Number is required",Response::HTTP_OK);
        }
        else{
            return $this->successResponse($priceOnSeats,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
        }
    }

      /**
 * @OA\Post(
 *     path="/api/BoardingDroppingPoints",
 *     tags={"BoardingDroppingPoints API"},
 *     description="get all Boarding Dropping Points for source and destination",
 *     summary="get all Boarding Dropping Points",
 *     @OA\Parameter(
 *          name="busId",
 *          description="bus Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example=1
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="sourceId",
 *          description="source Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *               example=82
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="destinationId",
 *          description="destination Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *               example=434
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="journey_date",
 *          description="journey date",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="18-09-2022"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="ReferenceNumber",
 *          description="reference number for Dolphin service",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="origin",
 *          description="Service provider name",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *  @OA\Response(response="200", description=" get all Boarding Dropping Points"),
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
    public function getBoardingDroppingPoints(Request $request) {
        $data = $request->only([
            'busId',
            'sourceId',
            'destinationId',
        ]);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $clientId = $user->id;

        $boardDropValidation = $this->boardingDroppingValidator->validate($data);
        
        if ($boardDropValidation->fails()) {
            $errors = $boardDropValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        }  
        $boardingPoints = $this->viewSeatsService->getBoardingDroppingPoints($request,$clientRole,$clientId);

        if($boardingPoints =='Invalid Origin'){
    
            return $this->errorResponse("Invalid Origin",Response::HTTP_OK);
    
        }if($boardingPoints =='ReferenceNumber_empty'){
            return $this->errorResponse("Reference Number is required",Response::HTTP_OK);
        }
        else{
            return $this->successResponse($boardingPoints,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
        }

        
    }
}
