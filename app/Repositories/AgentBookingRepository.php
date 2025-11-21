<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BusSeats;
use App\Models\TicketPrice;
use App\Models\BusLocationSequence;
use App\Models\BookingSequence;
use App\Repositories\ChannelRepository;
use App\Models\OdbusCharges;
use App\Models\BusOperator;
use App\Models\AgentWallet;
use App\Models\AgentFee;
use App\Models\Users;
use App\Services\ViewSeatsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use App\Transformers\DolphinTransformer;
use App\Transformers\MantisTransformer;


class AgentBookingRepository
{
    protected $bus;
    protected $ticketPrice;
    protected $location;
    protected $user;
    protected $booking;
    protected $busSeats;
    protected $busLocationSequence;
    protected $viewSeatsService; 
    protected $channelRepository; 
    protected $dolphinTransformer;
    protected $mantisTransformer;

    public function __construct(Bus $bus,TicketPrice $ticketPrice,Location $location,User $user,BusSeats $busSeats,Booking $booking,BusLocationSequence $busLocationSequence,ChannelRepository $channelRepository,ViewSeatsService $viewSeatsService,DolphinTransformer $dolphinTransformer,MantisTransformer $mantisTransformer)
    {
        $this->bus = $bus;
        $this->ticketPrice = $ticketPrice;
        $this->location = $location;
        $this->user = $user;
        $this->busSeats = $busSeats;
        $this->booking = $booking;
        $this->channelRepository = $channelRepository;
        $this->busLocationSequence = $busLocationSequence;
        $this->viewSeatsService = $viewSeatsService;  
        $this->dolphinTransformer = $dolphinTransformer;
        $this->mantisTransformer = $mantisTransformer;

    }   
    
