<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AgentBookingService;
use App\AppValidator\AgentBookingValidator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;


class AgentBookingController extends Controller
{

    use ApiResponser;
    
    protected $agentBookingService;
    protected $agentBookingValidator;
    

    public function __construct(AgentBookingService $agentBookingService,AgentBookingValidator $agentBookingValidator)
    {
        $this->agentBookingService = $agentBookingService;  
        $this->agentBookingValidator = $agentBookingValidator;      
    }
/**
 * @OA\Post(
 *     path="/api/AgentBooking",
 *     tags={"AgentBooking API"},
 *     summary="Ticket Booking by an Agent with customer details",
 *     @OA\RequestBody(
 *        required = true,
 *     description="Ticket Booking by an Agent",
 *        @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                property="customerInfo",
 *                type="object",
 *                @OA\Property(
 *                  property="email",
 *                  type="string",
 *                  default="abc@gmail.com",
 *                  example="abc@gmail.com"
 *                  ),
 *                @OA\Property(
 *                  property="phone",
 *                  type="number",
 *                  default=9912345678,
 *                  example=9912345678
 *                  ),
 *                @OA\Property(
 *                  property="name",
 *                  type="string",
 *                  default="Bob",
 *                  example="Bob"
 *                  )
 *                ),
 *             @OA\Property(
 *                property="agentInfo",
 *                type="object",
 *                @OA\Property(
 *                  property="email",
 *                  type="string",
 *                  default="abcdfr@gmail.com",
 *                  example="abcdfr@gmail.com"
 *                  ),
 *                @OA\Property(
 *                  property="phone",
 *                  type="number",
 *                  default=9912345673,
 *                  example=9912345673
 *                  ),
 *                @OA\Property(
 *                  property="name",
 *                  type="string",
 *                  default="Bob",
 *                  example="Bob"
 *                  )
 *                ),
 *             @OA\Property(
 *                property="bookingInfo",
 *                type="object",
 *                @OA\Property(
 *                  property="bus_operator_id",
 *                  type="number",
 *                  default=1,
 *                  example=1
 *                  ),
 *                @OA\Property(
 *                  property="bus_id",
 *                  type="number",
 *                  default=3,
 *                  example=3
 *                  ),
 *                @OA\Property(
 *                  property="source_id",
 *                  type="number",
 *                  default=1345,
 *                  example=1345
 *                  ),
 *                @OA\Property(
 *                  property="destination_id",
 *                  type="number",
 *                  default=1374,
 *                  example=1374
 *                  ),
 *                @OA\Property(
 *                  property="journey_date",
 *                  type="string",
 *                  default="2021-10-09",
 *                  example="2021-10-09" ,
 *                  ),
 *                @OA\Property(
 *                  property="boarding_point",
 *                  type="string",
 *                  default="Bus stand",
 *                  example="Bus stand" ,
 *                  ),
 *                @OA\Property(
 *                  property="dropping_point",
 *                  type="string",
 *                  default="bus stand",
 *                  example="bus stand" ,
 *                  ),
 *                @OA\Property(
 *                  property="boarding_time",
 *                  type="string",
 *                  default="21:00",
 *                  example="21:00" ,
 *                  ),
 *                @OA\Property(
 *                  property="dropping_time",
 *                  type="string",
 *                  default="06:30",
 *                  example="06:30" ,
 *                  ),
 *                @OA\Property(
 *                  property="origin",
 *                  type="string",
 *                  default="ODBUS",
 *                  example="ODBUS" ,
 *                  ),
 *                @OA\Property(
 *                  property="app_type",
 *                  type="string",
 *                  default="WEB",
 *                  example="WEB" ,
 *                  ),
 *                @OA\Property(
 *                  property="typ_id",
 *                  type="number",
 *                  default="1",
 *                  example="1" ,
 *                  ),
 *                @OA\Property(
 *                  property="total_fare",
 *                  type="double",
 *                  default="1000",
 *                  example="1000" ,
 *                  ),
 *                @OA\Property(
 *                  property="owner_fare",
 *                  type="double",
 *                  default="900",
 *                  example="900" ,
 *                  ),
 *                @OA\Property(
 *                  property="odbus_service_Charges",
 *                  type="double",
 *                  default="70",
 *                  example="70" ,
 *                  ),
 *                @OA\Property(
 *                  property="odbus_gst_charges",
 *                  type="number",
 *                  default="5",
 *                  example="5" ,
 *                  ),
 *                @OA\Property(
 *                  property="odbus_gst_amount",
 *                  type="double",
 *                  default="50",
 *                  example="50" ,
 *                  ),
 *                @OA\Property(
 *                  property="owner_gst_charges",
 *                  type="number",
 *                  default="5",
 *                  example="5" ,
 *                  ),
 *                @OA\Property(
 *                  property="owner_gst_amount",
 *                  type="double",
 *                  default="50",
 *                  example="50" ,
 *                  ),
 *                @OA\Property(
 *                  property="created_by",
 *                  type="string",
 *                  default="Customer",
 *                  example="Customer" ,
 *                  ),
 *                 @OA\Property(
 *                  property="bookingDetail",
 *                  type="array",
 *                  example={{
 *                    "bus_seats_id" : "49",
 *                    "passenger_name": "Bob",
 *                    "passenger_gender": "M",
 *                    "passenger_age": "22",
 *                    "created_by": "Customer"
 *                  }, {
 *                    "bus_seats_id" : "50",
 *                    "passenger_name": "Mom",
 *                    "passenger_gender": "F",
 *                    "passenger_age": "20",
 *                    "created_by": "Customer"
 *                  }},
 *                  @OA\Items(
 *                      @OA\Property(
 *                         property="bus_seats_id",
 *                         type="string",
 *                         example="ST1"
 *                      ),
 *                      @OA\Property(
 *                         property="passenger_name",
 *                         type="string",
 *                         example="Bob"
 *                      ),
 *                      @OA\Property(
 *                         property="passenger_gender",
 *                         type="string",
 *                         example="M"
 *                      ),
 *                      @OA\Property(
 *                         property="passenger_age",
 *                         type="string",
 *                         example="22"
 *                      ),
 *                      @OA\Property(
 *                         property="created_by",
 *                         type="string",
 *                         example="Customer"
 *                      ),
 *                    ),
 *                  ),
 *                ),
 *              ),
 *  ),
 *  @OA\Response(response="201", description="records added"),
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
    public function agentBooking(Request $request) {

        $token = JWTAuth::getToken();

        $user = JWTAuth::toUser($token);

         $data = $request->all();

        $clientRole = $user->role_id;
        $clientId = $user->id;

        // $data['bookingInfo']['origin']=$user->name;

        $bookingValidation = $this->agentBookingValidator->validate($data);
   
        if ($bookingValidation->fails()) {
         $errors = $bookingValidation->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
         try {
            $response =  $this->agentBookingService->agentBooking($data,$clientRole,$clientId);  
            if($response=='Bus_not_running'){
                return $this->errorResponse(Config::get('constants.BUS_NOT_RUNNING'),Response::HTTP_OK);
            }
            elseif($response =='Invalid Param'){
    
                return $this->errorResponse("Invalid Origin",Response::HTTP_OK);
        
            }elseif($response =='ReferenceNumber_empty'){
                return $this->errorResponse("Reference Number is required",Response::HTTP_OK);
            }

            elseif(isset($response['message'])){
             return $this->errorResponse($response['note'],Response::HTTP_OK);
             }
           else{
            return $this->successResponse($response,Config::get('constants.RECORD_ADDED'),Response::HTTP_CREATED);
           }
        }
        catch (Exception $e) {
            Log::info($e->getMessage());
             return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
        }      
    } 
}
