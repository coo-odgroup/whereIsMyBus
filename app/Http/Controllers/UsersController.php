<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\AppValidator\UsersValidator;
use App\AppValidator\LoginValidator;
use App\AppValidator\ResendotpValidator;
use App\AppValidator\UserProfileValidator;
use Illuminate\Support\Facades\Auth;
use App\Models\Users;
use App\Services\UsersService;
use App\Services\NotificationService;
use App\AppValidator\NotificationValidator;
//use JWTAuth;
//use Tymon\JWTAuth\Exceptions\JWTException;

class UsersController extends Controller
{
    use ApiResponser;
    protected $usersService;
    protected $usersValidator;
    protected $loginValidator;
    protected $userProfileValidator;
    protected $notificationService;
    protected $notificationValidator;
    protected $resendotpValidator;

    public function __construct(UsersService $usersService,UsersValidator $usersValidator,loginValidator $loginValidator,UserProfileValidator $userProfileValidator,NotificationService $notificationService,NotificationValidator $notificationValidator,ResendotpValidator $resendotpValidator)
    {
        $this->usersService = $usersService; 
        $this->usersValidator = $usersValidator; 
        $this->loginValidator = $loginValidator;
        $this->userProfileValidator = $userProfileValidator; 
        $this->notificationService = $notificationService;
        $this->notificationValidator = $notificationValidator;
        $this->resendotpValidator = $resendotpValidator;
    }
/**
 * @OA\Post(
 *     path="/api/Register",
 *     tags={"Register API"},
 *     description="user detatils saved and otp generated",
 *     summary="send OTP to user for registration",
 *     @OA\Parameter(
 *          name="name",
 *          description="name of user",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="email",
 *          description="email of user",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="phone",
 *          description="mobile number of user",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="fcmId",
 *          description="fcm Id(for APP use to store device Id)",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="created_by",
 *          description="created by",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *     @OA\Response(response="200", description="otp generated"),
 *     @OA\Response(response="206", description="not a valid credential"),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=401, description="Unauthorized access"),
 *     @OA\Response(response=404, description="No record found"),
 *     @OA\Response(response=500, description="Internal server error"),
 *     @OA\Response(response=502, description="Bad gateway"),
 *     @OA\Response(response=503, description="Service unavailable"),
 *     @OA\Response(response=504, description="Gateway timeout"),
 *     security={
 *       {"apiAuth": {}}
 *     }
 * )
 * 
 */
    public function Register(Request $request) {
      $data = $request->only([
        'name','email','phone','password','created_by'
       ]);  
      
       $usersValidation = $this->usersValidator->validate($data);
     
       if ($usersValidation->fails()) {
         $errors = $usersValidation->errors();
         return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
       }
       try {
         $response = $this->usersService->Register($request);
         if($response!='Existing User')
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
   
    /**
 * @OA\Post(
 *     path="/api/VerifyOtp",
 *     tags={"Verify Otp"},
 *     description="otp verification",
 *     summary="otp verification",
 *     @OA\Parameter(
 *          name="userId",
 *          description="user Id",
 *          required=true,
 *          in="query",
 *          @OA\Schema( 
 *              type="integer",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="otp",
 *          description="otp sent to user",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *          )
 *      ),
 *     @OA\Response(response="200", description="Registered successfully"),
 *     @OA\Response(response="206", description="otp not provided"),
 *     @OA\Response(response="406", description="Invalid otp"),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=401, description="Unauthorized access"),
 *     @OA\Response(response=404, description="No record found"),
 *     @OA\Response(response=500, description="Internal server error"),
 *     @OA\Response(response=502, description="Bad gateway"),
 *     @OA\Response(response=503, description="Service unavailable"),
 *     @OA\Response(response=504, description="Gateway timeout"),
 *     security={
 *       {"apiAuth": {}}
 *     }
 * )
 * 
 */  

   public function verifyOtp(Request $request) 
   {
    $data = $request->all();
    $verify = $this->usersService->verifyOtp($request);
    if($verify == ''){
      return $this->errorResponse(Config::get('constants.OTP_NULL'),Response::HTTP_OK);
    }elseif($verify == 'Inval OTP'){
     return $this->errorResponse(Config::get('constants.OTP_INVALID'),Response::HTTP_OK);
    }
    elseif($verify == 'Invalid User ID'){
      return $this->errorResponse(Config::get('constants.USER_INVALID'),Response::HTTP_OK);
     }
    else{
    return $this->successResponse($verify,Config::get('constants.VERIFIED'),Response::HTTP_OK);
    }
   }

/**
 * @OA\Post(
 *     path="/api/Login",
 *     tags={"Login API"},
 *     description="user login using phone or email",
 *     summary="user login using phone or email",
 *     @OA\Parameter(
 *          name="phone",
 *          description="phone of user",
 *          required=false,  
 *          in="query",
 *          @OA\Schema(
 *              type="integer",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="fcmId",
 *          description="fcm Id(for APP use to store device Id)",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="email",
 *          description="email of user",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *          )
 *      ),
 *     @OA\Response(response="200", description="otp generated"),
 *     @OA\Response(response="206", description="not a valid credential"),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=401, description="Unauthorized access"),
 *     @OA\Response(response=404, description="No record found"),
 *     @OA\Response(response=500, description="Internal server error"),
 *     @OA\Response(response=502, description="Bad gateway"),
 *     @OA\Response(response=503, description="Service unavailable"),
 *     @OA\Response(response=504, description="Gateway timeout"),
 *     security={
 *       {"apiAuth": {}}
 *     }
 * )
 * 
 */
  public function login(Request $request){  

    $data = $request->all();  
    $LoginValidation = $this->loginValidator->validate($data);
    
    if ($LoginValidation->fails()) {
      return $this->errorResponse(Config::get('constants.UN_REGISTERED'),Response::HTTP_OK);
    
      // $errors = $LoginValidation->errors();
      // return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
    }
    try {
      $response = $this->usersService->login($request);
      if($response!='un_registered')
      {
         return $this->successResponse($response,Config::get('constants.OTP_GEN'),Response::HTTP_OK);
      }else{
        return $this->errorResponse(Config::get('constants.UN_REGISTERED'),Response::HTTP_OK);
      }
  }
   catch (Exception $e) {
    return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
  }        

}

protected function createNewToken($token){
  $loginUser = [  
       'access_token' => $token,
       'token_type' => 'bearer',
       'expires_in' => Auth()->factory()->getTTL() * 60,
       'user' => Auth()->user()   
 ]; 
 return $this->successResponse($loginUser,Config::get('constants.OTP_VERIFIED'),Response::HTTP_OK);
}
 /**
 * @OA\Get(
 *  path="/api/UserProfile",
 *  summary="Get user details",
 *  tags={"User Profile"},
 *     @OA\Parameter(
 *         description="User Id",
 *         in="query",
 *         name="userId",
 *         required=true,
 *          @OA\Schema(
 *              type="integer",
 *              example=1
 *          )
 *     ),
 *     @OA\Parameter(
 *         description="User Token",
 *         in="query",
 *         name="token",
 *         required=true,
 *          @OA\Schema(
 *              type="string",
 *              example="E7CDunOAhI"
 *          )
 *     ),
 *  @OA\Response(response=200, description="Authorized User details"),
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
  
public function userProfile(Request $request) {
 
  $userDetails = $this->usersService->userProfile($request);
  
   if($userDetails=='Invalid'){
        return $this->errorResponse(Config::get('constants.INVALID_TOKEN'),Response::HTTP_OK);
      }
      else{
        return $this->successResponse($userDetails,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
      } 
}
   /**
   * @OA\Post(
   *     path="/api/updateProfile",
   *     tags={"update User Profile details"},
   *     summary="Update User Profile details",
   *     @OA\Parameter(
   *         description="User Id",
   *         in="query",
   *         name="userId",
   *         required=true,
   *          @OA\Schema(
   *              type="integer",
   *              example=1
   *          )
   *     ),
   *     @OA\Parameter(
   *         description="User Token",
   *         in="query",
   *         name="token",
   *         required=true,
   *          @OA\Schema(
   *              type="string",
   *              example="E7CDunOAhJ"
   *          )
   *     ),
   *     @OA\Parameter(
   *          name="name",
   *          description="user name",
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="SAM"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="email",
   *          description="user email",
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="abcd@gmail.com"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="pincode",
   *          description="pincode",
   *          in="query",
   *          @OA\Schema(
   *              type="number",
   *              example=122345
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="street",
   *          description="street",
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="St marry road"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="district",
   *          description="district",
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="Bangalore"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="address",
   *          description="address",
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="Bangalore"
   *          )
   *      ),
   *     @OA\Parameter(
   *          name="profile_image",
   *          description="profile image",
   *          in="query",
   *          @OA\Schema(
   *              type="string",
   *              example="profile.png"
   *          )
   *      ),
   *  @OA\Response(response=200, description="Update User Profile"),
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
  public function updateProfile(Request $request) {
      
      $data = $request->all();
      $userProfileValidation = $this->userProfileValidator->validate($data);
     
      if ($userProfileValidation->fails()) {
        $errors = $userProfileValidation->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
      }
      try{
      $response = $this->usersService->updateProfile($request); 
      if($response=='Invalid'){
        return $this->errorResponse(Config::get('constants.INVALID_TOKEN'),Response::HTTP_OK);
      }
      else{
        return $this->successResponse($response,Config::get('constants.PROFILE_UPDATED'),Response::HTTP_CREATED);
      }
    }
      catch (Exception $e) {
        return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
      }  
}

public function updateProfileImage(Request $request) 
{     
      $data = $request->all();
    
      // $userProfileValidation = $this->userProfileValidator->validate($data);
    
      // if ($userProfileValidation->fails()) {
      //   $errors = $userProfileValidation->errors();
      //   return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
      // }
      try{
          $response = $this->usersService->updateProfileImage($request); 
          if($response=='Invalid'){
               return $this->errorResponse(Config::get('constants.INVALID_TOKEN'),Response::HTTP_OK);
          }
          else{
            return $this->successResponse($response,Config::get('constants.PROFILE_UPDATED'),Response::HTTP_CREATED);
          }
    }
    catch (Exception $e) 
    {
        return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
    }  
}
  

public function refreshToken() {

  $res = [  
    'access_token' => auth()->refresh(),
    'token_type' => 'bearer',
    'expires_in' => Auth()->factory()->getTTL() * 60,
    'user' => Auth()->user() 
]; 

   return $this->successResponse($res,Config::get('constants.REFRESH_TOKEN'),Response::HTTP_OK);
}
/**
         * @OA\Post(
         *     path="/api/BookingHistory",
         *     tags={"Booking History of a Customer(Web use with pagination)"},
         *     description="Get Booking History of a Customer",
         *     summary="Get Booking History of a Customer",
         *     @OA\Parameter(
         *          name="status",
         *          description="status",
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              enum={"Completed", "Upcoming", "Cancelled"}
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="paginate",
         *          description="paginate",
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example=5
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="userId",
         *          description="user Id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example=1
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="token",
         *          description="token",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="E7CDunOAhI"
         *          )
         *      ),
         *  @OA\Response(response="200", description="Get Booking History Of a Customer"),
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
  public function BookingHistory(Request $request){  

    $data = $request->all();    
      $response =  $this->usersService->BookingHistory($request); 
      if($response=='Invalid'){
        return $this->errorResponse(Config::get('constants.INVALID_TOKEN'),Response::HTTP_OK);
      }
      else{
        return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
      }
  }

/**
         * @OA\Post(
         *     path="/api/AppBookingHistory",
         *     tags={"Booking History of a Customer(App use without pagination)"},
         *     description="Get Booking History of a Customer",
         *     summary="Get Booking History of a Customer",
         *     @OA\Parameter(
         *          name="status",
         *          description="status",
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              enum={"Completed", "Upcoming", "Cancelled"}
         *          )
         *      ),         *    
         *     @OA\Parameter(
         *          name="userId",
         *          description="user Id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example=1
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="token",
         *          description="token",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="E7CDunOAhI"
         *          )
         *      ),
         *  @OA\Response(response="200", description="Get Booking History Of a Customer"),
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
  
  public function AppBookingHistory(Request $request){  

    $data = $request->all();    
      $response =  $this->usersService->AppBookingHistory($request); 
      if($response=='Invalid'){
        return $this->errorResponse(Config::get('constants.INVALID_TOKEN'),Response::HTTP_OK);
      }
      else{
        return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
      }
  }

  /**
 * @OA\Get(path="/api/UserReviews",
 *   tags={"User Reviews"},
 *   summary="Get user Reviews of an authenticated user",
 *   description="Get user review details",
 *     @OA\Parameter(
 *         description="User Id",
 *         in="query",
 *         name="userId",
 *         required=true,
 *          @OA\Schema(
 *              type="integer",
 *              example=1
 *          )
 *     ),
 *     @OA\Parameter(
 *         description="User Token",
 *         in="query",
 *         name="token",
 *         required=true,
 *          @OA\Schema(
 *              type="string",
 *              example="E7CDunOAhI"
 *          )
 *     ),
 *  @OA\Response(response=200, description="Get User Reviews"),
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
 */
  public function userReviews(Request $request)
  {    
    $data = $request->all();
    $userReviewsValidation = $this->userProfileValidator->validate($data);
   
    if ($userReviewsValidation->fails()) {
      $errors = $userReviewsValidation->errors();
      return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
    }
    try{
      $response =  $this->usersService->userReviews($request); 
      if($response=='Invalid'){
        return $this->errorResponse(Config::get('constants.INVALID_TOKEN'),Response::HTTP_OK);
      }
      else{
        return $this->successResponse($response,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
      }
    }
    catch (Exception $e) {
      return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
    } 
  }
/**
 * @OA\Post(
 *     path="/api/SendNotification",
 *     tags={"SendNotification API"},
 *     summary="SendNotification(App use)",
 *     @OA\RequestBody(
 *        required = true,
 *     description="Send Notification",
 *        @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                property="notification",
 *                type="object",
 *                @OA\Property(
 *                  property="title",
 *                  type="string",
 *                  example="OFFER"
 *                  ),
 *                @OA\Property(
 *                  property="body",
 *                  type="string",
 *                  example="SUMMER SPL DISCOUNT"
 *                  )
 *                ),
 *              ),
 *  ),
 *  @OA\Response(response="200", description="Notification sent successfully"),
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
  public function sendNotification(Request $request) {
     
      $data = $request->all();
      $notificationValidation = $this->notificationValidator->validate($data);
     
      if ($notificationValidation->fails()) {
        $errors = $notificationValidation->errors();
        return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
      }
      try{
        $response =  $this->notificationService->sendNotification($request); 
        if($response=='failed'){
          return $this->errorResponse(Config::get('constants.PUSH_NTFY_FAILED'),Response::HTTP_OK);
        }else{
          return $this->successResponse($response,Config::get('constants.NOTIFICATION_SENT'),Response::HTTP_OK);
        }

      }
      catch (Exception $e) {
        return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
      } 
    }
    /**
 * @OA\Post(
 *     path="/api/ResendOTP",
 *     tags={"ResendOTP API"},
 *     description="Resend otp during registration or Login",
 *     summary="Resend otp during registration or Login",
 *     @OA\Parameter(
 *          name="isMobile",
 *          description="flag to check App user or not",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              default="true",
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="source",
 *          description="email or mobile no",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="isLogin",
 *          description="flag to check during registration(false) or Login(true) need to resend otp",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string",
 *              example="true",
 *          )
 *      ),
 *     @OA\Response(response="200", description="otp generated"),
 *     @OA\Response(response="206", description="not a valid credential"),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=401, description="Unauthorized access"),
 *     @OA\Response(response=404, description="No record found"),
 *     @OA\Response(response=500, description="Internal server error"),
 *     @OA\Response(response=502, description="Bad gateway"),
 *     @OA\Response(response=503, description="Service unavailable"),
 *     @OA\Response(response=504, description="Gateway timeout"),
 *     security={
 *       {"apiAuth": {}}
 *     }
 * )
 * 
 */
   /////////ANDROID USE///////////////////
   public function resendOTP(Request $request) {
     
    $data = $request->all();
    $resendotpValidation = $this->resendotpValidator->validate($data);
   
    if ($resendotpValidation->fails()) {
      $errors = $resendotpValidation->errors();
      return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
    }
    try {
      $response = $this->usersService->resendOTP($request);
      if($response == 'un_registered')
      {
        return $this->errorResponse(Config::get('constants.UN_REGISTERED'),Response::HTTP_OK);
      }elseif($response == 'record_not_found'){
        return $this->errorResponse(Config::get('constants.NO_RECORD_FOUND'),Response::HTTP_OK);
      }
      else{
        return $this->successResponse($response,Config::get('constants.OTP_GEN'),Response::HTTP_OK);
      }
    }
    catch (Exception $e) {
      return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
    }        
  } 
  
  
  public function loginweb(Request $request){  

    $arrParam = json_decode(decryptRequest($request['REQUEST_DATA'])); 
    $request['phone']= $data['phone']=isset($arrParam->phone) ? $arrParam->phone :null;
    $request['email']=$data['email']=isset($arrParam->email) ? $arrParam->email:null;

    $LoginValidation = $this->loginValidator->validate($data);
    
    if ($LoginValidation->fails()) {
      return $this->errorResponse(Config::get('constants.UN_REGISTERED'),Response::HTTP_OK);
    
      // $errors = $LoginValidation->errors();
      // return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
    }
    try {
      $response = $this->usersService->login($request);
      if($response!='un_registered')
      {
         return $this->successResponse(encryptResponse($response),Config::get('constants.OTP_GEN'),Response::HTTP_OK);
      }else{
        return $this->errorResponse(Config::get('constants.UN_REGISTERED'),Response::HTTP_OK);
      }
  }
   catch (Exception $e) {
    return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
  }        

}


public function verifyOtpweb(Request $request) 
   {
    
    $arrParam = json_decode(decryptRequest($request['REQUEST_DATA'])); 
    $request['otp']=$arrParam->otp;
    $request['userId']=$arrParam->userId;

    $verify = $this->usersService->verifyOtp($request);
    if($verify == ''){
      return $this->errorResponse(Config::get('constants.OTP_NULL'),Response::HTTP_OK);
    }elseif($verify == 'Inval OTP'){
     return $this->errorResponse(Config::get('constants.OTP_INVALID'),Response::HTTP_OK);
    }
    elseif($verify == 'Invalid User ID'){
      return $this->errorResponse(Config::get('constants.USER_INVALID'),Response::HTTP_OK);
     }
    else{
    return $this->successResponse(encryptResponse($verify),Config::get('constants.VERIFIED'),Response::HTTP_OK);
    }
   }


   public function Registerweb(Request $request) {

    $arrParam = json_decode(decryptRequest($request['REQUEST_DATA'])); 
    $request['name']=$arrParam->name;
    $request['email']=$arrParam->email;
    $request['phone']=$arrParam->phone;
    $request['created_by']=$arrParam->created_by;
    

      $data = $request->only([
        'name','email','phone','created_by'
       ]);  
      
       $usersValidation = $this->usersValidator->validate($data);
     
       if ($usersValidation->fails()) {
         $errors = $usersValidation->errors();
         return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
       }
       try {
         $response = $this->usersService->Register($request);
         if($response!='Existing User')
         {
            return $this->successResponse(encryptResponse($response),Config::get('constants.OTP_GEN'),Response::HTTP_OK);
         }else{
            return $this->errorResponse(encryptResponse($response),Response::HTTP_OK);
         }
       }
       catch (Exception $e) {
         return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
       }        
   } 

}