    public function agentBooking($request,$clientRole,$clientId,$priceDetails)
    { 

        $needGstBill = Config::get('constants.NEED_GST_BILL');
        $agentInfo = $request['agentInfo'];
        $customerInfo = $request['customerInfo'];
        $bookingInfo = $request['bookingInfo'];
        $defUserId = Config::get('constants.USER_ID');  

        $existingUser = Users::where('phone',$customerInfo['phone'])
                                    ->exists(); 
        if($existingUser==true){
            $userId = Users::where('phone',$customerInfo['phone'])
                                  ->first()->id;
            Users::where('id', $userId)->update($customerInfo);     
        }
        else{
            $userId = Users::create($request['customerInfo'])->latest()->first()->id;   
        }
        $validatedAgent = $this->user->where('phone',$agentInfo['phone'])
                                     ->where('status','1')
                                     ->exists();  
        if($validatedAgent==true){
        $agentId = $this->user->where('phone',$agentInfo['phone'])
                                  ->where('status','1')
                                  ->first('id');
        $aId = $this->user->where('phone',$agentInfo['phone'])
                                  ->where('status','1')
                                  ->first()->id;
         $walletDetail = AgentWallet::where('user_id',$aId)->orderBy('id','DESC')->where("status",1)->limit(1)->get();
         $walletBalance = 0;

        if(isset($walletDetail[0])){
            $walletBalance = $walletDetail[0]->balance;
        }else{
            $arr['note']="You do not have any wallet balance. Kindly recharge your wallet to book tickets";
            $arr['message']="less_balance";
            return $arr;
        } 
    if($walletBalance >= $bookingInfo['total_fare']){
        //Save Booking 
               $booking = new $this->booking;
        do {
           $transactionId = date('YmdHis') . gettimeofday()['usec'];
           } while ( $booking ->where('transaction_id', $transactionId )->exists());
        $booking->transaction_id =  $transactionId;
        do {
            $PNR = 'ODAG'.rand(100000,999999);
        } while ( $booking ->where('pnr', $PNR )->exists()); 
        $booking->pnr = $PNR;
        $booking->bus_id = $bookingInfo['bus_id'];
        $busId = $bookingInfo['bus_id'];
        $booking->source_id = $bookingInfo['source_id'];
        $booking->destination_id =  $bookingInfo['destination_id'];

        $j_day=1;

        if($bookingInfo['origin']== 'ODBUS'){

            $ticketPriceDetails = $this->ticketPrice->where('bus_id',$busId)->where('source_id',$bookingInfo['source_id'])
            ->where('destination_id',$bookingInfo['destination_id'])
            ->where('status','1')
            ->get();

           $j_day= $ticketPriceDetails[0]->j_day  ; 

        }

        $booking->j_day = $j_day;
        $booking->journey_dt = $bookingInfo['journey_date'];
        $booking->boarding_point = $bookingInfo['boarding_point'];
        $booking->dropping_point = $bookingInfo['dropping_point'];
        $booking->boarding_time = $bookingInfo['boarding_time'];
        $booking->dropping_time =  $bookingInfo['dropping_time'];
        $booking->origin = $bookingInfo['origin'];
        $booking->app_type = $bookingInfo['app_type'];
        $booking->typ_id = $bookingInfo['typ_id'];

        $booking->owner_fare = $priceDetails[0]['ownerFare'];
        $booking->total_fare = $priceDetails[0]['totalFare'];
        $booking->odbus_Charges = $priceDetails[0]['odbusServiceCharges'];
        $booking->additional_special_fare = $priceDetails[0]['specialFare'];
        $booking->additional_owner_fare = $priceDetails[0]['addOwnerFare'];
        $booking->additional_festival_fare = $priceDetails[0]['festiveFare'];
        $booking->transactionFee = $priceDetails[0]['transactionFee'];
        $booking->customer_gst_amount = isset($priceDetails[0]['customerGst']) ? $priceDetails[0]['customerGst'] : 0;
        $booking->customer_gst_percent = isset($priceDetails[0]['customerGst']) ? 5 : 0;
        $booking->customer_gst_status = isset($priceDetails[0]['customerGst']) ? 1 : 0;

        // $booking->owner_fare = $bookingInfo['owner_fare'];
        // $booking->total_fare = $bookingInfo['total_fare'];
        // $booking->odbus_Charges = $bookingInfo['odbus_service_Charges'];
        // $booking->additional_special_fare =$bookingInfo['specialFare'];
        // $booking->additional_owner_fare = $bookingInfo['addOwnerFare'];
        // $booking->additional_festival_fare = $bookingInfo['festiveFare'];
        // $booking->transactionFee = $bookingInfo['transactionFee'];

        if(isset($bookingInfo['adj_note'])){
            $booking->booking_adj_note = $bookingInfo['adj_note']; 
            $booking->adjust_pnr = $bookingInfo['adjust_pnr'];            

        }

        if(isset($bookingInfo['status'])){
            $booking->status = $bookingInfo['status'];
        } 

        if(isset($bookingInfo['booking_type'])){
            $booking->booking_type = $bookingInfo['booking_type'];
        } 
        $odbusGstPercent=0;
        $odbusGstAmount=0;

    if($bookingInfo['origin'] == 'ODBUS'){ // dolphin related changes
        
        $user_id = Bus::where('id',$bookingInfo['bus_id'])->first()->user_id;
        $busOperatorId = Bus::where('id',$bookingInfo['bus_id'])->first()->bus_operator_id;

        $odbusChargesRecord = OdbusCharges::where('user_id',$user_id)->get();
        if(isset($odbusChargesRecord[0])){
            $odbusGstPercent = OdbusCharges::where('user_id',$user_id)->first()->odbus_gst_charges;
        }else{
            $odbusGstPercent = OdbusCharges::where('user_id',$defUserId)->first()->odbus_gst_charges;
        }
        $odbusGstAmount = $bookingInfo['owner_fare'] * $odbusGstPercent/100;
        
        $busOperator = BusOperator::where("id",$busOperatorId)->get();   
        
            if($busOperator[0]->need_gst_bill == $needGstBill){   
                $ownerGstPercentage = $busOperator[0]->gst_amount;
                $booking->owner_gst_charges = $ownerGstPercentage;
                $ownerGstAmount = $bookingInfo['owner_fare'] * $ownerGstPercentage/100;
                $booking->owner_gst_amount = $ownerGstAmount;
            }  
    }
            
            $booking->odbus_gst_charges = $odbusGstPercent;           
            $booking->odbus_gst_amount = $odbusGstAmount; 

            $booking->CompanyID = $bookingInfo['CompanyID'];
            $booking->ReferenceNumber = $bookingInfo['ReferenceNumber'];
            $booking->RouteTimeID = $bookingInfo['RouteTimeID'];
            $booking->PickupID = $bookingInfo['PickupID'];
            $booking->DropID = $bookingInfo['DropID'];

        $agentCommissionByCustomer = AgentFee::get(); 

        $comissionByCustomer = 0;
        if($agentCommissionByCustomer){
            foreach($agentCommissionByCustomer as $agentCom){
                $priceFrom = $agentCom->price_from;
                $priceTo = $agentCom->price_to;
                if($bookingInfo['total_fare'] >= $priceFrom && $bookingInfo['total_fare']<= $priceTo){
                    $comissionByCustomer = $agentCom->max_comission;//maximum comission from customer                    
                    break;
                }  
            }   
        }        
        $booking->created_by = $bookingInfo['created_by'];
        $booking->users_id = $userId;  
        if(isset($bookingInfo['booking_type'])=='Adjust'){           
        }else{
            $booking->customer_comission = $comissionByCustomer;
        }      
        $agentId->booking()->save($booking);

        $seq_no_start=0;
        $seq_no_end=0;

        if($bookingInfo['origin']=='ODBUS'){

            //fetch the sequence from bus_locaton_sequence
                $seq_no_start = $this->busLocationSequence->where('bus_id',$busId)->where('location_id',$bookingInfo['source_id'])->first()->sequence;
                $seq_no_end = $this->busLocationSequence->where('bus_id',$busId)->where('location_id',$bookingInfo['destination_id'])->first()->sequence;
        }
       
        $bookingSequence = new BookingSequence;
        $bookingSequence->sequence_start_no = $seq_no_start;
        $bookingSequence->sequence_end_no = $seq_no_end;
            
        $booking->bookingSequence()->save($bookingSequence);

        //Update Booking Details >>>>>>>>>>

    if($bookingInfo['origin'] == 'ODBUS'){ // dolphin related changes
  
        $ticketPriceId = $ticketPriceDetails[0]->id;
        $bookingDetail = $request['bookingInfo']['bookingDetail'];
        $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id');  ////////in request passing seats_id with key as bus_seats_id
        foreach ($seatIds as $seatId){
            $busSeatsId[] = $this->busSeats
                ->where('bus_id',$busId)
                ->where('ticket_price_id',$ticketPriceId)
                ->where('seats_id',$seatId)->first()->id;
        }  
    }
        $bookingDetailModels = [];  
        $i=0;
       foreach ($bookingInfo['bookingDetail'] as $bDetail) {

            if($bookingInfo['origin'] == 'ODBUS'){ // dolphin related changes

                unset($bDetail['bus_seats_id']);
                $collection= collect($bDetail);
                $merged = ($collection->merge(['bus_seats_id' => $busSeatsId[$i]]))->toArray();
                $bookingDetailModels[] = new BookingDetail($merged);

            }elseif($bookingInfo['origin'] == 'DOLPHIN'){ // dolphin related changes{
                // get real seat name from dolphin transformer
                $ReferenceNumber=$bookingInfo['ReferenceNumber'];
                $seat_name= $this->dolphinTransformer->GetseatLayoutName($ReferenceNumber,$bDetail['bus_seats_id'],$clientRole,$clientId);

                unset($bDetail['bus_seats_id']);
                $bDetail['seat_name']= $seat_name; 
                $collection= collect($bDetail);
                $bookingDetailModels[] = new BookingDetail($collection->toArray());
            }
            elseif($bookingInfo['origin'] == 'MANTIS'){ // MANTIS related changes

                $seat_name = $this->mantisTransformer->GetseatText($bookingInfo['source_id'],$bookingInfo['destination_id'],$bookingInfo['journey_date'],$bookingInfo['bus_id'],$bDetail['bus_seats_id'],$clientRole,$clientId);
                
                $seat_fare = $this->mantisTransformer->GetseatFare($bookingInfo['source_id'],$bookingInfo['destination_id'],$bookingInfo['journey_date'],$bookingInfo['bus_id'],$bDetail['bus_seats_id'],$clientRole,$clientId);
                
                unset($bDetail['bus_seats_id']);
                $bDetail['seat_name'] = $seat_name; 
                $bDetail['seat_fare'] = $seat_fare;
                $collection = collect($bDetail);
                $bookingDetailModels[] = new BookingDetail($collection->toArray());
            }
            $i++;
        }    
            $booking->bookingDetail()->saveMany($bookingDetailModels);      
            return $booking; 
        }
        else{
                $arr['note']="Your current wallet balance is ₹ ".$walletBalance." Kindly recharge your wallet to book tickets";
                $arr['message'] ="less_balance";
                return $arr;
            } 
     }else{
         return 'AGENT_INVALID';
     }
    }
}