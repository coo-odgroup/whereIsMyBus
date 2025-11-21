<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BookingManageService;
use App\AppValidator\BookingManageValidator;
use App\AppValidator\AgentCancelTicketValidator;
use Illuminate\Support\Facades\Log;
use PDF;



class BookingManageController extends Controller
{

    use ApiResponser;
    /**
     * @var BookingManageService
     */
    protected $bookTicketService;
    protected $bookTicketValidator;
    protected $bookingManageService;
    protected $agentCancelTicketValidator;
    /**
     * BookingManageController Constructor
     *
     * @param BookingManageService $bookingManageService
     *
     */
    public function __construct(BookingManageService $bookingManageService,BookingManageValidator $bookingManageValidator,AgentCancelTicketValidator $agentCancelTicketValidator)
    {
        $this->bookingManageService = $bookingManageService;  
        $this->bookingManageValidator = $bookingManageValidator; 
        $this->agentCancelTicketValidator = $agentCancelTicketValidator;      
    }
    /**
     * @OA\Post(
     *     path="/api/JourneyDetails",
     *     tags={"Journey details of a costumer"},
     *     description="Journey Details",
     *     summary="Journey Details",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODM5163863"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="9090909090"
     *          )
     *      ),
     *  @OA\Response(response="200", description="get all Journey details"),
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
    public function getJourneyDetails(Request $request) {
         $data = $request->all();
           $bookingManageValidator = $this->bookingManageValidator->validate($data);
   
        if ($bookingManageValidator->fails()) {
        $errors = $bookingManageValidator->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
        } 
        try {
          $response =  $this->bookingManageService->getJourneyDetails($request);  
          if($response == 'PNR_NOT_MATCH'){
           return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
          }elseif($response == 'MOBILE_NOT_MATCH'){
           return $this->errorResponse(Config::get('constants.MOBILE_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
          }         
          else{
           return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
          }
          
      }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
          }      
    } 
    /**
     * @OA\Post(
     *     path="/api/PassengerDetails",
     *     tags={"Passenger details"},
     *     description="Passenger Details",
     *     summary="Passenger Details",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODM5163863"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="9090909090"
     *          )
     *      ),
     *  @OA\Response(response="200", description="get all Passenger details"),
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
    public function getPassengerDetails(Request $request) {
      $data = $request->all();
        $bookingManageValidator = $this->bookingManageValidator->validate($data);

     if ($bookingManageValidator->fails()) {
     $errors = $bookingManageValidator->errors();
     return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
     } 
     try {
      $response =  $this->bookingManageService->getPassengerDetails($request);  
      if($response == 'PNR_NOT_MATCH'){
       return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
      }elseif($response == 'MOBILE_NOT_MATCH'){
       return $this->errorResponse(Config::get('constants.MOBILE_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
      }         
      else{
       return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
      }
      
  }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 
     /**
     * @OA\Post(
     *     path="/api/BookingDetails",
     *     tags={"Booking details of a customer"},
     *     description="Booking Details",
     *     summary="Booking Details",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODM5163863"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="9090909090"
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
    public function getBookingDetails(Request $request) {     

       $data = $request->all();
        $bookingManageValidator = $this->bookingManageValidator->validate($data);

     if ($bookingManageValidator->fails()) {
     $errors = $bookingManageValidator->errors();
     return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
     } 

      

       try {
         $response =  $this->bookingManageService->getBookingDetails($request);  
         if($response == 'PNR_NOT_MATCH'){
          return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
         }elseif($response == 'MOBILE_NOT_MATCH'){
          return $this->errorResponse(Config::get('constants.MOBILE_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
         }         
         else{
          return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
         }
         
     }
     catch (Exception $e) {
      Log::info($e->getMessage());             
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 
    /**
     * @OA\Post(
     *     path="/api/EmailSms",
     *     tags={"Resending Ticket via Email/Sms"},
     *     description="Resending Ticket via Email/Sms",
     *     summary="Resending Ticket via Email/Sms",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODM5163863"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="9090909090"
     *          )
     *      ),
     *  @OA\Response(response="200", description="send email/sms"),
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
    public function emailSms(Request $request) {
      $data = $request->all();
        $bookingManageValidator = $this->bookingManageValidator->validate($data);

     if ($bookingManageValidator->fails()) {
     $errors = $bookingManageValidator->errors();
     return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
     } 
       try {
         $response =  $this->bookingManageService->emailSms($request);  
         if($response=='Invalid request'){
          return $this->errorResponse($response,Response::HTTP_PARTIAL_CONTENT);
         }else{
          return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
         }   
     }
     catch (Exception $e) {
         Log::info($e->getMessage());   
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 
    /**
     * @OA\Post(
     *     path="/api/cancelTicketInfo",
     *     tags={"Get detail information on cancellation rules and policies"},
     *     description="Get detail information on cancellation rules and policies",
     *     summary="Get detail information on cancellation rules and policies",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODM5163863"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="9090909090"
     *          )
     *      ),
     *  @OA\Response(response="200", description="Get detail information of ticket cancellation rules and policies"),
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

    public function cancelTicketInfo(Request $request) {
      $data = $request->all();
        $bookingManageValidator = $this->bookingManageValidator->validate($data);

     if ($bookingManageValidator->fails()) {
     $errors = $bookingManageValidator->errors();
     return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
     } 
       try {
        $response =  $this->bookingManageService->cancelTicketInfo($request);  

        if($response == 'PNR_NOT_MATCH'){
          return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
         }elseif($response == 'MOBILE_NOT_MATCH'){
          return $this->errorResponse(Config::get('constants.MOBILE_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
         }
         elseif($response == 'CANCEL_NOT_ALLOWED'){
          return $this->errorResponse(Config::get('constants.CANCEL_NOT_ALLOWED'),Response::HTTP_PARTIAL_CONTENT);
         } 
         elseif($response == 'Ticket_already_cancelled'){
          return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
         }  

         
         else{
          return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
         }    
     }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 


    public function downloadTicket($pnr)
    {

        $data= $this->bookingManageService->downloadTicket($pnr);  

      $pdf = PDF::loadView('htmlPdf',$data);
      PDF::loadView('htmlPdf',$data)->save(public_path().'/ticketpdf/'.$pnr.'.pdf');

       // $pdf = PDF::loadView('htmlPdf');
        return $pdf->download($pnr.'.pdf');
    }


    
    /**
     * @OA\Post(
     *     path="/api/AgentcancelTicketOTP",
     *     tags={"Agent Ticket Cancel confirmation otp send to costumer"},
     *     description="Agent Ticket Cancel send otp to costumer for confirmation",
     *     summary="Agent Ticket Cancel send otp to costumer for conformation",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODM5163863"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="9090909090"
     *          )
     *      ),
     *  @OA\Response(response="200", description="Agent ticket cancel confirmation otp sent to       costumer "),
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
    public function agentcancelTicketOTP(Request $request) {
      $data = $request->all();
        $bookingManageValidator = $this->bookingManageValidator->validate($data);

     if ($bookingManageValidator->fails()) {
     $errors = $bookingManageValidator->errors();
     return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
     } 
       try {
        $response =  $this->bookingManageService->agentcancelTicketOTP($request);  
       
        if($response == 'PNR_NOT_MATCH'){
          return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
         }elseif($response == 'MOBILE_NOT_MATCH'){
          return $this->errorResponse(Config::get('constants.MOBILE_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
         } 
         elseif($response == 'Ticket_already_cancelled'){
          return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
         }        
         else{
          return $this->successResponse($response,Config::get('constants.OTP_SENT_CUSTOMER'),Response::HTTP_OK);
         }    
     }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    } 
    /**
     * @OA\Post(
     *     path="/api/AgentcancelTicket",
     *     tags={"Agent Ticket Cancellation"},
     *     description="Agent Ticket Cancellation",
     *     summary="Agent Ticket Cancellation",
     *     @OA\Parameter(
     *          name="pnr",
     *          description="pnr",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="ODM5163863"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="mobile",
     *          description="mobile",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="9090909090"
     *          )
     *      ),
     *     @OA\Parameter(
     *          name="otp",
     *          description="otp",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="1212121"
     *          )
     *      ),
     *  @OA\Response(response="200", description="Agent ticket cancellation successful "),
     *  @OA\Response(response=206, description="Validation error: Not a valid pnr or Mobile number or otp"),
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
    public function agentcancelTicket(Request $request) {
      $data = $request->all();
        $bookingManageValidator = $this->agentCancelTicketValidator->validate($data);

     if ($bookingManageValidator->fails()) {
     $errors = $bookingManageValidator->errors();
     return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
     } 
       try {
        $response =  $this->bookingManageService->agentcancelTicket($request);  
        switch($response){
          case('PNR_NOT_MATCH'):
            return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
            break;
          case('MOBILE_NOT_MATCH'):
            return $this->errorResponse(Config::get('constants.MOBILE_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
            break;

            case('Ticket_already_cancelled'):
              return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
              break;
          case('CANCEL_NOT_ALLOWED'):
            return $this->errorResponse(Config::get('constants.CANCEL_NOT_ALLOWED'),Response::HTTP_PARTIAL_CONTENT);
            break;
          case('INVALID_OTP'):
            return $this->errorResponse(Config::get('constants.OTP_INVALID'),Response::HTTP_PARTIAL_CONTENT);
            break;
        }
      return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);  
     }
     catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
       }      
    }  
    
    public function pnrDetail($pnr){

      try {
      $result = $this->bookingManageService->getPnrDetails($pnr);
   
      if($result=='INVALID_PNR'){

        return $this->errorResponse(Config::get('constants.INVALID_PNR'),Response::HTTP_PARTIAL_CONTENT);

      } else{
        return $this->successResponse($result,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK); 
      }     
      
    }   
      catch (Exception $e) {     
        return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
      } 

    }

    public function FeedbackCronJob(){     
      $result = $this->bookingManageService->FeedbackCronJob(); 
       return $this->successResponse($result,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK); 

    }

    public function GetPnr($trans_id){     
      $result = $this->bookingManageService->GetPnr($trans_id);   
      if($result=='notfound'){
        return $this->errorResponse("Booking not found",Response::HTTP_PARTIAL_CONTENT);
      }else{
        return $this->successResponse($result,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK); 
      }
     
    }

    
}
