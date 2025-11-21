<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ClientBookingService;
use App\AppValidator\ClientBookingValidator;
use App\AppValidator\SeatBlockValidator;
use App\AppValidator\TicketConfirmValidator;
use App\AppValidator\ClientCancelTicketValidator;
use App\AppValidator\WalletBalanceValidator;
use App\AppValidator\ClientCancelTktValidator;
use App\AppValidator\BookingManageValidator;
use App\Models\OdbusCharges;
use Illuminate\Support\Facades\Log;
use App\Repositories\ClientBookingRepository;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ClientBookingController extends Controller
{

    use ApiResponser;
    
    protected $clientBookingService;
    protected $clientBookingValidator;
    protected $seatBlockValidator;
    protected $ticketConfirmValidator;
    protected $clientCancelTicketValidator;
    protected $clientCancelTktValidator;
    protected $bookingManageService;
    protected $clientBookingRepository;  
    

    public function __construct(ClientBookingService $clientBookingService,ClientBookingValidator $clientBookingValidator,SeatBlockValidator $seatBlockValidator,TicketConfirmValidator $ticketConfirmValidator,ClientCancelTicketValidator $clientCancelTicketValidator,ClientCancelTktValidator $clientCancelTktValidator,BookingManageValidator $bookingManageValidator,ClientBookingRepository $clientBookingRepository)
    {
        $this->clientBookingService = $clientBookingService;  
        $this->clientBookingValidator = $clientBookingValidator;
        $this->seatBlockValidator = $seatBlockValidator; 
        $this->ticketConfirmValidator = $ticketConfirmValidator;   
        $this->clientCancelTicketValidator = $clientCancelTicketValidator;  
        $this->clientCancelTktValidator = $clientCancelTktValidator; 
        $this->bookingManageValidator = $bookingManageValidator;   
    }
        /**
     * @OA\Post(
     *     path="/api/PassengerInfo",
     *     tags={"PassengerInfo API(Client Booking Process)"},
     *     summary="Capturing customer details in a booking process by a client",
     *     @OA\RequestBody(
     *        required = true,
     *     description="Capturing passenger details in a booking process by a client",
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
     *                property="bookingInfo",
     *                type="object",
     *                @OA\Property(
     *                  property="bus_id",
     *                  type="number",
     *                  default=1,
     *                  example=1
     *                  ),
     *                @OA\Property(
     *                  property="source_id",
     *                  type="number",
     *                  default=82,
     *                  example=82
     *                  ),
     *                @OA\Property(
     *                  property="destination_id",
     *                  type="number",
     *                  default=434,
     *                  example=434
     *                  ),
     *                @OA\Property(
     *                  property="journey_dt",
     *                  type="string",
     *                  default="2022-06-30",
     *                  example="2022-06-30" ,
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
     *                 @OA\Property(
     *                  property="bookingDetail",
     *                  type="array",
     *                  example={{
     *                    "bus_seats_id" : "2694",
     *                    "passenger_name": "Bob",
     *                    "passenger_gender": "M",
     *                    "passenger_age": "22"
     *                  }, {
     *                    "bus_seats_id" : "2755",
     *                    "passenger_name": "Mom",
     *                    "passenger_gender": "F",
     *                    "passenger_age": "20"
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
    public function clientBooking(Request $request) {
        
        $advDays = OdbusCharges::where('user_id', '1')->first()->advance_days_show;
        $token = JWTAuth::getToken();
        
        $user = JWTAuth::toUser($token);
        $data = $request->all();

        // Log::info("clientBooking");
        // Log::info($data);

        $clientRole = $user->role_id;
        $clientId = $user->id;

        $todayDate = Date('Y-m-d');
        $validTillDate = Date('Y-m-d', strtotime($todayDate. " + $advDays days"));
        $data['bookingInfo']['user_id']=$user->id;
        $bookingValidation = $this->clientBookingValidator->validate($data);
   
        if ($bookingValidation->fails()) {
         $errors = $bookingValidation->errors();
         return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try { 
          $response = $this->clientBookingService->clientBooking($data,$clientRole,$clientId); 

          //Log::info($response);
        
          if( $data['bookingInfo']['journey_dt'] > $validTillDate ||  $data['bookingInfo']['journey_dt'] < $todayDate ){
          
          return $this->errorResponse('wrong date format or not in range - '.$data['bookingInfo']['journey_dt'],Response::HTTP_OK);
      
          }elseif($response=='Bus_not_running'){
              return $this->errorResponse(Config::get('constants.BUS_NOT_RUNNING'),Response::HTTP_OK);
          }
          elseif(isset($response['status']) && $response['status']=='Gender Error'){
    
            return $this->errorResponse($response['message'],Response::HTTP_OK);
    
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
        //  try {
        //     $response =  $this->clientBookingService->clientBooking($data);  
            
        //     if(isset($response['message'])){
        //      return $this->errorResponse($response['note'],Response::HTTP_OK);
        //      }
        //    else{
        //     return $this->successResponse($response,Config::get('constants.RECORD_ADDED'),Response::HTTP_CREATED);
        //    }
        // }
        catch (Exception $e) {

          Log::info($e->getMessage());

             return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
        }      
    } 

        /**
     * @OA\Post(
     *     path="/api/SeatBlock",
     *     tags={"SeatBlock API(Client Booking Process)"},
     *     description="Block seats for further payment process",
     *     summary="Block seats for further payment process",
     *     @OA\Parameter(
     *          name="transaction_id",
     *          description="transaction id against booking",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="20220404141311561229"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="customer_gst_status",
     *          description="customer gst required or not(0:not required,1:required)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
    *              enum={"0", "1"}
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="customer_gst_number",
     *          description="customer gst number",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="2323232323"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="customer_gst_business_name",
     *          description="customer gst business name",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Bob"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="customer_gst_business_email",
     *          description="customer gst business email",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="example@gmail.com"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="customer_gst_business_address",
     *          description="customer gst business address",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="example"
     *          )
     *      ),
     *  @OA\Response(response="201", description="Seats blocked for payment process"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response="406", description="Seats already booked"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={{ "apiAuth": {} }}
     * )
     * 
     */
    public function seatBlock(Request $request)
    {   
        $data = $request->all();

        // Log::info("seatBlock");
        // Log::info($data);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token); 
        $clientRole = $user->role_id;
        $seatBlockValidation = $this->seatBlockValidator->validate($data);
        $clientId = $user->id;
  
        if ($seatBlockValidation->fails()) {
        $errors = $seatBlockValidation->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try {
            $response = $this->clientBookingService->seatBlock($request,$clientRole,$clientId);

            
          // Log::info($response);

            if(isset($response['status']) && $response['status']=="Success" ){

              return $this->successResponse($response,Config::get('constants.SEAT_BLOCKED_FOR_PAYMENT'),Response::HTTP_CREATED);    
  
           }elseif($response=='BUS_SEIZED'){
  
              return $this->errorResponse(Config::get('constants.BUS_SEIZED'),Response::HTTP_OK);
  
           }elseif($response=='SEAT UN-AVAIL'){
  
             // return $this->successResponse($response,Config::get('constants.HOLD'),Response::HTTP_OK);
              return $this->errorResponse(Config::get('constants.HOLD'),Response::HTTP_OK);
              
           }elseif($response=='BUS_CANCELLED'){
  
              return $this->errorResponse(Config::get('constants.BUS_CANCELLED'),Response::HTTP_OK); 
              
          }elseif($response=='SEAT_BLOCKED'){
  
              return $this->errorResponse(Config::get('constants.SEAT_BLOCKED'),Response::HTTP_OK); 
              
          }else{
              return $this->errorResponse($response,Response::HTTP_OK); 
          }

          
            // switch($response){
            //     case('BUS_SEIZED'):  
            //     return $this->errorResponse(Config::get('constants.BUS_SEIZED'),Response::HTTP_OK);
            //     break;
            //     case('SEAT UN-AVAIL'):  
            //         return $this->successResponse($response,Config::get('constants.HOLD'),Response::HTTP_OK);
            //     break;
            //     case('BUS_CANCELLED'):    
            //         return $this->errorResponse(Config::get('constants.BUS_CANCELLED'),Response::HTTP_OK);   
            //     break;
            //     case('SEAT_BLOCKED'):    
            //         return $this->errorResponse(Config::get('constants.SEAT_BLOCKED'),Response::HTTP_OK);   
            //     break;
            // }
        }
        catch (Exception $e) {
             return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
        }        
    }
    
        /**
     * @OA\Post(
     *     path="/api/TicketConfirmation",
     *     tags={"TicketConfirmation API(Client Booking Process)"},
     *     description="Ticket booking confirmed with seats booked status",
     *     summary="Ticket booking confirmed with seats booked status",
     *     @OA\Parameter(
     *          name="transaction_id",
     *          description="transaction id against booking",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="20220404141311561229"
     *          )
     *      ),
     *  @OA\Response(response="200", description="Tickets booked successfully"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response="406", description="Seats already booked"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={{ "apiAuth": {} }}
     * )
     * 
     */
    public function ticketConfirmation(Request $request){

        $data = $request->all();

        // Log::info("ticketConfirmation");
        // Log::info($data);
        
        $ticketConfValidation = $this->ticketConfirmValidator->validate($data);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $clientRole = $user->role_id;
        $data = $request->all();
        
        $data['client_id']=$user->id;
        $data['client_name']=$user->name;
    
        if ($ticketConfValidation->fails()) {
        $errors = $ticketConfValidation->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        }  
        try{  
            $response = $this->clientBookingService->ticketConfirmation($data,$clientRole); 

            switch($response){
              case('SEAT UN-AVAIL'):  
                  return $this->successResponse($response,Config::get('constants.HOLD'),Response::HTTP_OK);
              break;
              case('BUS_CANCELLED'):    
                  return $this->errorResponse(Config::get('constants.BUS_CANCELLED'),Response::HTTP_OK);   
              break;
              case('SEAT_BLOCKED'):    
                  return $this->errorResponse(Config::get('constants.SEAT_BLOCKED'),Response::HTTP_OK);   
              break;
          }
   
            return $this->successResponse($response,Config::get('constants.TICKET_CONFIRMED'),Response::HTTP_OK);
         }
        catch (Exception $e) {
            Log::Info($e->getMessage());
            return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
          }     
    }
    /////////////admin client panel use/////////////////////
    public function clientCancelTicket(Request $request) {

        $data = $request->all();
        $cancelTicketValidator = $this->clientCancelTicketValidator->validate($data);

        if ($cancelTicketValidator->fails()) {
        $errors = $cancelTicketValidator->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try {
            $response = $this->clientBookingService->clientCancelTicket($data);  
            switch($response){
            //   case('PNR_NOT_MATCH'):
            //     return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
            //     break;

            case('Ticket_already_cancelled'):
              return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
              break;
              case('INV_CLIENT'):
                return $this->errorResponse(Config::get('constants.INVALID_CLIENT'),Response::HTTP_PARTIAL_CONTENT);
                break;
              case('CANCEL_NOT_ALLOWED'):
                return $this->errorResponse(Config::get('constants.CANCEL_NOT_ALLOWED'),Response::HTTP_PARTIAL_CONTENT);
                break;
            }
          return $this->successResponse($response,Config::get('constants.TICKET_CANCELLED'),Response::HTTP_OK);  
         }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 

    public function clientCancelTicketInfo(Request $request) {

        $data = $request->all();
        $cancelTicketValidator = $this->clientCancelTicketValidator->validate($data);

        if ($cancelTicketValidator->fails()) {
        $errors = $cancelTicketValidator->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try {
            $response = $this->clientBookingService->clientCancelTicketInfo($data);  
            switch($response){             
               case('Ticket_already_cancelled'):
                return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
                break;

              case('INV_CLIENT'):
                return $this->errorResponse(Config::get('constants.INVALID_CLIENT'),Response::HTTP_PARTIAL_CONTENT);
                break;
              case('CANCEL_NOT_ALLOWED'):
                return $this->errorResponse(Config::get('constants.CANCEL_NOT_ALLOWED'),Response::HTTP_PARTIAL_CONTENT);
                break;
            }
          return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);  
         }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 

    /////////client panel use//////////////
     /**
     * @OA\Post(
     *     path="/api/ClientCancelTicketinfo",
     *     tags={"ClientCancelTicketinfo API(Ticket cancellation detail information)"},
     *     description="Ticket cancellation detail information",
     *     summary="Ticket cancellation detail information",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr of booked ticket",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODCL60276"
     *          )
     *      ),
     *  @OA\Response(response="200", description="Record Fetched Successfully"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response="406", description="Seats already booked"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={{ "apiAuth": {} }}
     * )
     * 
     */
    public function clientCancelTicketInfos(Request $request) {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $data = $request->all();
        
        $data['user_id'] = $user->id;
       
        $cancelTicketValidator = $this->clientCancelTktValidator->validate($data);

        if ($cancelTicketValidator->fails()) {
        $errors = $cancelTicketValidator->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try {
            $response = $this->clientBookingService->clientCancelTicketInfos($data);  
            switch($response){

              case('Ticket_already_cancelled'):
                return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
                break;
                
              case('INV_CLIENT'):
                return $this->errorResponse(Config::get('constants.INVALID_CLIENT'),Response::HTTP_PARTIAL_CONTENT);
                break;
              case('CANCEL_NOT_ALLOWED'):
                return $this->errorResponse(Config::get('constants.CANCEL_NOT_ALLOWED'),Response::HTTP_PARTIAL_CONTENT);
                break;
            }
          return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);  
         }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 
    /**
     * @OA\Post(
     *     path="/api/ClientTicketCancellation",
     *     tags={"ClientTicketCancellation API(Ticket cancellation)"},
     *     description="Ticket cancellation",
     *     summary="Ticket cancellation",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr of booked ticket",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODCL60276"
     *          )
     *      ),
     *  @OA\Response(response="200", description="Record Fetched Successfully"),
     *  @OA\Response(response=206, description="validation error"),
     *  @OA\Response(response=400, description="Bad request"),
     *  @OA\Response(response=401, description="Unauthorized access"),
     *  @OA\Response(response=404, description="No record found"),
     *  @OA\Response(response="406", description="Seats already booked"),
     *  @OA\Response(response=500, description="Internal server error"),
     *  @OA\Response(response=502, description="Bad gateway"),
     *  @OA\Response(response=503, description="Service unavailable"),
     *  @OA\Response(response=504, description="Gateway timeout"),
     *     security={{ "apiAuth": {} }}
     * )
     * 
     */
    public function clientTicketCancel(Request $request) {

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $data = $request->all();  
        $data['user_id'] = $user->id;

        $cancelTicketValidator = $this->clientCancelTktValidator->validate($data);

        if ($cancelTicketValidator->fails()) {
        $errors = $cancelTicketValidator->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try {
            $response = $this->clientBookingService->clientTicketCancel($data);  
            switch($response){

              case('Ticket_already_cancelled'):
                return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
                break;
                
              case('INV_CLIENT'):
                return $this->errorResponse(Config::get('constants.INVALID_CLIENT'),Response::HTTP_PARTIAL_CONTENT);
                break;
              case('CANCEL_NOT_ALLOWED'):
                return $this->errorResponse(Config::get('constants.CANCEL_NOT_ALLOWED'),Response::HTTP_PARTIAL_CONTENT);
                break;
            }
          return $this->successResponse($response,Config::get('constants.TICKET_CANCELLED'),Response::HTTP_OK);  
         }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 
    
     /**
     * @OA\Post(
     *     path="/api/TicketDetails",
     *     tags={"TicketDetails API"},
     *     description="Ticket Details",
     *     summary="Ticket Details",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODCL59164"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="8908965606"
     *          )
     *      ),
     *  @OA\Response(response="200", description="get all Booking details of a customer"),
     *  @OA\Response(response=206, description="Validation error: Not a valid pnr or Mobile number"),
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
    public function ticketDetails(Request $request) {     

      $data = $request->all();
      $bookingManageValidator = $this->bookingManageValidator->validate($data);

      if ($bookingManageValidator->fails()) {
      $errors = $bookingManageValidator->errors();
      return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
      } 
      try {
        $response =  $this->clientBookingService->ticketDetails($request);  
        if($response == 'PNR_NOT_MATCH'){
          return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
        }elseif($response == 'RECORD_NOT_FOUND'){
          return $this->errorResponse(Config::get('constants.RECORD_NOT_FOUND'),Response::HTTP_PARTIAL_CONTENT);
        }         
        else{
          return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
        }   
      }
      catch (Exception $e) {    
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
      }      
    } 

    public function walletBalance(Request $request){

      $data = $request->only([
        'ClientId'
      ]);   
  

      $walletBalanceValidator = WalletBalanceValidator::validate($data);

      if ($walletBalanceValidator->fails()) {
      $errors = $walletBalanceValidator->errors();
      return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
      } 
      
      try {
        $response =  $this->clientBookingService->walletBalance($data);  
        if($response=='Unauthorized'){
          return $this->errorResponse(Config::get('constants.CLIENT_UNAUTHORIZED'),Response::HTTP_UNAUTHORIZED);
        }else{
          return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
        }
       
      }
      catch (Exception $e) {    
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
      }     
      
    }

   public function PaytmdriverDetailApi(){

    PaytmdriverCallBackAPI('ODCL51335');
    
  }
}
