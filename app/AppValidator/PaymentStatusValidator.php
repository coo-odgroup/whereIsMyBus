<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentStatusValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'transaction_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            //'razorpay_signature' => 'required',
            // 'name' => 'required',
            // 'phone' => 'required',
            // 'routedetails' => 'required',    
            // 'bookingdate' => 'required',    
            // 'journeydate' => 'required',    
            // 'boarding_point' => 'required',    
            // 'departureTime' => 'required', 
            // 'dropping_point' => 'required',
            // 'arrivalTime' => 'required',
            // 'seat_id' => 'required|array|min:1',
            // 'seat_no' => 'required|array|min:1',
            // 'bus_id' => 'required',    
            // 'source' => 'required',    
            // 'destination' => 'required',    
            // 'busname' => 'required',    
            // 'busNumber' => 'required',
            // 'bustype' => 'required',    
            // 'busTypeName' => 'required',    
            // 'sittingType' => 'required',
            // 'totalfare'=> 'required',
            // 'odbus_charges'=> 'required',
            // 'odbus_gst'=> 'required',
            // 'owner_fare'=> 'required',
            // 'source'=> 'required',
            // 'destination'=> 'required',    
            // 'conductor_number' => 'required',    
            // 'passengerDetails' => 'required'
        ];      
      
        $payemntStatusValidator = Validator::make($data, $rules);
        return $payemntStatusValidator;
    }
}





