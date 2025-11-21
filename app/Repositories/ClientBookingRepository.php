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
use App\Models\BusCancelled;
use App\Models\BusLocationSequence;
use App\Models\BookingSequence;
use App\Repositories\ChannelRepository;
use App\Services\ListingService;
use App\Models\OdbusCharges;
use App\Models\BusOperator;
use App\Models\AgentWallet;
use App\Models\ClientWallet;
use App\Models\ClientFeeSlab;
use App\Models\AgentFee;
use App\Models\Users;
use App\Models\Seats;
use App\Models\ManageSms;
use App\Models\BusContacts;
use App\Services\ViewSeatsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use App\Transformers\DolphinTransformer;
use App\Transformers\MantisTransformer;
use Illuminate\Support\Facades\DB;


class ClientBookingRepository
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
    protected $listingService; 
    protected $users;


    public function __construct(Bus $bus,TicketPrice $ticketPrice,Location $location,User $user,BusSeats $busSeats,Booking $booking,BusLocationSequence $busLocationSequence,ChannelRepository $channelRepository,ViewSeatsService $viewSeatsService,ListingService $listingService,DolphinTransformer $dolphinTransformer,Users $users,MantisTransformer $mantisTransformer)
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
        $this->listingService = $listingService; 
        $this->dolphinTransformer = $dolphinTransformer;
        $this->users = $users;
        $this->mantisTransformer = $mantisTransformer;

    }   
    
    public function clientBooking($request,$clientRole,$clientId)
    {  
        $needGstBill = Config::get('constants.NEED_GST_BILL');
        $customerInfo = $request['customerInfo'];
        $bookingInfo = $request['bookingInfo'];
        $defUserId = Config::get('constants.USER_ID');

        $entry_date = $bookingInfo['journey_dt'];
        $busId = $bookingInfo['bus_id'];
        $sourceId = $bookingInfo['source_id'];
        $destinationId =  $bookingInfo['destination_id'];
        $ReferenceNumber = (isset($request['bookingInfo']['ReferenceNumber'])) ? $request['bookingInfo']['ReferenceNumber'] : '';
        
        ////////////////////////busId validation////////////////////////////////////
        $source = Location::where('id',$sourceId)->first()->name;
        $destination = Location::where('id',$destinationId)->first()->name;
        $clientDetail = User::where('id', $clientId)->first();

        

        $bookingDetail = $request['bookingInfo']['bookingDetail'];////////in request passing seats_id with key as bus_seats_id
	    $bookingInfo['origin'] = (isset($bookingInfo['origin'])) ? $bookingInfo['origin']: 'ODBUS';
        if($bookingInfo['origin']== 'ODBUS'){ // dolphin related changes
            $busOperatorId = Bus::where('id',$bookingInfo['bus_id'])->first()->bus_operator_id;         
            $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id');
            $seater = Seats::whereIn('id',$seatIds)->where('berthType',1)->pluck('id');
            $sleeper = Seats::whereIn('id',$seatIds)->where('berthType',2)->pluck('id');
       
            $reqInfo = array(
                "source" => $source,
                "destination" => $destination,
                "entry_date" => $entry_date,
                "bus_operator_id" => Null,
                "user_id" => Null,
                "origin" =>'ODBUS'
            ); 
            $busRecords = $this->listingService->getAll($reqInfo,$clientRole,$clientId);

           

            if($busRecords){
            $busRecords->pluck('busId');
            $validBus = $busRecords->pluck('busId')->contains($busId);
            }
                if(!$validBus){
                    return "Bus_not_running";
                }
                $data = array(
                    'busId' => $busId,
                    'sourceId' => $sourceId,
                    'destinationId' => $destinationId,
                    'seater' => $seater,
                    'sleeper' => $sleeper,
                    'entry_date' => $entry_date,
                    'origin' => $bookingInfo['origin']
                );
                $priceDetails = $this->viewSeatsService->getPriceCalculation($data,$clientId);

                $data = array(
                    'busId' => $busId,
                    'sourceId' => $sourceId,
                    'destinationId' => $destinationId,
                    'seater' => $seater->toArray(),
                    'sleeper' => $sleeper->toArray(),
                    'entry_date' => $entry_date,
                    'origin' => $bookingInfo['origin']
                );

                $PDetails = $this->viewSeatsService->getPriceOnSeatsSelection($data,$clientRole,$clientId); 

        }    
        if($bookingInfo['origin'] =='DOLPHIN'){
            $bookingDetail = $request['bookingInfo']['bookingDetail'];//in request passing seats_id with key as bus_seats_id
            $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id');
            $seatTypArr = $this->dolphinTransformer->GetSeatType($ReferenceNumber,$seatIds,$clientRole,$clientId);
            
            $data = array(
                'busId' => $busId,
                'sourceId' => $sourceId,
                'destinationId' => $destinationId,
                'seater' => $seatTypArr['seater'],
                'sleeper' => $seatTypArr['sleeper'],
                'entry_date' => $entry_date,
                'ReferenceNumber' => $ReferenceNumber,
                'origin' => $bookingInfo['origin'],
            );
            $priceDetails = $this->viewSeatsService->DolphinPriceCalculation($data,$clientRole,$clientId);

            $data = array(
                'busId' => $busId,
                'sourceId' => $sourceId,
                'destinationId' => $destinationId,
                'seater' => $seater->toArray(),
                'sleeper' => $sleeper->toArray(),
                'entry_date' => $entry_date,
                'origin' => $bookingInfo['origin']
            );

            $PDetails = $this->viewSeatsService->getPriceOnSeatsSelection($data,$clientRole,$clientId); 

        }
        if($bookingInfo['origin'] =='MANTIS'){
            $bookingDetail = $request['bookingInfo']['bookingDetail'];//in request passing seats_id with key as bus_seats_id
            $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id'); 
            $entry_date = $bookingInfo['journey_dt'];
            $busId = $bookingInfo['bus_id'];
            $sourceId = $bookingInfo['source_id'];
            $destinationId =  $bookingInfo['destination_id'];

            $mantisSeatresult = $this->mantisTransformer->MantisSeatLayout($sourceId,$destinationId,$entry_date,$busId,$clientRole,$clientId);
            
            $seater = [];
            $lbSleeper = [];
            $ubSleeper = [];
            $sleeper = [];
           
            if(isset($mantisSeatresult['lower_berth'])){
                $seater = collect($mantisSeatresult['lower_berth'])->whereIn('id', $seatIds)->where('berthType',1)->pluck('id');

                $lbSleeper = collect($mantisSeatresult['lower_berth'])->whereIn('id', $seatIds)->where('berthType',2)->pluck('id');
            }
            if(isset($mantisSeatresult['upper_berth'])){
                $ubSleeper = collect($mantisSeatresult['upper_berth'])->whereIn('id', $seatIds)->where('berthType',2)->pluck('id');
            }
            $sleeper = collect($lbSleeper)->merge(collect($ubSleeper));
            $data = array(
                'busId' => $busId,
                'sourceId' => $sourceId,
                'destinationId' => $destinationId,
                'seater' => $seater,
                'sleeper' => $sleeper,
                'entry_date' => $entry_date,
                'origin' => $bookingInfo['origin'],
            );
            $priceDetails = $this->viewSeatsService->MantisPriceCalculation($data,$clientRole,$clientId);

            $data = array(
                'busId' => $busId,
                'sourceId' => $sourceId,
                'destinationId' => $destinationId,
                'seater' => $seater->toArray(),
                'sleeper' => $sleeper->toArray(),
                'entry_date' => $entry_date,
                'origin' => $bookingInfo['origin']
            );

            $PDetails = $this->viewSeatsService->getPriceOnSeatsSelection($data,$clientRole,$clientId); 

        }
        $cId = $this->user->where('id',$bookingInfo['user_id'])
                                ->where('status','1')
                                ->first('id');
                              
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
        if(isset($bookingInfo['user_id'])){
         $walletDetail = ClientWallet::where('user_id',$bookingInfo['user_id'])->orderBy('id','DESC')->where("status",1)->limit(1)->get();
       
        $walletBalance = 0;

        if(isset($walletDetail[0])){
            $walletBalance = $walletDetail[0]->balance;
        }else{
            $arr['note']="You do not have any wallet balance. Kindly recharge your wallet to book tickets";
            $arr['message']="less_balance";
            return $arr;
        } 
        if($walletBalance >= (int)$priceDetails[0]['totalFare']){
            //Save Booking 
                $booking = new $this->booking;
            do {
            $transactionId = date('YmdHis') . gettimeofday()['usec'];
            } while ( $booking ->where('transaction_id', $transactionId )->exists());
            $booking->transaction_id =  $transactionId;
            do {
                $PNR = 'ODCL'.rand(10000,99999);
                } while ( $booking ->where('pnr', $PNR )->exists()); 
            $booking->pnr = $PNR;
            $booking->user_id = $bookingInfo['user_id'];
            $booking->bus_id = $bookingInfo['bus_id'];
            $booking->source_id = $bookingInfo['source_id'];
            $booking->destination_id =  $bookingInfo['destination_id'];

            $j_day = 1;

            if($bookingInfo['origin']== 'ODBUS'){

                $ticketPriceDetails = $this->ticketPrice->where('bus_id',$busId)->where('source_id',$bookingInfo['source_id'])->where('destination_id',$bookingInfo['destination_id'])->get();
                $j_day = $ticketPriceDetails[0]->j_day  ; 
            } 
            $booking->j_day = $j_day;
            $booking->journey_dt = $bookingInfo['journey_dt'];
            $booking->boarding_point = $bookingInfo['boarding_point'];
            $booking->dropping_point = $bookingInfo['dropping_point'];
            $booking->boarding_time = $bookingInfo['boarding_time'];
            $booking->dropping_time =  $bookingInfo['dropping_time'];
            $booking->origin = $bookingInfo['origin'];
            $booking->app_type = 'CLNTWEB';
            $booking->owner_fare = $priceDetails[0]['ownerFare'];
            $booking->total_fare = $priceDetails[0]['totalFare'];
            $booking->client_gst = $priceDetails[0]['gst'];
            $booking->odbus_Charges = $priceDetails[0]['odbusServiceCharges'];
            $booking->additional_special_fare = $priceDetails[0]['specialFare'];
            $booking->additional_owner_fare = $priceDetails[0]['addOwnerFare'];
            $booking->additional_festival_fare = $priceDetails[0]['festiveFare'];

            $booking->CompanyID = (isset($bookingInfo['CompanyID'])) ? $bookingInfo['CompanyID']: '';
            $booking->ReferenceNumber =(isset($bookingInfo['ReferenceNumber'])) ? $bookingInfo['ReferenceNumber']: '';
            $booking->RouteTimeID = (isset($bookingInfo['RouteTimeID'])) ? $bookingInfo['RouteTimeID'] : '';
            $booking->PickupID =(isset($bookingInfo['PickupID'])) ? $bookingInfo['PickupID'] : '';
            $booking->DropID = (isset($bookingInfo['DropID'])) ? $bookingInfo['DropID'] : '';
            
            $odbusGstPercent = 0;
            $odbusGstAmount = 0;

            if($bookingInfo['origin'] == 'ODBUS'){ // dolphin related changes
                $odbusGstPercent = OdbusCharges::where('user_id',$defUserId)->first()->odbus_gst_charges;
                $booking->odbus_gst_charges = $odbusGstPercent;
                $odbusGstAmount = $priceDetails[0]['ownerFare'] * $odbusGstPercent/100;
                $busOperator = BusOperator::where("id",$busOperatorId)->get();   
                if($busOperator[0]->need_gst_bill == $needGstBill){   
                    $ownerGstPercentage = $busOperator[0]->gst_amount;
                    $booking->owner_gst_charges = $ownerGstPercentage;
                    $ownerGstAmount = $priceDetails[0]['ownerFare'] * $ownerGstPercentage/100;
                    $booking->owner_gst_amount = $ownerGstAmount;
                } 
            }
            $booking->odbus_gst_charges = $odbusGstPercent;
            $booking->odbus_gst_amount = $odbusGstAmount;
                
            $clientCommissions = ClientFeeSlab::where('user_id', $clientId)
                                                    ->where('status', '1')
                                                    ->get(); 
           // Log::info($PDetails);
          
            $actual_fare_for_commission=  $PDetails[0]['totalFare'] - $PDetails[0]['gst']; // changes in 8 Feb,2023(as per santosh)
            //Log::info($actual_fare_for_commission);

            $actual_fare_for_commission= $actual_fare_for_commission;

            //Log::info($actual_fare_for_commission);
           //Log::info($actual_fare_for_commission);
           // Log::info($clientCommissions);

            $clientComission = 0;
            if($clientCommissions){
                foreach($clientCommissions as $clientCom){
                    $startFare = $clientCom->starting_fare;
                    $uptoFare = $clientCom->upto_fare;
                    if($actual_fare_for_commission >= $startFare && $actual_fare_for_commission <= $uptoFare){
                        $clientComission = $clientCom->commision;
                        //Log::info($clientComission);
                        break;
                    }  
                }   
            } 
            $clientComAmount = round( ($actual_fare_for_commission/100) * $clientComission ,2);
            $booking->client_comission = $clientComAmount;
            $booking->client_percentage = $clientComission;
                        
            $booking->created_by = $clientDetail->name;
            $booking->users_id = $userId;
            $cId->booking()->save($booking);

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

                if($bDetail['passenger_gender']=='Female' || $bDetail['passenger_gender']=='female' ){
                    $bDetail['passenger_gender']='F';
                }
                if($bDetail['passenger_gender']=='Male' || $bDetail['passenger_gender']=='male' ){
                    $bDetail['passenger_gender']='M';
                }
                if($bookingInfo['origin'] == 'ODBUS'){ // dolphin related changes

                    unset($bDetail['bus_seats_id']);
                    $collection= collect($bDetail);
                    $merged = ($collection->merge(['bus_seats_id' => $busSeatsId[$i], 'created_by' => $clientDetail->name]))->toArray();
                    $bookingDetailModels[] = new BookingDetail($merged);
                }

                elseif($bookingInfo['origin'] == 'DOLPHIN'){ // dolphin related changes{
                    // get real seat name from dolphin transformer
                    $ReferenceNumber=$bookingInfo['ReferenceNumber'];
                    $seat_name= $this->dolphinTransformer->GetseatLayoutName($ReferenceNumber,$bDetail['bus_seats_id'],$clientRole,$clientId);

                    unset($bDetail['bus_seats_id']);
                    $bDetail['seat_name']= $seat_name; 
                    $bDetail['created_by']= $clientDetail->name; 
                    $collection= collect($bDetail);
                    $bookingDetailModels[] = new BookingDetail($collection->toArray());
                }
                elseif($bookingInfo['origin'] == 'MANTIS'){ // MANTIS related changes

                    $seat_name = $this->mantisTransformer->GetseatText($bookingInfo['source_id'],$bookingInfo['destination_id'],$bookingInfo['journey_dt'],$bookingInfo['bus_id'],$bDetail['bus_seats_id'],$clientRole,$clientId);
                    
                    $seat_fare = $this->mantisTransformer->GetseatFare($bookingInfo['source_id'],$bookingInfo['destination_id'],$bookingInfo['journey_dt'],$bookingInfo['bus_id'],$bDetail['bus_seats_id'],$clientRole,$clientId);
                    unset($bDetail['bus_seats_id']);
                    $bDetail['seat_name'] = $seat_name; 
                    $bDetail['seat_fare'] = $seat_fare;
                    $collection = collect($bDetail);
                    $bookingDetailModels[] = new BookingDetail($collection->toArray());
                }
                $i++;
            }    
            $passengerDetails = $booking->bookingDetail()->saveMany($bookingDetailModels);      
            return collect($booking->toArray())
                                    ->only(['pnr', 'transaction_id'])
                                    ->all();
        }
        else{
            $arr['note']="Your current wallet balance is ₹ ".$walletBalance." Kindly recharge your wallet to book tickets";
            $arr['message']="less_balance";
            return $arr;
        } 
        }else{
            return 'CLIENT_INVALID';
        }
}

    public function ticketConfirmation($request)
    {
        $SmsGW = config('services.sms.otpservice');
        $defUserId = Config::get('constants.USER_ID');
        $seatHold = Config::get('constants.SEAT_HOLD_STATUS');
        $booked = Config::get('constants.BOOKED_STATUS');
        $transactionId = $request['transaction_id'];
        
        $bookingRecord = $this->booking->where('transaction_id', $transactionId)
                                        //->where('status', $seatHold)
                                       ->with('bookingDetail')
                                       ->with(['clientWallet' => function($cw){
                                        $cw->orderBy('id','DESC');
                                        $cw->where("status",1);
                                        $cw->limit(1);
                                        }])
                                       ->get(); 
        
        if($bookingRecord[0]->status==1){
            $bal['wallet_balance'] = $bookingRecord[0]->clientWallet[0]->balance;
            $bal['final_pnr'] = $bookingRecord[0]->pnr;

            return $bal;
        }                               

        // Log::info($transactionId);

        $origin = $bookingRecord[0]->origin;  
        $busId = $bookingRecord[0]->bus_id;  
        $sourceId = $bookingRecord[0]->source_id;
        $destinationId = $bookingRecord[0]->destination_id;
        $entry_date = $bookingRecord[0]->journey_dt;
                             
        $bookingId = $bookingRecord[0]->id; 
        $pnr = $bookingRecord[0]->pnr;
        $comissionAmount = $bookingRecord[0]->client_comission;             
        $amount = $bookingRecord[0]->total_fare;
        $discount = $bookingRecord[0]->coupon_discount;
        $payable_amount = $bookingRecord[0]->total_fare;
        $odbus_charges = $bookingRecord[0]->odbus_charges;
        $odbus_gst = $bookingRecord[0]->odbus_gst_charges;
        $owner_fare = $bookingRecord[0]->owner_fare;
                              
        $clientDetails = ClientWallet::where('user_id',$request['client_id'])
                                        ->orderBy('id','DESC')->where("status",1)->limit(1)
                                        ->get(); 

        $clientWallet = new ClientWallet();
        $clientWallet->transaction_id = $transactionId;
        $clientWallet->booking_id = $bookingId;
        $clientWallet->amount = $amount;
        $clientWallet->transaction_type = 'd';
        $clientWallet->balance = $clientDetails[0]->balance - $amount;
        $clientWallet->user_id = $request['client_id'];
        $clientWallet->created_by = $request['client_name'];
        $clientWallet->status = 1;
        $clientWallet->save();
        
        $clientDetails = ClientWallet::where('user_id',$request['client_id'])
                                        ->orderBy('id','DESC')->where("status",1)->limit(1)
                                        ->get(); 

        $tranId = date('YmdHis') . gettimeofday()['usec'];
        $clientWallet = new ClientWallet();
        $clientWallet->transaction_id = $tranId;
        $clientWallet->amount = $comissionAmount;
        $clientWallet->type = 'Commission';
        $clientWallet->booking_id = $bookingId;
        $clientWallet->transaction_type = 'c';
        $clientWallet->balance = $clientDetails[0]->balance + $comissionAmount;
        $clientWallet->user_id = $request['client_id'];
        $clientWallet->created_by = $request['client_name'];
        $clientWallet->status = 1;
        $clientWallet->save();

        /////////Update booking table as status booked////////////
  
        $this->booking->where('id', $bookingId)->update(['status' => $booked]);
        $booking = $this->booking->find($bookingId);
        $booking->bookingDetail()->where('booking_id', $bookingId)->update(array('status' => $booked));

        if($origin=='ODBUS'){
  
        $bookingDetails = $this->booking->where('transaction_id', $transactionId)                               
                                ->with('users')
                               ->with(["bus" => function($bs){
                                $bs->with(['cancellationslabs'=> function($c){
                                    $c->with('cancellationSlabInfo');
                                    }]);
                                $bs->with(['BusType' => function($bt){
                                   $bt->with('busClass');
                                 }]);
                                $bs->with('BusSitting');                
                                $bs->with('busContacts');               
                                }])
                                ->with(["bookingDetail" => function($bk){
                                    $bk->with('busSeats');    
                                }])
                              ->with(['clientWallet' => function($cw){
                                $cw->orderBy('id','DESC');
                                $cw->where("status",1);
                                $cw->limit(1);
                                }])
                              ->get();
                }

             if($origin=='DOLPHIN' || $origin=='MANTIS'){
                    $bookingDetails = $this->booking->where('transaction_id', $transactionId)
                   ->with('users')
                   ->with('bus')
                    ->with('bookingDetail')
                  ->with(['clientWallet' => function($cw){
                    $cw->orderBy('id','DESC');
                    $cw->where("status",1);
                    $cw->limit(1);
                    }])
                  ->get();
            }                      
        $srcName = Location::where('id',$bookingDetails[0]->source_id)->first()->name;
        $destName = Location::where('id',$bookingDetails[0]->destination_id)->first()->name;
        
        $bookingDetails[0]['src_name'] = $srcName;
        $bookingDetails[0]['dest_name'] = $destName;

        if($origin=='ODBUS'){
       
            $busSeatsIds = $bookingRecord[0]->bookingDetail->pluck('bus_seats_id');
            $busSeatsDetails = BusSeats::whereIn('id',$busSeatsIds)->with('seats')->get();
            $seat_no = $busSeatsDetails->pluck('seats.seatText');
            $passengerDetails = $bookingRecord[0]->bookingDetail;
            $busname = $bookingDetails[0]->bus->name;
            $busNumber = $bookingDetails[0]->bus->bus_number;
            $conductor_number = $bookingDetails[0]->bus->busContacts->phone;
            $journeydate = $bookingDetails[0]->journey_dt;
            $routedetails = $srcName.' To '.$destName;
            $departureTime = $bookingDetails[0]->boarding_time;
            $departureTime = date("H:i:s",strtotime($departureTime));
            $arrivalTime = $bookingDetails[0]->dropping_time;
            $bookingdate = $bookingRecord[0]->created_at;
            $bookingdate = date("d-m-Y", strtotime($bookingdate));
            $boarding_point = $bookingDetails[0]->boarding_point;
            $dropping_point = $bookingDetails[0]->dropping_point;
            $bustype = $bookingDetails[0]->bus->BusType->busClass->class_name;
            $busTypeName = $bookingDetails[0]->bus->BusType->name;
            $sittingType = $bookingDetails[0]->bus->BusSitting->name;
            $name = $bookingDetails[0]->users->name;
            $phone = $bookingDetails[0]->users->phone;
            $email = $bookingDetails[0]->users->email;
            $cancellationslabs = $bookingDetails[0]->bus->cancellationslabs->cancellationSlabInfo; 
            $transactionFee = $bookingRecord[0]->transactionFee;
            $customer_gst_status=$bookingRecord[0]->customer_gst_status;
            $customer_gst_number=$bookingRecord[0]->customer_gst_number;
            $customer_gst_business_name=$bookingRecord[0]->customer_gst_business_name;
            $customer_gst_business_email=$bookingRecord[0]->customer_gst_business_email;
            $customer_gst_business_address=$bookingRecord[0]->customer_gst_business_address;
            $customer_gst_percent=$bookingRecord[0]->customer_gst_percent;
            $customer_gst_amount=$bookingRecord[0]->customer_gst_amount;
            $coupon_discount=$bookingRecord[0]->coupon_discount;
        
            $smsData = array(
                "seat_no" => $seat_no,
                "passengerDetails" => $passengerDetails, 
                "busname" => $busname,
                "busNumber" => $busNumber,
                "journeydate" => $journeydate,
                "routedetails" => $srcName."-".$destName,
                "departureTime" => $departureTime,
                "phone" => $phone,
                "conductor_number" => $conductor_number,
            );

          

          $emailData = array(
            "pnr" => $pnr,
            "seat_no" => $seat_no,
            "passengerDetails" => $passengerDetails, 
            "busname" => $busname,
            "busNumber" => $busNumber,
            "phone" => $phone,
            "name" => $name,
            "email" => $email,
            "journeydate" => $journeydate,
            "bookingdate" => $bookingdate,
            "boarding_point" => $boarding_point,
            "arrivalTime" => $arrivalTime,
            "dropping_point" => $dropping_point,
            "routedetails" => $routedetails,
            "departureTime" => $departureTime,
            "conductor_number" => $conductor_number,
            "source" => $srcName,
            "destination" => $destName,
            "bustype" => $bustype,
            "busTypeName" => $busTypeName,
            "sittingType" => $sittingType,
           );

          
          

         /////////////////send email to odbus admin////////

        $this->channelRepository->sendAdminEmailTicket($amount,$discount,$payable_amount,$odbus_charges,$odbus_gst,$owner_fare,$emailData,$pnr,$cancellationslabs,$transactionFee,$customer_gst_status,$customer_gst_number,$customer_gst_business_name,$customer_gst_business_email,$customer_gst_business_address,$customer_gst_percent,$customer_gst_amount,$coupon_discount);

        ///////////////////CMO SMS/////////////////////////////////////////////////
        $busContactDetails = BusContacts::where('bus_id',$busId)
                                        ->where('status','1')
                                        ->where('booking_sms_send','1')
                                        ->get('phone');
         
        if($busContactDetails->isNotEmpty()){
           
            $contact_number = collect($busContactDetails)->implode('phone',',');
        
            $sms_gateway = OdbusCharges::where('user_id',$defUserId)->first()->sms_gateway;
            
            if($sms_gateway ==1){
               
            $sendSmsCMO =  $this->channelRepository->sendSmsCMO($amount,$smsData, $pnr, $contact_number);  
            }  
        }
         
        unset($bookingDetails[0]->bus->cancellationslabs); 
        unset($bookingDetails[0]->bus->cancellationslabs_id); 
    }
        $bal['wallet_balance'] = $bookingDetails[0]->clientWallet[0]->balance;
        $bal['final_pnr'] = $bookingRecord[0]->pnr; 

        return $bal;        
    }

    public function DolphinClientCancelTicketInfo($clientId,$pnr,$booked){
        return $this->booking->where([
            ['pnr', '=', $pnr],
            ['status', '=', $booked], 
            ['user_id', '=', $clientId],  
            ])
            ->select('id','pnr','users_id','user_id','bus_id','source_id','destination_id','client_comission','journey_dt','boarding_point','dropping_point','boarding_time','dropping_time','total_fare','payable_amount')
            ->with(['users'=> function($u){
            $u->select('id','name','email','phone');   
            }])
            ->with('bookingDetail')
            ->get();
    }
    public function MantisClientCancelTicketInfo($clientId,$pnr,$booked){
        return $this->booking->where([
            ['pnr', '=', $pnr],
            ['status', '=', $booked], 
            ['user_id', '=', $clientId],  
            ])
            ->select('id','pnr','users_id','user_id','bus_id','source_id','destination_id','client_comission','journey_dt','boarding_point','dropping_point','boarding_time','dropping_time','total_fare','payable_amount','tkt_no')
            ->with(['users'=> function($u){
            $u->select('id','name','email','phone');   
            }])
            ->with('bookingDetail')
            ->get();
    }

    public function clientCancelTicket($clientId,$pnr,$booked)
    { 
        return $this->booking->where([
                                    ['pnr', '=', $pnr],
                                    ['status', '!=', 0], 
                                    ['user_id', '=', $clientId],  
                                    ])
                ->select('id','pnr','users_id','user_id','bus_id','source_id','destination_id','client_comission','journey_dt','boarding_point','dropping_point','boarding_time','dropping_time','total_fare','client_gst','status','deduction_percent','refund_amount')
               ->with(['users'=> function($u){
                  $u->select('id','name','email','phone');   
               }])
               ->with(["bus" => function($bs){
                $bs->select('id','name','bus_number','bus_type_id','bus_sitting_id','cancellationslabs_id');
                $bs->with(['cancellationslabs'=> function($c){
                    $c->select('id','rule_name','cancellation_policy_desc');
                    $c->with(['cancellationSlabInfo' => function($cs){
                        $cs->select('cancellation_slab_id','duration','deduction');
                        }]);
                    }]);              
                }])
                ->with(["bookingDetail" => function($b){
                    $b->select('id','booking_id','bus_seats_id','passenger_name','passenger_gender');
                    $b->with(["busSeats" => function($bs){
                        $bs->select('id','seats_id');
                        $bs->with(["seats" => function($s){
                            $s->select('id','seatText');  
                        }]);
                    }]);    
                }])
            //   ->with(['clientWallet' => function($cw){
            //     $cw->select('booking_id','balance');
            //     $cw->orderBy('id','DESC');
            //     $cw->where("status",1);
            //     $cw->limit(1);
            //     }])
              ->get();
    }

    public function updateClientCancelTicket($bookingId,$userId,$data){
       
        $bookingCancelled = Config::get('constants.BOOKED_CANCELLED');
       
        $clientDetails = ClientWallet::where('user_id',$userId)
                                        ->orderBy('id','DESC')->where("status",1)->limit(1)
                                        ->get();                               
        
        $transactionId = date('YmdHis') . gettimeofday()['usec'];
        $clientWallet = new ClientWallet();
        $clientWallet->transaction_id = $transactionId;
        $clientWallet->amount = $data['refundAmount'];
        $clientWallet->type = 'Refund';
        $clientWallet->booking_id = $bookingId;
        $clientWallet->transaction_type = 'c';
        $clientWallet->balance = $clientDetails[0]->balance + $data['refundAmount'];
        $clientWallet->user_id = $userId;
        $clientWallet->created_by = $clientDetails[0]->created_by;
        $clientWallet->status = 1;
        
        $clientWallet->save();

        $clientDetails =  ClientWallet::where('user_id',$userId)->orderBy('id','DESC')->where("status",1)->limit(1)->get(); 
    
        if($data['ClientCancelCommission'] > 0){
            
            $transactionId = date('YmdHis') . gettimeofday()['usec'];
            $clientWallet = new ClientWallet();
            $clientWallet->transaction_id = $transactionId;
            //$clientWallet->amount = $deductAmt/2;
            $clientWallet->amount = $data['ClientCancelCommission'];
            $clientWallet->type = 'CancelCommission';
            $clientWallet->booking_id = $bookingId;
            $clientWallet->transaction_type = 'c';
            //$clientWallet->balance = $clientDetails[0]->balance + ($deductAmt/2);
            $clientWallet->balance = $clientDetails[0]->balance + $data['ClientCancelCommission'];
            $clientWallet->user_id = $userId;
            $clientWallet->created_by = $clientDetails[0]->created_by;
            $clientWallet->status = 1;
            $clientWallet->save(); 
        }
        
        $this->booking->where('id', $bookingId)->update(['status' => $bookingCancelled,'refund_amount' => $data['refundAmount'], 'deduction_percent' => $data['Percentage'],'deduction_amount' => $data['deductAmount'], 'odbus_cancel_profit' => $data['OdbusCancelCommission']]);             
        
        return $clientWallet;
    }
    //////ticketDetails(client use)//////////////
    public function bookingDetails($mobile,$pnr)
    { 
        
      $data= Users::where('phone',$mobile)->with(["booking" => function($u) use($pnr){
        $u->where('booking.pnr', $pnr);            
        $u->with(["bus" => function($bs){
            $bs->with('cancellationslabs.cancellationSlabInfo');
            $bs->with('BusType.busClass');
            $bs->with('BusSitting');                
            $bs->with('busContacts');
          } ] );             
        $u->with(["bookingDetail" => function($b){
            $b->with(["busSeats" => function($s){
                $s->with("seats");
              }]);
            }]); 
          }])->get();

         
           return $data;

    }

}