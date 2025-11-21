<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Models\OdbusCharges;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BookTicketService;
use App\AppValidator\BookTicketValidator;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class BookTicketController extends Controller
{

    use ApiResponser;
    /**
     * @var bookTicketService
     */
    protected $bookTicketService;
    protected $bookTicketValidator;
    /**
     * BookTicketController Constructor
     *
     * @param BookTicketService $bookTicketService
     *
     */
    public function __construct(BookTicketService $bookTicketService,BookTicketValidator $bookTicketValidator)
    {
        $this->bookTicketService = $bookTicketService;  
        $this->bookTicketValidator = $bookTicketValidator;      
    }

/**
 * @OA\Post(
 *     path="/api/BookTicket",
 *     tags={"BookTicket API"},
 *     summary="Ticket Booking with customer details",
 *     @OA\RequestBody(
 *        required = true,
 *     description="Ticket Booking with customer details",
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
 *                  property="coupon_code",
 *                  type="string",
 *                  default="ODTEST3",
 *                  example="ODTEST3"
 *                  ),
 *                @OA\Property(
 *                  property="user_id",
 *                  type="number",
 *                  default=1,
 *                  example=1
 *                  ),
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
 *                  property="journey_date",
 *                  type="string",
 *                  default="2022-03-09",
 *                  example="2022-03-09" ,
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
 *                  default="ANDROID",
 *                  example="ANDROID" ,
 *                  ),
 *                @OA\Property(
 *                  property="typ_id",
 *                  type="number",
 *                  default="1",
 *                  example="1" ,
 *                  ),
 *                @OA\Property(
 *                  property="created_by",
 *                  type="string",
 *                  default="Customer",
 *                  example="Customer" ,
 *                  ),
 *                @OA\Property(
 *                  property="CompanyID",
 *                  type="integer",
 *                  default="",
 *                  example="" ,
 *                  ),
 *                @OA\Property(
 *                  property="PickupID",
 *                  type="integer",
 *                  default="",
 *                  example="" ,
 *                  ),
 *                @OA\Property(
 *                  property="DropID",
 *                  type="integer",
 *                  default="",
 *                  example="" ,
 *                  ),
 *                @OA\Property(
 *                  property="RouteTimeID",
 *                  type="integer",
 *                  default="",
 *                  example="" ,
 *                  ),
 *                @OA\Property(
 *                  property="ReferenceNumber",
 *                  type="integer",
 *                  default="",
 *                  example="" ,
 *                  ),
 *                 @OA\Property(
 *                  property="bookingDetail",
 *                  type="array",
 *                  example={{
 *                    "bus_seats_id" : "2755",
 *                    "passenger_name": "Bob",
 *                    "passenger_gender": "M",
 *                    "passenger_age": "22",
 *                    "created_by": "Customer"
 *                  }},
 *                  @OA\Items(
 *                      @OA\Property(
 *                         property="bus_seats_id",
 *                         type="number",
 *                         example="2755"
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
 *  @OA\Response(response="201", description="Records added"),
 *  @OA\Response(response=206, description="Validation error"),
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
    public function bookTicket(Request $request) {

        $advDays = OdbusCharges::where('user_id', '1')->first()->advance_days_show;
        
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $data = $request->all();
       // $data['bookingInfo']['origin']=$user->name;
        $clientRole = $user->role_id;
        $clientId = $user->id;
        $bookTicketValidation = $this->bookTicketValidator->validate($data);

        $todayDate = Date('Y-m-d');
        
        //$validTillDate = Date('Y-m-d', strtotime('+15 days'));
        $validTillDate = Date('Y-m-d', strtotime($todayDate. " + $advDays days"));
        
        if ($bookTicketValidation->fails()) {
            $errors = $bookTicketValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try { 
             $response =  $this->bookTicketService->bookTicket($request,$clientRole,$clientId); 
            
            if( $data['bookingInfo']['journey_date'] > $validTillDate ||  $data['bookingInfo']['journey_date'] < $todayDate ){
            return $this->errorResponse('wrong date format or not in range - '.$data['bookingInfo']['journey_date'],Response::HTTP_OK);
        
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
            
            else{
                return $this->successResponse($response,Config::get('constants.RECORD_ADDED'),Response::HTTP_CREATED);
            }
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
          }      
    } 
}
