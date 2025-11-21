<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\UsersService;
use App\Services\UserService;
use Illuminate\Support\Facades\Config;
use App\Traits\ApiResponser;
use InvalidArgumentException;
use App\AppValidator\UserValidator;
use App\AppValidator\AgentDetailsValidator;
use App\AppValidator\AgentLoginValidator;
use App\AppValidator\ClientValidator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;

class UserController extends Controller
{
   
    use ApiResponser;    
      
    protected $usersService;
    protected $userService;
    protected $userValidator;
    protected $agentDetailsValidator;
    protected $agentLoginValidator;
    protected $clientValidator;

    public function __construct(UsersService $usersService,UserService $userService,UserValidator $userValidator,AgentLoginValidator $agentLoginValidator,AgentDetailsValidator $agentDetailsValidator,ClientValidator $clientValidator)
    {
        $this->usersService = $usersService;
        $this->userService = $userService;
        $this->userValidator = $userValidator;    
        $this->agentLoginValidator = $agentLoginValidator; 
        $this->agentDetailsValidator = $agentDetailsValidator;  
        $this->clientValidator = $clientValidator;     
    }
//////////////////client Login////////////////////////////////////////////////////
/**
 * @OA\Post(
 *     path="/api/ClientLogin",
 *     tags={"ClientLogin API"},
 *     description="client login to generate client access token",
 *     summary="client login to generate client access token",
 *     @OA\Parameter(
 *          name="client_id",
 *          description="client_id of client",
 *          required=true,  
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="password",
 *          description="password of client",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *          format= "password"
 *          )
 *      ),
 *     @OA\Response(response="200", description="client access token generated"),
 *     @OA\Response(response="422", description="wrong credentials"),
 *     @OA\Response(response="206", description="not a valid credential"),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=401, description="Unauthorized access"),
 *     @OA\Response(response=404, description="No record found"),
 *     @OA\Response(response=500, description="Internal server error"),
 *     @OA\Response(response=502, description="Bad gateway"),
 *     @OA\Response(response=503, description="Service unavailable"),
 *     @OA\Response(response=504, description="Gateway timeout"),
 * )
 * 
 */
public function clientLogin(Request $request){  

  $data = $request->all();                                                 
  $clientValidation = ClientValidator::validate($data);
  if ($clientValidation->fails()) {
    $errors = $clientValidation->errors();
    return self::errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
  }
  try {

    $data['status']=1;

   // dd($data);

    if (! $token = auth()->attempt($data)) {
      return self::errorResponse(Config::get('constants.WRONG_CREDENTIALS'),Response::HTTP_UNPROCESSABLE_ENTITY );
  }
  $loginClient =  self::createNewToken($token);
  User::where('id', $loginClient['user']->id)->update(['client_access_token' => $token ]);
  // User::where('client_id', $request['client_id'])->update(['client_access_token' => $token ]);
  //return $this->successResponse($loginClient,Config::get('constants.CLIENT_TOKEN'),Response::HTTP_OK);



  return ApiResponser::successResponse($token,Config::get('constants.CLIENT_TOKEN'),Response::HTTP_OK);
}
  catch (Exception $e) {
   return ApiResponser::errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
} 
}
protected function createNewToken($token){
    $loginClient = [  
         'access_token' => $token,
         'token_type' => 'bearer',
         'expires_in' => Auth()->factory()->getTTL() * 60,
         'user' => Auth()->user()   
   ]; 
   return  $loginClient;
  }
/**
 * @OA\Get(
 *  path="/api/ClientDetails",
 *  summary="Get client details",
 *  tags={"Authorized Client Details"},
 *  @OA\Response(response=200, description="Authorized Client details"),
 *  @OA\Response(response=206, description="validation error"),
 *  @OA\Response(response=400, description="Bad request"),
 *  @OA\Response(response=401, description="Unauthorized access"),
 *  @OA\Response(response=404, description="No record found"),
 *  @OA\Response(response=500, description="Internal server error"),
 *  @OA\Response(response=502, description="Bad gateway"),
 *  @OA\Response(response=503, description="Service unavailable"),
 *  @OA\Response(response=504, description="Gateway timeout"),
 *  security={{ "apiAuth": {} }}
 * )
 */
public function clienDetails() {
    $client = auth()->user();
    if(!is_null($client)) {
      return $this->successResponse($client,Config::get('constants.CLIENT_DETAILS'),Response::HTTP_OK);
    }
    else {
      return $this->errorResponse(Config::get('constants.CLIENT_UNAUTHORIZED'),Response::HTTP_UNAUTHORIZED);
    }
  }
  
/////////////////////////Agent Registration//////////////////////////////////////////////////////////
    public function Register(Request $request) {  
      $data = $request->only('phone'); 
                                                     
       $userValidation = $this->userValidator->validate($data);
     
       if ($userValidation->fails()) {
         $errors = $userValidation->errors();
         return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
       }
       try {
         $response = $this->userService->Register($request);
         if($response!='Registered Agent')
         {
            return $this->successResponse($response,Config::get('constants.OTP_GEN'),Response::HTTP_OK);
         }else{
            return $this->errorResponse($response,Response::HTTP_OK);
         }
       }
       catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
       }      
    } 


    public function verifyOtp(Request $request) 
    {
     $data = $request->all();
     $verify = $this->userService->verifyOtp($request);
     if($verify == ''){
       return $this->errorResponse(Config::get('constants.OTP_NULL'),Response::HTTP_OK);
     }elseif($verify == 'Inval OTP'){
      return $this->errorResponse(Config::get('constants.OTP_INVALID'),Response::HTTP_OK);
     }
     else{
     return $this->successResponse($verify,Config::get('constants.VERIFIED'),Response::HTTP_OK);
     }
    }
   public function login(Request $request) {  
    
    $data = $request->only([
                              'email','password'
                            ]); 
    $loginValidation = $this->agentLoginValidator->validate($data);
  
    if ($loginValidation->fails()) {
      $errors = $loginValidation->errors();
      return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
    }
    try {

      $response = $this->userService->login($request);
      switch($response){
          case('un_registered_agent'):   //User is not registered
              return $this->errorResponse(Config::get('constants.UNREGISTERED'),Response::HTTP_OK);
          break;
          case('pwd_mismatch'):     //Password Mismatch
              return $this->errorResponse(Config::get('constants.PWD_MISMATCH'),Response::HTTP_OK);   
          break;
      }
      return $this->successResponse($response,Config::get('constants.LOGIN_SUCCESSFUL'),Response::HTTP_OK);     
      }
      catch (Exception $e) {
       return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
      } 
    } 

    public function getRoles() {
      $roles = $this->userService->getRoles();
      return $this->successResponse($roles,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }

    public function agentRegister(Request $request) {

      $data = $request->all();
      $agentDetailsValidation = $this->agentDetailsValidator->validate($data);
     
      if ($agentDetailsValidation->fails()) {
        $errors = $agentDetailsValidation->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
      }
      try {
        $agent = $this->userService->agentRegister($request);
      return $this->successResponse($agent,Config::get('constants.REGT_SUCCESS'),Response::HTTP_OK);
      }
      catch (Exception $e) {
        return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
      }        
    }


    public function getallAgent()
    {
        $wallet = $this->userService->getallAgent();
        return $this->successResponse($wallet,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK); 
    }
}
