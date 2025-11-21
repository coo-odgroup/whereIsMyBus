<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\OfferService;
use App\AppValidator\CouponValidator;

class OfferController extends Controller
{

    use ApiResponser;
    
    protected $offerService;
    protected $couponValidator;
   
    public function __construct(OfferService $offerService,CouponValidator $couponValidator)
    {
        $this->offerService = $offerService;  
        $this->couponValidator = $couponValidator;  
    }
    /**
     * @OA\Post(
     *     path="/api/Offers",
     *     tags={"Offers API"},
     *     description="get all Offers",
     *     summary="Get all Offers",
     *     @OA\Parameter(
     *          name="user_id",
     *          description="user Id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="1"
     *          )
     *      ),
     *  @OA\Response(response="200", description="Get all Offers"),
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
    public function offers(Request $request) {
        $allOffers = $this->offerService->offers($request);
        return $this->successResponse($allOffers,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
    /**
         * @OA\Post(
         *     path="/api/Coupons",
         *     tags={"Coupons API"},
         *     description="get all Valid Coupons",
         *     summary="Get all Valid Coupons with respect to bus,route and bus operator",
         *     @OA\Parameter(
         *          name="coupon_code",
         *          description="coupon code",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="TEST123"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="bus_id",
         *          description="bus Id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example="1"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="source_id",
         *          description="source id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example="82"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="destination_id",
         *          description="destination id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example="377"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="journey_date",
         *          description="journey id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="string",
         *              example="2022-02-16"
         *          )
         *      ),
         *     @OA\Parameter(
         *          name="transaction_id",
         *          description="transaction id",
         *          required=true,
         *          in="query",
         *          @OA\Schema(
         *              type="integer",
         *              example="20211129150129121955"
         *          )
         *      ),
         *  @OA\Response(response="200", description="get all Valid Coupons"),
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
    public function coupons(Request $request) {

        $data = $request->all();
           $couponValidation = $this->couponValidator->validate($data);
         
           if ($couponValidation->fails()) {
             $errors = $couponValidation->errors();
             return $this->errorResponse($errors->toJson(),Response::HTTP_PARTIAL_CONTENT);
           }
            try {
            $response = $this->offerService->coupons($request);
            switch($response){
                case('min_tran_amount'):   //Transaction amount is Less then Minimum Transation
                    return $this->errorResponse(Config::get('constants.COUPON_NOT_APPLICABLE'),Response::HTTP_OK);
                break;
                case('inval_coupon'):     //Invalid or Unknown Coupon code
                    return $this->errorResponse(Config::get('constants.INVALID_COUPON'),Response::HTTP_OK);   
                break;
                case('coupon_expired'):   //Validity of Coupon Has Expired
                    return $this->errorResponse(Config::get('constants.COUPON_EXPIRED'),Response::HTTP_OK);
                break;
                case('already_applied'):   //Validity of Coupon Has already applied once
                    return $this->errorResponse(Config::get('constants.COUPON_ALREADY_APPLIED_ONCE'),Response::HTTP_OK);
                break;    
                
            }
            return $this->successResponse($response,Config::get('constants.COUPON_APPLIED'),Response::HTTP_OK);    
            }
            catch (Exception $e) {
             return $this->errorResponse($e->getMessage(),Response::HTTP_PARTIAL_CONTENT);
            }         
    }
    /**
     * @OA\Get(
     *     path="/api/AllPathUrls",
     *     tags={"All PathUrls API"},
     *     description="get all urls related to images",
     *     summary="get all urls related to images",
     *  @OA\Response(response="200", description="Get all Path Urls"),
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
    public function getPathUrls(Request $request) {
        $allUrls = $this->offerService->getPathUrls($request);
        return $this->successResponse($allUrls,Config::get('constants.RECORD_FETCHED'),Response::HTTP_OK);
    }
    
}