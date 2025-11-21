<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CancelTicketService;
use App\AppValidator\CancelTicketValidator;

class CancelTicketController extends Controller
{

    use ApiResponser;
    /**
     * @var cancelTicketService
     */
    protected $cancelTicketService;
    protected $cancelTicketValidator;
    /**
     * cancelTicketController Constructor
     *
     * @param CancelTicketService $cancelTicketService
     *
     */
    public function __construct(CancelTicketService $cancelTicketService,CancelTicketValidator $cancelTicketValidator)
    {
        $this->cancelTicketService = $cancelTicketService; 
        $this->cancelTicketValidator = $cancelTicketValidator;     
    }

/**
 * @OA\Post(
 *     path="/api/CancelTicket",
 *     tags={"CancelTicket API"},
 *     description="refund initiated for ticket cancellation",
 *     summary="refund initiated for ticket cancellation",
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
 *          name="phone",
 *          description="mobile number",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *              example="9090909090"
 *          )
 *      ),
 *  @OA\Response(response="201", description=" Refund initiated on cancellation of ticket"),
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

 public function CancelDolphinSeat(Request $request){

      $data = $request->all();    
      try {
        $response =  $this->cancelTicketService->CancelDolphinSeat($request);
        return $this->successResponse($response,Config::get('constants.REFUND_INITIATED'),Response::HTTP_CREATED);
      
      }
      catch (Exception $e) {
        return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
      }      
} 

    public function cancelTicket(Request $request) {
          $data = $request->all();
          $cancelTicketValidator = $this->cancelTicketValidator->validate($data);

          if ($cancelTicketValidator->fails()) {
          $errors = $cancelTicketValidator->errors();
          return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
          } 
         try {
          $response =  $this->cancelTicketService->cancelTicket($request); 
          if($response == 'refunded'){
            return $this->successResponse($response,Config::get('constants.REFUNDED_COMPLETED'));
          }

          elseif($response == 'PNR_NOT_MATCH'){
            return $this->errorResponse(Config::get('constants.PNR_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
          }

          elseif($response == 'MOBILE_NOT_MATCH'){
            return $this->errorResponse(Config::get('constants.MOBILE_NOT_MATCH'),Response::HTTP_PARTIAL_CONTENT);
          }

          elseif($response == 'Ticket_already_cancelled'){
            return $this->errorResponse("Ticket Already cancelled. Please contact Odbus Support Team",Response::HTTP_PARTIAL_CONTENT);
           }  
  
          
          else{
            return $this->successResponse($response,Config::get('constants.REFUND_INITIATED'),Response::HTTP_CREATED);
          }
          // elseif($response == 'noPayment'){
          //   return $this->successResponse($response,Config::get('constants.NO_PAYMENT'));
          // }
          
       }
       catch (Exception $e) {
           return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
         }      
   } 
}