<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Slider;
use App\Models\Coupon;
use App\Models\Booking;
use App\Models\Users;
use App\Models\CouponRoute;
use App\Models\CouponAssignedBus;
use App\Models\CouponOperator;
use App\Models\FilePathUrls;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use JWTAuth;

class OfferRepository
{
    protected $slider;
    public function __construct(Slider $slider)
    {
        $this->slider = $slider;  
    }   
    
    public function offers($request)
    { 
        $busOffer = Config::get('constants.Bus_Offers');
        $festiveOffer = Config::get('constants.Festive_Offers');
        $user_id = $request['user_id'];
      
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        $allOffers = $this->slider->where('user_id', $user_id)
                                  ->where('start_date','<=',$currentDate)
                                  ->where('end_date','>=',$currentDate)
                                  ->where('status',1) 
                                  ->where('slider_photo','!=','')
                                  ->with(['coupon' => function ($a){
                                    $a->where('status',1);
                                    }])
                                  ->get(['id','coupon_id','user_id','occassion','category','url','slider_photo','alt_tag','start_date','start_time','end_date','end_time','slider_description','android_image']);

       
        return $allOffers;    
    }
    public function coupons($request)
    {   

         $user = JWTAuth::parseToken()->authenticate();
          
        $via=[0];

        if($user->client_id=='odbusSas'){ // for website
            $via=[0,1];
        }

        if($user->client_id=='odbusSasAndroid'){ // for App
            $via=[0,2];
        }

        $requestedCouponCode = $request['coupon_code'];
        $busId = $request['bus_id'];
        $sourceId = $request['source_id'];
        $destId = $request['destination_id'];
        $busOperatorId = $request['bus_operator_id'];
        $jDate = $request['journey_date'];
        //$totalFare = $request['total_fare'];
        $transactionId = $request['transaction_id'];
        $selCouponRecords = Coupon::where('status','1')->get();
        $bookingDetails = Booking::where('transaction_id',$transactionId)->get();
        $totalFare = 0;
                           
       // Log::info($bookingDetails[0]->owner_fare.'---'.$bookingDetails[0]->odbus_charges.'---'.$bookingDetails[0]->additional_owner_fare.'---'.$bookingDetails[0]->additional_special_fare.'---'.$bookingDetails[0]->additional_festival_fare);                                      
                                                

        if(isset($bookingDetails[0])){
            $totalFare = ($bookingDetails[0]->owner_fare) + ($bookingDetails[0]->odbus_charges) + ($bookingDetails[0]->additional_owner_fare) + ($bookingDetails[0]->additional_special_fare) + ($bookingDetails[0]->additional_festival_fare); // added additional fare on 23-sep-2024::lima Mohanty (due to calculation issue)
        }

        //Log::info($totalFare);
    
        $routeCoupon = Coupon::where('source_id', $sourceId)////Route wise coupon
                                ->where('destination_id', $destId)
                                ->where('coupon_type_id', 2)
                                ->whereIn('via',$via)
                                ->where('status','1')
                                ->where('from_date', '<=', $jDate)
                                ->where('to_date', '>=', $jDate)
                                ->where('bus_id', $busId)
                                ->get();
        if(isset($routeCoupon[0])){                           
                $routeCouponCode = $routeCoupon[0]->coupon_code;
        }else{
            $routeCouponCode =[];
        } 
        
        $operatorCoupon = Coupon::where('bus_operator_id', $busOperatorId) ////Operator wise coupon
                                ->where('coupon_type_id', 1)
                                ->whereIn('via',$via)
                                ->where('status','1')
                                ->where('from_date', '<=', $jDate)
                                ->where('to_date', '>=', $jDate)
                                ->where('bus_id', $busId)
                                ->get();
        if(isset($operatorCoupon[0])){                           
            $opCouponCode = $operatorCoupon[0]->coupon_code;
        }else{
            $opCouponCode =[];
        } 
        $opRouteCoupon = Coupon::where('bus_operator_id', $busOperatorId) ////OperatorRoute wise coupon
                                    ->where('coupon_type_id', 3)
                                    ->whereIn('via',$via)
                                    ->where('source_id', $sourceId)
                                    ->where('destination_id', $destId)
                                    ->where('status','1')
                                    ->where('from_date', '<=', $jDate)
                                    ->where('to_date', '>=', $jDate)
                                    ->where('bus_id', $busId)
                                    ->get();
        if(isset($opRouteCoupon[0])){                           
            $opRouteCouponCode = $opRouteCoupon[0]->coupon_code;
        }else{
            $opRouteCouponCode =[];
        } 
        
        // $busCoupon = Coupon::where('bus_id', $busId) ////Bus wise coupon
        //                         ->where('status','1')
        //                         ->get();
        // if(isset($busCoupon[0])){                           
        //     $busCouponCode = $busCoupon[0]->coupon_code;
        // }else{
        //     $busCouponCode =[];
        // } 
        
        $CouponRecords = collect([$opRouteCouponCode,$opCouponCode,$routeCouponCode]); 
        //$CouponRecords = collect($busCouponCode);       
        $CouponRecords = $CouponRecords->flatten()->unique()->values()->all();

        ///Coupon applicable on specific date range
        $appliedCoupon = collect([]);
        $date = Carbon::now();
        $bookingDate = $date->toDateString();

        // log::info($bookingDate);
        // log::info($jDate);

        foreach($CouponRecords as $key => $coupon){
        
            $type = $selCouponRecords->where('coupon_code',$coupon)->first()->valid_by;
            switch($type){
                case(1):    //Coupon available on journey date
                    $dateInRange = $selCouponRecords->where('coupon_code',$coupon)
                                ->where('from_date', '<=', $jDate)
                                ->where('to_date', '>=', $jDate)
                                ->whereIn('via',$via)
                                ->where('status', 1)
                                ->all();           
                    break;
                case(2):    //Coupon available on booking date
                    $dateInRange = $selCouponRecords->where('coupon_code',$coupon)
                    ->where('from_date', '<=', $bookingDate)
                    ->where('to_date', '>=', $bookingDate)
                    ->whereIn('via',$via)
                    ->where('status', 1)
                    ->all();
                    break;      
            }

            $applied_once=0;
            if($dateInRange){
                $matchedCoupon = collect($dateInRange)->first();
                $applied_once = $matchedCoupon->apply_once;
                $appliedCoupon->push($coupon);                
            }
        } 
        $couponExists = $appliedCoupon->contains($requestedCouponCode);
        if($couponExists)
        {
            $userId = Booking::where('transaction_id',$transactionId)->first()->users_id;

             $ifMobileCouponApplied = Booking::where('coupon_code',$requestedCouponCode)->whereIn('status',[1,2])->where('users_id',$userId)->count('id');

             if($ifMobileCouponApplied>0 && $applied_once==1){
                return "already_applied";
             } 

            $couponCount = Booking::where('coupon_code',$requestedCouponCode)->whereIn('status',[1,2])->count('id');
           

        }else{
            return "inval_coupon";
        } 

        // $couponDetails = $selCouponRecords[0]->where('coupon_code',$appliedCoupon)
        //                                           ->where('bus_id',$busId)
        //                                           ->get(); 
        switch($type){
        case(1):    //Coupon available on journey date
            $couponDetails = $selCouponRecords[0]->where('coupon_code',$appliedCoupon)
                                                    ->where('bus_id',$busId)
                                                    ->whereIn('via',$via)
                                                    ->where('from_date', '<=', $jDate)
                                                    ->where('to_date', '>=', $jDate)
                                                     ->where('status', 1)
                                                    ->get(); 

            break;
        case(2):    //Coupon available on booking date

            $couponDetails = $selCouponRecords[0]->where('coupon_code',$appliedCoupon)
            ->where('bus_id',$busId)
            ->whereIn('via',$via)
            ->where('from_date', '<=', $bookingDate)
            ->where('to_date', '>=', $bookingDate)
            ->where('status', 1)
            ->get(); 
            break;      
    }  
    
        $maxRedeemCount = $couponDetails[0]->max_redeem;

              
        if($couponCount < $maxRedeemCount){     
            if(isset($couponDetails)){ 
                $couponType = $couponDetails[0]->type;  ///type:1 for percentage and 2 for amount
                $maxDiscount = $couponDetails[0]->max_discount_price;
               

                if($couponType == '1'){
                    $percentage = $couponDetails[0]->percentage;
                   
                    $discount = ($totalFare*($percentage))/100;

                   
                    
                    if($discount <=  $maxDiscount ){
                        $totalAmount = $totalFare - $discount; 
                        $payableAmount = round($totalAmount + $bookingDetails[0]->transactionFee  + $bookingDetails[0]->customer_gst_amount,2); 
                        $couponRecords = array(
                            "totalAmount" => $totalFare, 
                            "discount" => $discount,
                            "payableAmount" => $payableAmount 
                        );

                      //Log::info($totalAmount.'-----'.$payableAmount);


                        Booking::where('users_id', $userId)->where('transaction_id', $transactionId)
                                                            ->update([
                                                                'coupon_code' => $requestedCouponCode,
                                                                'coupon_discount' => $discount,
                                                                'payable_amount' => $payableAmount
                                                            ]);
                   
                        return $couponRecords;
                    }else{
                        $discount = $maxDiscount;
                        $totalAmount = $totalFare - $maxDiscount;
                        $payableAmount = round($totalAmount + $bookingDetails[0]->transactionFee +  $bookingDetails[0]->customer_gst_amount,2);
                      
                        $couponRecords = array(
                            "totalAmount" => $totalFare, 
                            "discount" => $discount,
                            "payableAmount" => $payableAmount 
                        );
                        Booking::where('users_id', $userId)->where('transaction_id', $transactionId)
                                                            ->update([
                                                                'coupon_code' => $requestedCouponCode,
                                                                'coupon_discount' => $discount,
                                                                'payable_amount' => $payableAmount
                                                            ]);

                            // Log::info('2222');                                   
                            // Log::info($couponRecords);                                      
                        return $couponRecords;
                    }
                }elseif($couponType == '2'){  
                    $minTransactionAmount = $couponDetails[0]->min_tran_amount;
                    if($totalFare >= $minTransactionAmount ){
                        $discount = $couponDetails[0]->amount;
                        $totalAmount = $totalFare - $discount; 
                        $payableAmount = round($totalAmount + $bookingDetails[0]->transactionFee + $bookingDetails[0]->customer_gst_amount ,2); 
                        
                        $couponRecords = array(
                            "totalAmount" => $totalFare, 
                            "discount" => $discount,
                            "payableAmount" => $payableAmount 
                        );
                        Booking::where('users_id', $userId)->where('transaction_id', $transactionId)
                                                            ->update([
                                                                'coupon_code' => $requestedCouponCode,
                                                                'coupon_discount' => $discount,
                                                                'payable_amount' => $payableAmount
                                                            ]);
                        //  Log::info('3333');                                   
                        //  Log::info($couponRecords);                                   
                        return $couponRecords;
                    }else{
                        return "min_tran_amount";
                    }
                }
            }else{
                return "inval_coupon";
            }
        }else{
            return "coupon_expired";
        }
    }
   public function getPathUrls(Request $request)
    {    
        return FilePathUrls::get();
    }   

}