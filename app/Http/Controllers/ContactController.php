<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ContactService;
use App\Models\Users;
use Illuminate\Support\Facades\Log;
use App\Repositories\ContactRepository;
use App\AppValidator\ContactValidator;

class ContactController extends Controller
{
    use ApiResponser;
    protected $contactService;
    protected $contactValidator;  
  
    public function __construct(ContactService $contactService, ContactValidator $contactValidator)
        {
            $this->contactService = $contactService;
            $this->contactValidator = $contactValidator;  
        }
/**
         * @OA\Post(
         *     path="/api/saveContacts",
         *     tags={"Add Contacts"},
         *     description="Add Contacts",
         *     summary="Add Contacts",
         *     @OA\Parameter(
         *          name="name",
         *          description="customer name",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="email",
         *          description="customer email",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="phone",
         *          description="customer mobile no",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="service",
         *          description="Type of service required by a customer",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              enum={"Select Services", "Feedback", "Complaints","Marketing","Agency Enquiry", "Solutions Enquiry"}
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="message",
         *          description="detail message about the service required by a customer",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="user_id",
         *          description="User Id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              default=1,
         *          )
         *      ),
         *  @OA\Response(response="200", description="Add Contacts of a acustomer"),
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
    public function save(Request $request)
        {

            $data = $request->all();
      
            $contactValidation = $this->contactValidator->validate($data);
      
            if ($contactValidation->fails()) {
            $errors = $contactValidation->errors();
            return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
            } 
            try {
            $response =  $this->contactService->save($request); 
            return $this->successResponse($response,Config::get('constants.RECORD_ADDED'),Response::HTTP_CREATED);
            }
            catch (Exception $e) { 
            
                return $this->errorResponse($e->getMessage(),Response::HTTP_NOT_FOUND);
            }	
    
    }
 

}
