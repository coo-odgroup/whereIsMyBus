<?php
namespace App\AppValidator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientBookingValidator 
{   

    public function validate($data) { 
        
        $rules = [
            'customerInfo.phone' => 'required||digits:10',
            'customerInfo.name' => 'required|max:50',
            'bookingInfo.bus_id' => 'required|numeric',
            'bookingInfo.source_id' => 'required|numeric',
            'bookingInfo.destination_id' => 'required|numeric',
            'bookingInfo.journey_dt' => 'required|date_format:Y-m-d',
            'bookingInfo.boarding_point' => 'required',
            'bookingInfo.dropping_point' => 'required',
            'bookingInfo.boarding_time' => 'required|date_format:H:i',
            'bookingInfo.dropping_time' => 'required|date_format:H:i',
            'bookingInfo.origin' => 'required',
            'bookingInfo.bookingDetail' => [
                                            'bus_seats_id' => 'required',
                                            'passenger_name' => 'required',
                                            'passenger_gender' => 'required',
                                            'passenger_age' => 'required',
                                            'created_by' => 'required',
                                           ]
        ];      
      
        $bookTicketValidation = Validator::make($data, $rules);
        return $bookTicketValidation;
    }

}