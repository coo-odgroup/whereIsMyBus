<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Amenities;
use App\Services\AmenitiesService;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ListingService;
use App\AppValidator\ListingValidator;
use App\AppValidator\FilterValidator;
use App\AppValidator\FilterOptionsValidator;
use App\AppValidator\BusDetailsValidator;
use App\AppValidator\LocationValidator;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ListingController extends Controller
{

    use ApiResponser;
    /**
     * @var amenitiesService
     */
    protected $listingService;
    protected $listingValidator;
    protected $filterValidator;
    protected $filterOptionsValidator;
    protected $busDetailsValidator;
    protected $locationValidator;


    /**
     * ListingController Constructor
     *
     * @param ListingService $listingService,ListingValidator $listingValidator
     *
     */
    public function __construct(ListingService $listingService,ListingValidator $listingValidator,FilterValidator $filterValidator,FilterOptionsValidator $filterOptionsValidator,BusDetailsValidator $busDetailsValidator,LocationValidator $locationValidator)
    {
        $this->listingService = $listingService;
        $this->listingValidator = $listingValidator; 
        $this->filterValidator = $filterValidator;
        $this->filterOptionsValidator = $filterOptionsValidator; 
        $this->busDetailsValidator = $busDetailsValidator; 
        $this->locationValidator = $locationValidator;      
    }
/**
 * @OA\Info(title="ODBUS Consumer APIs", version="0.1",
 * description="L5 Swagger OpenApi description for ODBUS Consumer APIs",
 * )
 */
/**
 * @OA\SecurityScheme(
 *     type="http",
 *     name="Token based",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="AUTH0",
 *     securityScheme="apiAuth",
 * )
 */
/**
 * @OA\Get(path="/api/getLocation",
 *   tags={"getLocation API"},
 *   summary="Get List of Locations",
 *   description="Locations with SearchValue params",
 *     @OA\Parameter(
 *          name="locationName",
 *          description="name or synonym of Location",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *  @OA\Response(response="200", description="all locations"),
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
 */
    public function getLocation(Request $request) {
        $data = $request->only([
            'locationName'
        ]);
        $locationValidation = $this->locationValidator->validate($data);
        
        if ($locationValidation->fails()) {
            $errors = $locationValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        }
        
        $location = $this->listingService->getLocation($request);
        return $this->successResponse($location,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
/**
 * @OA\Get(
 *     path="/api/Listing",
 *     tags={"Listing API"},
 *     description="Get List of Buses",
 *     summary="Get List of Buses",
 *     @OA\Parameter(
 *          name="source",
 *          description="name of source",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="Bhubaneswar"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="destination",
 *          description="name of destination",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="balasore"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="entry_date",
 *          description="journey date",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="25-01-2022"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="bus_operator_id",
 *          description="bus operator id",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="user_id",
 *          description="user id",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer"
 *          )
 *      ),
 *  @OA\Response(response="200", description="List of Buses"),
 *  @OA\Response(response=206, description="validation error: Not a valid entry date"),
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
    public function getAllListing(Request $request) {

        $data = $request->only([
            'source',
            'destination',
            'entry_date',
            'bus_operator_id',
          ]);
        //$data = $request->all();
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $clientId = $user->id;
        
        $listingValidation = $this->listingValidator->validate($data);
          
        if ($listingValidation->fails()) {
            $errors = $listingValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        }
        
        $listingData = $this->listingService->getAll($request,$clientRole,$clientId);
        return $this->successResponse($listingData,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }

/**
 * @OA\Post(
 *     path="/api/Filter",
 *     tags={"Filter API"},
 *     description="Get List of Buses with Filter Params",
 *     summary="Get List of Buses with Filter Params",
 *     @OA\Parameter(
 *          name="price",
 *          description="Buses sort by price:0-without sorting, 1- ascending order sorting",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              default="0"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="sourceID",
 *          description="source Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="82"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="destinationID",
 *          description="destination Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="434"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="entry_date",
 *          description="journey date",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="15-01-2022"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="bus_operator_id",
 *          description="bus operator id",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="user_id",
 *          description="user id",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer"
 *          )
 *      ),
 *   @OA\Parameter(
 *      name="busType[]",
 *      description="AC or NONAC type Bus:1-AC, 2-NONAC",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *        type="array",
 *          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=1,
 *              )
 *          )
 *    ),            
 *     @OA\Parameter(
 *          name="seatType[]",
 *          description="Seater or Sleeper type Bus:1-seater, 2-sleeper",
 *          in="query",
 *          required=false,
 *          @OA\Schema(
 *          type="array",
 *          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=1,
 *              )
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="boardingPointId[]",
 *          description="Boarding point Ids",
 *          in="query",
 *          required=false,
 *          @OA\Schema(
 *          type="array",
 *          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=1,
 *              )
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="dropingingPointId[]",
 *          description="Dropping point Ids",
 *          in="query",
 *          required=false,
 *          @OA\Schema(
 *          type="array",
 *          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=1,
 *              )
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="operatorId[]",
 *          description="Operator Ids",
 *          in="query",
 *          required=false,
 *          @OA\Schema(
 *           type="array",
 *          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=1,
 *              )
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="amenityId[]",
 *          description="Amenity Ids",
 *          in="query",
 *          required=false,
 *          @OA\Schema(
 *          type="array",
*          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=1,
 *              )
 *          )
 *      ),
 *  @OA\Response(response="200", description="List of Buses"),
 *  @OA\Response(response=206, description="validation error: Not a valid entry date"),
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
    public function filter(Request $request) {
        $data = $request->only([
            'price',
            'sourceID',
            'destinationID',
            'entry_date',
            'bus_operator_id',
        ]);
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $clientId = $user->id;

        $filterValidation = $this->filterValidator->validate($data);
        
        if ($filterValidation->fails()) {
            $errors = $filterValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        $filterData = $this->listingService->filter($request,$clientRole, $clientId);
        return $this->successResponse($filterData,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
/**
 * @OA\Post(
 *     path="/api/FilterOptions",
 *     tags={"FilterOptions API"},
 *     description="get all Filter options for BusType,SeatType,BoardingPoints,DroppingPoints,Operators,Amenities",
 *     summary="Get all Filter options",
 *     @OA\Parameter(
 *          name="sourceID",
 *          description="source Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="82"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="destinationID",
 *          description="destination Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="53"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="busIDs[]",
 *          description="bus Ids",
 *          in="query",
 *          required=true,
 *          @OA\Schema(
 *          type="array",
 *          @OA\Items(
 *              type="integer",
 *              format="int64",
 *              example=254,
 *              )
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="entry_date",
 *          description="journey date",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="18-09-2022"
 *          )
 *      ),
 *  @OA\Response(response="200", description="get all Filter Options"),
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
    public function getFilterOptions(Request $request) {
        $data = $request->only([
            'sourceID',
            'destinationID'
        ]);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $clientId = $user->id;

        $filterOptionsValidation = $this->filterOptionsValidator->validate($data);
        if ($filterOptionsValidation->fails()) {
            $errors = $filterOptionsValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        $FilterData = $this->listingService->getFilterOptions($request,$clientRole,$clientId);
        return $this->successResponse($FilterData,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
   
    /**
 * @OA\Post(
 *     path="/api/BusDetails",
 *     tags={"BusDetails API"},
 *     description="Get details of a Bus",
 *     summary="Get Details of a Bus",
 *     @OA\Parameter(
 *          name="bus_id",
 *          description="bus Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="483"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="source_id",
 *          description="source Id",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="82"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="destination_id",
 *          description="destination Id",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="434"
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
 *          name="origin",
 *          description="destination Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="ODBUS"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="ReferenceNumber",
 *          description="ReferenceNumber",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *  @OA\Response(response="200", description="Bus Details"),
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
    public function busDetails(Request $request) {
        $data = $request->only([
            'bus_id',
            'source_id',
            'destination_id',
            'journey_date'
        ]);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $clientId = $user->id;

        $busDetailsValidation = $this->busDetailsValidator->validate($data);
        if ($busDetailsValidation->fails()) {
            $errors = $busDetailsValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        $details = $this->listingService->busDetails($request,$clientRole, $clientId);

        if($details =='Invalid Origin'){
    
            return $this->errorResponse("Invalid Origin",Response::HTTP_OK);
    
        }if($details =='ReferenceNumber_empty'){
            return $this->errorResponse("Reference Number is required",Response::HTTP_OK);
        }
        else{
           return $this->successResponse($details,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
        }
    }

    public function UpdateExternalApiLocation(){
        $details = $this->listingService->UpdateExternalApiLocation();
        return $this->successResponse($details,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);

    }
    public function updateMantisApiLocation(){
        $details = $this->listingService->updateMantisApiLocation();
        return $this->successResponse($details,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);

    }
}
