<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Repositories\ClientBookingRepository;
use App\Services\ViewSeatsService;
use App\Repositories\ChannelRepository;
use App\Repositories\CancelTicketRepository;
use App\Repositories\BookingManageRepository;
use App\Repositories\CommonRepository;
use App\Models\TicketPrice;
use App\Models\BusCancelled;
use App\Models\BookingSeized;
use App\Models\OdbusCharges;
use App\Models\Booking;
use App\Models\BusSeats;
use App\Models\User;
use App\Models\ClientFeeSlab;
use App\Models\Location;
use App\Models\BusContacts;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Transformers\DolphinTransformer;
use App\Transformers\MantisTransformer;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ClientBookingService
{
    
    protected $clientBookingRepository;  
    protected $viewSeatsService; 
    protected $channelRepository; 
    protected $commonRepository;
    protected $cancelTicketRepository;
    protected $dolphinTransformer;
    protected $bookingManageRepository;  
    protected $mantisTransformer;  

    public function __construct(ClientBookingRepository $clientBookingRepository,ViewSeatsService $viewSeatsService,ChannelRepository $channelRepository,CommonRepository $commonRepository,CancelTicketRepository $cancelTicketRepository,BookingManageRepository $bookingManageRepository,DolphinTransformer $dolphinTransformer,MantisTransformer $mantisTransformer)
    {
        $this->clientBookingRepository = $clientBookingRepository;
        $this->viewSeatsService = $viewSeatsService;
        $this->channelRepository = $channelRepository;
        $this->commonRepository = $commonRepository;
        $this->cancelTicketRepository = $cancelTicketRepository;
        $this->bookingManageRepository = $bookingManageRepository;
        $this->dolphinTransformer = $dolphinTransformer;
        $this->mantisTransformer = $mantisTransformer;

    }
    public function clientBooking($request,$clientRole,$clientId)
    {

        try {
            $ReferenceNumber = (isset($request['bookingInfo']['ReferenceNumber'])) ? $request['bookingInfo']['ReferenceNumber'] : '';
            $origin = (isset($request['bookingInfo']['origin'])) ? $request['bookingInfo']['origin'] : 'ODBUS';
                if($origin !='DOLPHIN' && $origin != 'ODBUS' && $origin != 'MANTIS'){
                    return 'Invalid Origin';
                }else if($origin == 'DOLPHIN'){
                    if($ReferenceNumber ==''){    
                        return 'ReferenceNumber_empty';
                    }
                }
                if($origin == 'ODBUS'){
                    ////////// gender validation
                	$rrr = $this->genderValidate($request,$clientRole,$clientId);
                    
                	if($rrr != null){
                		return $rrr;
                	}
                }
            $bookTicket = $this->clientBookingRepository->clientBooking($request,$clientRole,$clientId);
            return $bookTicket;
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }  
    }   

    public function seatBlock($request,$clientRole,$clientId)
    {

        try {
                $seatHold = Config::get('constants.SEAT_HOLD_STATUS');  
                $transationId = $request['transaction_id']; 
                $records = $this->channelRepository->getBookingRecord($transationId);
                $busId = $records[0]->bus_id;
                $sourceId = $records[0]->source_id;
                $destinationId = $records[0]->destination_id;
                $entry_date = $records[0]->journey_dt;
                $entry_date = date("Y-m-d", strtotime($entry_date));
                $origin = $records[0]->origin;
                if(isset($request['IsAcBus'])){
                    $IsAcBus = $request['IsAcBus'];
                }else{
                     $IsAcBus = false;
                }

                if($origin == 'ODBUS') {
                    $bookingDetails = Booking::where('transaction_id', $transationId)
                                            ->with(["bookingDetail" => function($b){
                                                $b->with(["busSeats" => function($bs){
                                                    $bs->with(["seats" => function($s){ 
                                                    }]);
                                                }]);    
                                            }])
                                            ->get();
                    $busId = $bookingDetails[0]->bus_id; 
                    $sourceId = $bookingDetails[0]->source_id;
                    $destinationId = $bookingDetails[0]->destination_id;
                    $entry_date = $bookingDetails[0]->journey_dt;
                    
                    $seatIds = [];
                    foreach($bookingDetails[0]->bookingDetail as $bd){
                        array_push($seatIds,$bd->busSeats->seats->id);              
                    }  
                    $data = array(
                        'busId' => $busId,
                        'sourceId' =>  $sourceId,
                        'destinationId' => $destinationId,
                        'entry_date' => $entry_date,
                        'seatIds' => $seatIds,
                    ); 
                    $routeDetails = TicketPrice::where('source_id', $sourceId)
                                ->where('destination_id', $destinationId)
                                ->where('bus_id', $busId)
                                ->where('status','1')
                                ->get();
                    /////////////seize time recheck////////////////////////
                
                    //$CurrentDateTime = "2022-09-09 07:46:35";
                    $CurrentDateTime = Carbon::now();//->toDateTimeString();
                    if(isset($routeDetails[0])){
                    $seizedTime = $routeDetails[0]->seize_booking_minute;
                    $depTime = date("H:i:s", strtotime($routeDetails[0]->dep_time)); 
                    
                    $depDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $entry_date.' '.$depTime);
                    $diff_in_minutes = $depDateTime->diffInMinutes($CurrentDateTime);
                        if($depDateTime>=$CurrentDateTime){
                            $diff_in_minutes = $depDateTime->diffInMinutes($CurrentDateTime);
                        }else{
                            $diff_in_minutes = 0;
                        }
                        /////////////day wise seize time change////////////////////////////////
                        $dayWiseSeizeTime = BookingSeized::where('ticket_price_id',$routeDetails[0]->id)
                        ->where('seized_date', $entry_date)
                        ->where('status', 1)
                        ->get('seize_booking_minute');   

                        if(!$dayWiseSeizeTime->isEmpty()){
                            $dWiseSeizeTime = $dayWiseSeizeTime[0]->seize_booking_minute;
                            if($dWiseSeizeTime > $diff_in_minutes){
                                return "BUS_SEIZED";
                            }
                        }
                        elseif($seizedTime > $diff_in_minutes){
                            return "BUS_SEIZED";
                        }
                    }                             
                    ///////////////////////cancelled bus recheck////////////////////////            
                    $startJDay = $routeDetails[0]->start_j_days;
                    $ticketPriceId = $routeDetails[0]->id;

                    switch($startJDay){
                        case(1):
                            $new_date = $entry_date;
                            break;
                        case(2):
                            $new_date = date('Y-m-d', strtotime('-1 day', strtotime($entry_date)));
                            break;
                        case(3):
                            $new_date = date('Y-m-d', strtotime('-2 day', strtotime($entry_date)));
                            break;
                    }   
                    $cancelledBus = BusCancelled::where('bus_id', $busId)
                                                ->where('status', '1')
                                                ->with(['busCancelledDate' => function ($bcd) use ($new_date){
                                                $bcd->where('cancelled_date',$new_date);
                                                }])->get(); 
                
                    $busCancel = $cancelledBus->pluck('busCancelledDate')->flatten();
                    if(isset($busCancel) && $busCancel->isNotEmpty()){
                        return "BUS_CANCELLED";
                    }
                    /////////////////seat block recheck////////////////////////
                    $blockSeats = BusSeats::where('operation_date', $entry_date)
                                            ->where('type',2)
                                            ->where('bus_id',$busId)
                                            ->where('status',1)
                                            ->where('ticket_price_id',$ticketPriceId)
                                            ->whereIn('seats_id',$seatIds)
                                            ->get();                        
                    if(isset($blockSeats) && $blockSeats->isNotEmpty()){
                        return "SEAT_BLOCKED";
                    }
                    $bookedHoldSeats = $this->viewSeatsService->checkBlockedSeats($data);
                    $intersect = collect($bookedHoldSeats)->intersect($seatIds);
                }
                elseif($origin=='DOLPHIN') {
                        $intersect=[];
                        $res= $this->dolphinTransformer->BlockSeat($records,$clientRole);
                        if($res['Status']!=1){
                            return  $res['Message'];
                        }
                    }
                else if($origin =='MANTIS') {
                        ////
                        $seatTexts = $records[0]->bookingDetail->pluck('seat_name');
                        $mantisSeatresult = $this->mantisTransformer->MantisSeatLayout($sourceId,$destinationId,$entry_date,$busId,$clientRole,$clientId);
                        //return $mantisSeatresult;
                        $seater = [];
                        $lbSleeper = [];
                        $ubSleeper = [];
                        $sleeper = [];
                    
                        if(isset($mantisSeatresult['lower_berth'])){
                            $seater = collect($mantisSeatresult['lower_berth'])->whereIn('seatText', $seatTexts)->where('berthType',1)->pluck('id');

                            $lbSleeper = collect($mantisSeatresult['lower_berth'])->whereIn('seatText', $seatTexts)->where('berthType',2)->pluck('id');
                        }
                        if(isset($mantisSeatresult['upper_berth'])){
                            $ubSleeper = collect($mantisSeatresult['upper_berth'])->whereIn('seatText', $seatTexts)->where('berthType',2)->pluck('id');
                        }
                        $sleeper = collect($lbSleeper)->merge(collect($ubSleeper));
                        
                        $data = array(
                            'busId' => $busId,
                            'sourceId' => $sourceId,
                            'destinationId' => $destinationId,
                            'seater' => $seater,
                            'sleeper' => $sleeper,
                            'entry_date' => $entry_date,
                            'origin' => $origin,
                        );
                        $priceDetails = $this->viewSeatsService->getPriceOnSeatsSelection($data,$clientRole,$clientId);
                        ////
                        $intersect = [];

                        $res = $this->mantisTransformer->HoldSeatsClient($sourceId,$destinationId,$entry_date,$busId,$records,$clientRole,$clientId,$IsAcBus);
                        if(!$res["success"]){ 
                            return $res["Error"]["Msg"];
                        }
                      }    
            $amount = $records[0]->total_fare;  
            /////////////// calculate customer GST  (customet gst = (owner fare + service charge) - Coupon discount)
            if($origin=='ODBUS' || ($origin=='DOLPHIN' && $res['Status']==1) || ($origin =='MANTIS' && $res["success"])) {
            $masterSetting=$this->commonRepository->getCommonSettings('1'); // 1 stands for ODBSU is from user table to get maste setting data
            if($request['customer_gst_status']== 1){
                if($origin =='MANTIS') { 
                    $update_customer_gst['customer_gst_status'] = 1; 
                    $update_customer_gst['owner_fare'] = $priceDetails[0]['baseFare'];
                    $update_customer_gst['customer_gst_percent'] = 5.00;//as discussed with Santosh
                    $update_customer_gst['customer_gst_amount'] = $priceDetails[0]['ownerFare'] - $priceDetails[0]['baseFare'];
                    }
                else{
                    $update_customer_gst['customer_gst_status']=1;
                    $update_customer_gst['customer_gst_number']=$request['customer_gst_number'];
                    $update_customer_gst['customer_gst_business_name']=$request['customer_gst_business_name'];
                    $update_customer_gst['customer_gst_business_email']=$request['customer_gst_business_email'];
                    $update_customer_gst['customer_gst_business_address']=$request['customer_gst_business_address'];
                    $update_customer_gst['customer_gst_percent']=$masterSetting[0]->customer_gst;
                  
                    if($records[0]->customer_gst_amount==0){
                        $customer_gst_amount= round((( ($records[0]->owner_fare+$records[0]->odbus_charges) ) *$masterSetting[0]->customer_gst)/100,2);
                        $amount = round($amount+$customer_gst_amount,2);
                        $update_customer_gst['payable_amount']=$amount;
                        $update_customer_gst['customer_gst_amount']=$customer_gst_amount;
                    }
                }
            }else{
                $amount = round($amount - $records[0]->customer_gst_amount,2);
                $update_customer_gst['customer_gst_status']=0;
                $update_customer_gst['customer_gst_number']=null;
                $update_customer_gst['customer_gst_business_name']=null;
                $update_customer_gst['customer_gst_business_email']=null;
                $update_customer_gst['customer_gst_business_address']=null;
                $update_customer_gst['customer_gst_percent']=0;                    
                $update_customer_gst['customer_gst_amount']=0;
                $update_customer_gst['payable_amount']=$amount;    
                }
                $this->channelRepository->updateCustomerGST($update_customer_gst,$transationId);
                if(count($intersect)){
                    return "SEAT UN-AVAIL";
                }else{
                    $bookingId = $records[0]->id;   
                    $name = $records[0]->users->name; 
                    //Update Booking Ticket Status in booking Change status to 4(Seat on hold)   
                    $this->channelRepository->UpdateStatus($bookingId, $seatHold);

                    /////mantis holdId updated to booking table////////
                    if($origin=='MANTIS'){
                        $holdId = $res["data"]['HoldId'];
                        $this->channelRepository->UpdateMantisHoldId($transationId,$holdId);   
                    } 

                    $data = array(                        
                        'status' => "Success",
                        'customer_name' => $name,
                        'amount' => $amount,
                    );
                    return $data;         
            } 
          }            
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }   
    } 
 
    public function ticketConfirmation($request,$clientRole)
    {


        try {
            $records = $this->channelRepository->getBookingRecord($request['transaction_id']);
            $transationId = $request['transaction_id'];
            $origin=$records[0]->origin;
            if($origin=='DOLPHIN') {
                $res = $this->dolphinTransformer->BookSeat($records,$clientRole);
                $bookingRecord = $records;
                if($res['Status']==1 && $res['PNRNO']){
                   $updateApiData['api_pnr']=$res['PNRNO'];
                   $updateApiData['pnr']=$res['PNRNO'];
                   $updateApiData['bus_name']="DOLPHIN TOURS & TRAVELS";
                   $this->channelRepository->UpdateAPIPnr($request['transaction_id'],$updateApiData);                 
                }else{
                    return 'Failed';
                }
            } 
            //////Mantis changes///////
            if($origin=='MANTIS') {

                $bookingRecord = $records;
                $holdId = $bookingRecord[0]->holdId;

                $res = $this->mantisTransformer->BookSeat($records,$holdId);
                if($res["success"]) 
                {  
                    $updatebookingDt['pnr'] = $res["data"]["PNRNo"];
                    $updatebookingDt['api_pnr'] = $res["data"]["PNRNo"];
                    $updatebookingDt['tkt_no'] = $res["data"]["TicketNo"];;
                    $this->channelRepository->UpdateMantisAPIPnr($transationId,$updatebookingDt);
                }else{
                        return $res["Error"]["Msg"];
                }
            }
           
            $bookTicket = $this->clientBookingRepository->ticketConfirmation($request);
           //// paytm driver api call
            if($records[0]->user_id==env('PAYTM_ID')){
                PaytmdriverCallBackAPI($records[0]->pnr); 
            }
           

           
            return $bookTicket;
        } catch (Exception $e) {
            Log::info("ticket confirmation api");   
            Log::info($e->getMessage());   
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }   
    }  
    
    public function clientCancelTicket($request)////////admin panel use
    {
        try {        
            $pnr = $request['pnr'];
            $clientId = $request['user_id'];
            $booked = Config::get('constants.BOOKED_STATUS');
            $booking_detail = $this->clientBookingRepository->clientCancelTicket($clientId,$pnr,$booked);

            if(isset($booking_detail[0])){ 

                if($booking_detail[0]->status==2){
                    return "Ticket_already_cancelled";
                }
                       $jDate =$booking_detail[0]->journey_dt;
                       $jDate = date("d-m-Y", strtotime($jDate));
                       $boardTime =$booking_detail[0]->boarding_time; 
                       $seat_arr=[];
                       foreach($booking_detail[0]->bookingDetail as $bd){
                       
                          $seat_arr = Arr::prepend($seat_arr, $bd->busSeats->seats->seatText);
                       }
                       $busName = $booking_detail[0]->bus->name;
                       $busNumber = $booking_detail[0]->bus->bus_number;
                       $sourceName = $this->cancelTicketRepository->GetLocationName($booking_detail[0]->source_id);                   
                       $destinationName =$this->cancelTicketRepository->GetLocationName($booking_detail[0]->destination_id);
                       $route = $sourceName .'-'. $destinationName;
                       $userMailId = $booking_detail[0]->users->email;
                       $phone = $booking_detail[0]->users->phone;
                       $combinedDT = date('Y-m-d H:i:s', strtotime("$jDate $boardTime"));
                       $current_date_time = Carbon::now()->toDateTimeString(); 
                       $bookingDate = new DateTime($combinedDT);
                       $cancelDate = new DateTime($current_date_time);
                       $interval = $bookingDate->diff($cancelDate);
                       $interval = ($interval->format("%a") * 24) + $interval->format(" %h");
                       
                       $smsData = array(
                           'phone' => $phone,
                           'PNR' => $pnr,
                           'busdetails' => $busName.'-'.$busNumber,
                           'doj' => $jDate, 
                           'route' => $route,
                           'seat' => $seat_arr
                       );
                       $emailData = array(
                           'email' => $userMailId,
                           'contactNo' => $phone,
                           'pnr' => $pnr,
                           'journeydate' => $jDate, 
                           'route' => $route,
                           'seat_no' => $seat_arr,
                           'cancellationDateTime' => $current_date_time
                       );
                       
                       if($cancelDate >= $bookingDate || $interval < 12)
                       {
                       return "CANCEL_NOT_ALLOWED";
                       }
                       $userId = $booking_detail[0]->user_id;
                       $bookingId = $booking_detail[0]->id;
                       $srcId = $booking_detail[0]->source_id;
                       $desId = $booking_detail[0]->destination_id;
                       $paidAmount = $booking_detail[0]->total_fare;
                       $client_comission = $booking_detail[0]->client_comission;
                       $paid_amount_without_gst=$paidAmount-$booking_detail[0]->client_gst;
                      // Log::Info("line 419- ".$paid_amount_without_gst);
                       $sourceName = Location::where('id',$srcId)->first()->name;
                       $destinationName = Location::where('id',$desId)->first()->name;
                       
                       $data['source'] = $sourceName;
                       $data['destination'] = $destinationName;
                       $data['bookingDetails'] = $booking_detail;
   
                       if($booking_detail[0]->status==2){
                           $data['cancel_status'] = false;
                       }else{
                           $data['cancel_status'] = true;
                       }
                       
                       $cancelPolicies = $booking_detail[0]->bus->cancellationslabs->cancellationSlabInfo;
                      
                       foreach($cancelPolicies as $cancelPolicy){
                          $duration = $cancelPolicy->duration;
                          $deduction = $cancelPolicy->deduction;
                          $duration = explode("-", $duration, 2);
                          $max= $duration[1];
                          $min= $duration[0];

                          
       
                          if( $interval > 999){
                            
                              $deduction = 10;//minimum deduction 
                              
                              $data['totalfare'] = $paidAmount;
                              $data['Percentage'] = $deduction;
                              $data['deductionPercentage'] = $deduction."%";
                              $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                              $data['deductAmount'] = $deductAmt;
                            
                              $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                              $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount 12
                              $refundAmt = round(($refundAmt + $gstOnRefund),2);  
                              $data['refundAmount'] = $refundAmt;

                              $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_comission);
                              $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                              $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit'];
                              $data['gstOnRefund'] = $gstOnRefund;
                              
                              $clientWallet = $this->clientBookingRepository->updateClientCancelTicket($bookingId,$userId,$data); 
                             
                              $smsData['refundAmount'] = $refundAmt;     
                              $emailData['deductionPercentage'] = $deduction; // 20%
                              $emailData['refundAmount'] = $refundAmt;
                              $emailData['totalfare'] = $paidAmount;
                             
                            
                              return $data;
          
                          }elseif($min <= $interval && $interval <= $max){ 
                           
                               $data['totalfare'] = $paidAmount;
                              $data['Percentage'] = $deduction;
                              $data['deductionPercentage'] = $deduction."%";
                              $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                              $data['deductAmount'] = $deductAmt;
                            
                              $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                              $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount
                              $refundAmt = round(($refundAmt + $gstOnRefund),2);  

                              $data['refundAmount'] = $refundAmt;
                              $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_comission);
                              $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                              $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit'];  
                              $data['gstOnRefund'] = $gstOnRefund;         
                            
                              $clientWallet = $this->clientBookingRepository->updateClientCancelTicket($bookingId,$userId,$data); 

                              $smsData['refundAmount'] = $refundAmt; 
                              $emailData['deductionPercentage'] = $deduction;
                              $emailData['refundAmount'] = $refundAmt;
                              $emailData['totalfare'] = $paidAmount;
                              
                          
                              return $data;   
                          }
                      }                          
          } 
          else{            
              return "INV_CLIENT";            
          }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }    
    }   

    public function clientCancelTicketInfo($request)////admin panel use
    {
        try {        
            $pnr = $request['pnr'];
            $clientId = $request['user_id'];
            $booked = Config::get('constants.BOOKED_STATUS');   

            $pnr_dt = $this->bookingManageRepository->getPnrInfo($pnr); 

            if($pnr_dt->origin=='DOLPHIN'){    
                $booking_detail= $this->clientBookingRepository->DolphinClientCancelTicketInfo($clientId,$pnr,$booked);
                 if(isset($booking_detail[0])){ 
                            $dolphin_cancel_det= $this->dolphinTransformer->cancelTicketInfo($pnr_dt->api_pnr);                      
                             if($dolphin_cancel_det['RefundAmount']==0 && $dolphin_cancel_det['TotalFare']==0){
                                return 'Ticket_already_cancelled';
                             }
                                $emailData['cancel_status'] ="true";
                                $emailData['refundAmount'] = $dolphin_cancel_det['RefundAmount'];                                
                                $emailData['totalfare'] = $booking_detail[0]->payable_amount; 
                               return $emailData;
                    }    
                    else{                
                        return "INV_CLIENT";                
                    }
            }
            elseif($pnr_dt->origin == 'MANTIS'){    
                $booking_detail = $this->clientBookingRepository->MantisClientCancelTicketInfo($clientId,$pnr,$booked);
                 if(isset($booking_detail[0])){ 
                        $bookingId = $booking_detail[0]->id;
                        $tktNo = $booking_detail[0]->tkt_no;
                        $seatArr = $this->cancelTicketRepository->getSeatNames($bookingId);
                        $collection = collect($seatArr);
                        $seatNos = $collection->implode(',');
                        $res = $this->mantisTransformer->isCancellable($pnr,$tktNo,$seatNos);
                        if($res["success"]){ 
                            $emailData['cancel_status'] ="true";
                            $emailData['refundAmount'] = $res['data']['RefundAmount'];
                            $emailData['deductAmount'] =$deductAmount =  round($res['data']['TotalFare'] - $res['data']['RefundAmount'], 2);  
                            $emailData['totalfare'] = $totalfare = $res['data']['TotalFare'];    
                            $emailData['deductionPercentage'] = $res['data']['ChargePct'].'%';
                            return $emailData;   
                        }elseif(!$res["success"]){ 
                            return $res["error"];
                        }
                    }    
                    else{                
                        return "INV_CLIENT";                
                    }
            }                    
            elseif($pnr_dt->origin=='ODBUS'){
            $booking_detail = $this->clientBookingRepository->clientCancelTicket($clientId,$pnr,$booked);
           
            if(isset($booking_detail[0])){ 

                if($booking_detail[0]->status==2){
                    return "Ticket_already_cancelled";
                }

                       $jDate =$booking_detail[0]->journey_dt;
                       $jDate = date("d-m-Y", strtotime($jDate));
                       $boardTime =$booking_detail[0]->boarding_time; 
                       $combinedDT = date('Y-m-d H:i:s', strtotime("$jDate $boardTime"));
                       $current_date_time = Carbon::now()->toDateTimeString(); 
                       $bookingDate = new DateTime($combinedDT);
                       $cancelDate = new DateTime($current_date_time);
                       $interval = $bookingDate->diff($cancelDate);
                       $interval = ($interval->format("%a") * 24) + $interval->format(" %h");
                
                       if($cancelDate >= $bookingDate || $interval < 12)
                       {
                       return "CANCEL_NOT_ALLOWED";
                       }

                       $userId = $booking_detail[0]->user_id;
                       $paidAmount = $booking_detail[0]->total_fare; 
                       $client_comission = $booking_detail[0]->client_comission;
                       $paid_amount_without_gst=$paidAmount-$booking_detail[0]->client_gst;
                       //Log::Info("line 587- ".$paid_amount_without_gst);
                       if($booking_detail[0]->status==2){
                           $data['cancel_status'] = false;
                       }else{
                           $data['cancel_status'] = true;
                       }
                       
                       $cancelPolicies = $booking_detail[0]->bus->cancellationslabs->cancellationSlabInfo;
                       foreach($cancelPolicies as $cancelPolicy){
                          $duration = $cancelPolicy->duration;
                          $deduction = $cancelPolicy->deduction;
                          $duration = explode("-", $duration, 2);
                          $max= $duration[1];
                          $min= $duration[0];
       
                          if( $interval > 999){
                            
                              $deduction = 10;//minimum deduction 
                              $data['totalfare'] = $paidAmount;
                              $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                              $data['deductAmount'] = $deductAmt;
                              $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                              $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount
                              $refundAmt = round(($refundAmt + $gstOnRefund),2);  
                             $data['refundAmount'] = $refundAmt;   
                             $data['gstOnRefund'] = $gstOnRefund;                          
                              $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_comission);
                            
                              return $data;
          
                          }elseif($min <= $interval && $interval <= $max){ 
                            $data['totalfare'] = $paidAmount;
                            $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                            $data['deductAmount'] = $deductAmt;                          
                          
                            $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                            $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount
                            $refundAmt = round(($refundAmt + $gstOnRefund),2);  
                            $data['refundAmount'] = $refundAmt;
                            $data['gstOnRefund'] = $gstOnRefund;

                              $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_comission);
                                    
                              return $data;   
                          }
                      }                          
            }else{         
                return "INV_CLIENT";            
            }
          }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }    
    }  
    
    public function clientCancelTicketInfos($request)////client panel use
    {
        try {        
            $pnr = $request['pnr'];
            $clientId = $request['user_id'];
            $booked = Config::get('constants.BOOKED_STATUS');
            $pnr_dt = $this->bookingManageRepository->getPnrInfo($pnr); 

            if($pnr_dt->origin=='DOLPHIN'){    
                $booking_detail= $this->clientBookingRepository->DolphinClientCancelTicketInfo($clientId,$pnr,$booked);
                 if(isset($booking_detail[0])){ 
                            $dolphin_cancel_det= $this->dolphinTransformer->cancelTicketInfo($pnr_dt->api_pnr);                      
                             if($dolphin_cancel_det['RefundAmount']==0 && $dolphin_cancel_det['TotalFare']==0){
                                return 'Ticket_already_cancelled';
                             }
                                $emailData['cancel_status'] ="true";
                                $emailData['refundAmount'] = (int)$dolphin_cancel_det['RefundAmount'];                                
                                $emailData['totalfare'] = $booking_detail[0]->payable_amount; 
                               return $emailData;
                    }    
                    else{                
                        return "INV_CLIENT";                
                    }
            }
            elseif($pnr_dt->origin == 'MANTIS'){    
                $booking_detail = $this->clientBookingRepository->MantisClientCancelTicketInfo($clientId,$pnr,$booked);
                 if(isset($booking_detail[0])){ 
                        $bookingId = $booking_detail[0]->id;
                        $tktNo = $booking_detail[0]->tkt_no;
                        $seatArr = $this->cancelTicketRepository->getSeatNames($bookingId);
                        $collection = collect($seatArr);
                        $seatNos = $collection->implode(',');
                        $res = $this->mantisTransformer->isCancellable($pnr,$tktNo,$seatNos);
                        if($res["success"]){ 
                            $emailData['cancel_status'] ="true";
                            $emailData['refundAmount'] = $res['data']['RefundAmount'];
                            $emailData['deductAmount'] =$deductAmount =  round($res['data']['TotalFare'] - $res['data']['RefundAmount'], 2);  
                            $emailData['totalfare'] = $totalfare = $res['data']['TotalFare'];    
                            $emailData['deductionPercentage'] = $res['data']['ChargePct'].'%';
                            return $emailData;   
                        }elseif(!$res["success"]){ 
                            return $res["error"];
                        }
                    }    
                    else{                
                        return "INV_CLIENT";                
                    }
            }            
            elseif($pnr_dt->origin=='ODBUS'){
            $booking_detail = $this->clientBookingRepository->clientCancelTicket($clientId,$pnr,$booked);

           // dd($clientId,$pnr,$booked);
                if(isset($booking_detail[0])){ 

                    if($booking_detail[0]->status==2){
                        return "Ticket_already_cancelled";
                    }

                        $jDate =$booking_detail[0]->journey_dt;
                        $jDate = date("d-m-Y", strtotime($jDate));
                        $boardTime =$booking_detail[0]->boarding_time; 
                        $combinedDT = date('Y-m-d H:i:s', strtotime("$jDate $boardTime"));
                        $current_date_time = Carbon::now()->toDateTimeString(); 
                        $bookingDate = new DateTime($combinedDT);
                        $cancelDate = new DateTime($current_date_time);
                        $interval = $bookingDate->diff($cancelDate);
                        $interval = ($interval->format("%a") * 24) + $interval->format(" %h");
                        
                        if($cancelDate >= $bookingDate || $interval < 12)
                        {
                        return "CANCEL_NOT_ALLOWED";
                        }
                        $paidAmount = $booking_detail[0]->total_fare;
                        $client_commission = $booking_detail[0]->client_comission;
                        $paid_amount_without_gst=$paidAmount- $booking_detail[0]->client_gst;

                        //Log::Info("line 718- ".$paid_amount_without_gst);

                        $userId = $booking_detail[0]->user_id;
                        if($booking_detail[0]->status==2){
                            $data['cancel_status'] = false;
                        }else{
                            $data['cancel_status'] = true;
                        }
                        $cancelPolicies = $booking_detail[0]->bus->cancellationslabs->cancellationSlabInfo;
                        foreach($cancelPolicies as $cancelPolicy){
                            $duration = $cancelPolicy->duration;
                            $deduction = $cancelPolicy->deduction;
                            $duration = explode("-", $duration, 2);
                            $max= $duration[1];
                            $min= $duration[0];
                            if( $interval > 999){
                                $deduction = 10;//minimum deduction 
                                $data['totalfare'] = $paidAmount;
                                $data['deductionPercentage'] = $deduction."%";
                                $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                                $data['deductAmount'] = $deductAmt;                             
                                $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                                $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount
                                $refundAmt = round(($refundAmt + $gstOnRefund),2);  
                                $data['refundAmount'] = $refundAmt;
                                $data['gstOnRefund'] = $gstOnRefund;
                                $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_commission);
                                $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                                $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit'];

                                return $data;
                            }elseif($min <= $interval && $interval <= $max){ 
                                $data['totalfare'] = $paidAmount;
                                $data['deductionPercentage'] = $deduction."%";
                                $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                                $data['deductAmount'] = $deductAmt;                             
                                $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                                $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount
                                $refundAmt = round(($refundAmt + $gstOnRefund),2);                              
                                $data['refundAmount'] = $refundAmt;
                                $data['gstOnRefund'] = $gstOnRefund;
                                $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_commission);
                                $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                                $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit'];  
                                 return $data;   
                            }
                        }                          
                }else{         
                    return "INV_CLIENT";            
            }
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }    
    }   
    
    public function cancelCommission($userId,$deductAmt,$client_commission){
        $clientCancelComPer =0;
        $clientCancelCom = ClientFeeSlab::where('user_id',$userId)->first();

        if($clientCancelCom){
            $clientCancelComPer = $clientCancelCom->cancellation_commission;
        }
        
        if($clientCancelComPer == 0){
            $OdbusCancelProfit = $deductAmt;
            $clientCancelProfit = 0; 
        }else{
          $OdbusCancelProfit = round($deductAmt * ((100 - $clientCancelComPer))/100,2); 
          $clientCancelProfit = round($deductAmt - $OdbusCancelProfit,2);
        }
        $cancelCom = array(
                            "OdbusCancelProfit" => $OdbusCancelProfit, 
                            "clientCancelProfit" => ($clientCancelProfit - $client_commission) // if booking cancel then cancel profit - client commission amout will be refunded to client wallet . 16 March ,2024 
                    );
        return $cancelCom;
    }

    public function clientTicketCancel($request)////////client panel use
    {
        $defUserId = Config::get('constants.USER_ID');

        try {        
            $pnr = $request['pnr'];
            $clientId = $request['user_id'];
            $booked = Config::get('constants.BOOKED_STATUS');
            $pnr_dt = $this->bookingManageRepository->getPnrInfo($pnr); 
            if($pnr_dt->origin=='DOLPHIN'){    
                $booking_detail= $this->clientBookingRepository->DolphinClientCancelTicketInfo($clientId,$pnr,$booked);
                        if(isset($booking_detail[0])){ 
                            $userId = $booking_detail[0]->user_id;

                            $client_commission = $booking_detail[0]->client_comission;

                            $bookingId = $booking_detail[0]->id;
                            $dolphin_cancel_det= $this->dolphinTransformer->ConfirmCancellation($pnr_dt->api_pnr);                      
                            if($dolphin_cancel_det['Status']==0){
                                return 'Ticket_already_cancelled';
                            }                        
                                $emailData['cancel_status'] ="true";
                                $emailData['refundAmount'] = (int)$dolphin_cancel_det['RefundAmount'];                                
                                $emailData['totalfare'] = $totalfare=$booking_detail[0]->payable_amount;  
                                $data['refundAmount']=$refundAmt=$dolphin_cancel_det['RefundAmount'];
                                $deductAmount =  $booking_detail[0]->payable_amount - $dolphin_cancel_det['RefundAmount'];
                                $deduction=round((($deductAmount / $totalfare) * 100),1);
                                $data['Percentage'] = $deduction;
                                $data['deductAmount'] = $deductAmount;
                                $data['totalfare'] = $totalfare;
                                $cancelComCal = $this->cancelCommission($userId,$deductAmount,$client_commission);
                                $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                                $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit']; 
                                $clientWallet = $this->clientBookingRepository->updateClientCancelTicket($bookingId,$userId,$data);  
                               return $emailData;
                    }    
                    else{                
                        return "INV_CLIENT";                
                    }
            }
            if($pnr_dt->origin=='MANTIS'){    
                $booking_detail = $this->clientBookingRepository->MantisClientCancelTicketInfo($clientId,$pnr,$booked);
                        if(isset($booking_detail[0])){ 
                            $userId = $booking_detail[0]->user_id;
                            $client_commission = $booking_detail[0]->client_comission;

                            $bookingId = $booking_detail[0]->id;
                            $tktNo = $booking_detail[0]->tkt_no;
                            $seatArr = $this->cancelTicketRepository->getSeatNames($bookingId);
                            $collection = collect($seatArr);
                            $seatNos = $collection->implode(',');
                            $res = $this->mantisTransformer->isCancellable($pnr,$tktNo,$seatNos);
                            if($res["success"]){ 
                                $mantis_cancel_res = $this->mantisTransformer->cancelSeats($pnr,$tktNo,$seatNos); 
                                if(!$mantis_cancel_res["success"]){
                                    return $res["Error"]["Msg"];
                                }
                                elseif($mantis_cancel_res["success"]){
                                    $emailData['cancel_status'] ="true";
                                    $emailData['refundAmount'] = $mantis_cancel_res['data']['RefundAmount'];
                                    $emailData['totalfare'] = $totalfare = $mantis_cancel_res['data']['TotalFare'];
                                    $emailData['deductAmount'] = $deductAmount = $mantis_cancel_res['data']['ChargeAmt'];
                                    $data['refundAmount'] = $refundAmt = $mantis_cancel_res['data']['RefundAmount'];
                                    $data['Percentage'] = $deduction = $mantis_cancel_res['data']['ChargePct'];
                                    $data['deductAmount'] = $deductAmount;
                                    $data['totalfare'] = $totalfare;
                                    $cancelComCal = $this->cancelCommission($userId,$deductAmount,$client_commission);
                                    $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                                    $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit']; 
                                    $clientWallet = $this->clientBookingRepository->updateClientCancelTicket($bookingId,$userId,$data);  
                                    return $emailData;
                                }    
                            }
                            elseif(!$res["success"]){ 
                                return $res["error"];
                            }     
                    }    
                    else{                
                        return "INV_CLIENT";                
                    }
            }                
            elseif($pnr_dt->origin=='ODBUS'){

            $booking_detail = $this->clientBookingRepository->clientCancelTicket($clientId,$pnr,$booked);
            
            if(isset($booking_detail[0])){ 

                if($booking_detail[0]->status==2){
                    return "Ticket_already_cancelled";
                }
               
                       $jDate =$booking_detail[0]->journey_dt;
                       $jDate = date("d-m-Y", strtotime($jDate));
                       $boardTime =$booking_detail[0]->boarding_time; 
                       $seat_arr=[];
                       foreach($booking_detail[0]->bookingDetail as $bd){
                       
                          $seat_arr = Arr::prepend($seat_arr, $bd->busSeats->seats->seatText);
                       }
                       $busId = $booking_detail[0]->bus_id;
                       $busName = $booking_detail[0]->bus->name;
                       $busNumber = $booking_detail[0]->bus->bus_number;
                       $sourceName = $this->cancelTicketRepository->GetLocationName($booking_detail[0]->source_id);                   
                       $destinationName =$this->cancelTicketRepository->GetLocationName($booking_detail[0]->destination_id);
                       $route = $sourceName .' To '. $destinationName;
                       $userMailId = $booking_detail[0]->users->email;
                       $phone = $booking_detail[0]->users->phone;
                       $combinedDT = date('Y-m-d H:i:s', strtotime("$jDate $boardTime"));
                       $current_date_time = Carbon::now()->toDateTimeString(); 
                       $bookingDate = new DateTime($combinedDT);
                       $cancelDate = new DateTime($current_date_time);
                       $interval = $bookingDate->diff($cancelDate);
                       $interval = ($interval->format("%a") * 24) + $interval->format(" %h");
                       
                       $cancelPolicies = $booking_detail[0]->bus->cancellationslabs->cancellationSlabInfo;
                       $smsData = array(
                           'phone' => $phone,
                           'PNR' => $pnr,
                           'busdetails' => $busName.'-'.$busNumber,
                           'doj' => $jDate, 
                           'route' => $route,
                           'seat' => $seat_arr
                       );
                       $emailData = array(
                           'email' => $userMailId,
                           'contactNo' => $phone,
                           'pnr' => $pnr,
                           'journeydate' => $jDate, 
                           'route' => $route,
                           'seat_no' => $seat_arr,
                           'cancellationDateTime' => $current_date_time,
                           'origin' => $booking_detail[0]->origin,
                            'bus_name' => $busName,
                            'transaction_fee' => $booking_detail[0]->transactionFee,
                           'cancelation_policy'=> $cancelPolicies
                       );
                       
                       if($cancelDate >= $bookingDate || $interval < 12)
                       {
                       return "CANCEL_NOT_ALLOWED";
                       }
                       $userId = $booking_detail[0]->user_id;
                       $bookingId = $booking_detail[0]->id;
                       $srcId = $booking_detail[0]->source_id;
                       $desId = $booking_detail[0]->destination_id;
                       $paidAmount = $booking_detail[0]->total_fare;
                       $client_commission = $booking_detail[0]->client_comission;
                       $paid_amount_without_gst = $paidAmount - $booking_detail[0]->client_gst;

                       $sourceName = Location::where('id',$srcId)->first()->name;
                       $destinationName = Location::where('id',$desId)->first()->name;
                       
                    //    $data['source'] = $sourceName;
                    //    $data['destination'] = $destinationName;
                    //    $data['bookingDetails'] = $booking_detail;
   
                       if($booking_detail[0]->status==2){
                           $data['cancel_status'] = false;
                       }else{
                           $data['cancel_status'] = true;
                       }
                       
                       $cancelPolicies = $booking_detail[0]->bus->cancellationslabs->cancellationSlabInfo;
                       foreach($cancelPolicies as $cancelPolicy){
                          $duration = $cancelPolicy->duration;
                          $deduction = $cancelPolicy->deduction;
                          $duration = explode("-", $duration, 2);
                          $max= $duration[1];
                          $min= $duration[0];
       
                          if( $interval > 999){
                            
                              $deduction = 10;//minimum deduction 
                              $data['totalfare'] = $paidAmount;
                              $data['Percentage'] = $deduction;
                              $data['deductionPercentage'] = $deduction."%";
                              $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                              $data['deductAmount'] = $deductAmt;
                              $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                                $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount
                                $refundAmt = round(($refundAmt + $gstOnRefund),2); 

                              $data['refundAmount'] = $refundAmt;
                              $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_commission);
                              $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                              $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit'];                            
                              $data['gstOnRefund'] = $gstOnRefund;
                              $clientWallet = $this->clientBookingRepository->updateClientCancelTicket($bookingId,$userId,$data); 
                             
                              $smsData['refundAmount'] = $refundAmt;     
                              $emailData['deductionPercentage'] = $deduction;
                              $emailData['refundAmount'] = $refundAmt;
                              $emailData['totalfare'] = $paidAmount;
                            
                              $this->cancelTicketRepository->sendAdminEmailTicketCancel($emailData); 

                              ////////////////////////////CMO SMS SEND ON TICKET CANCEL////////////////
                             $busContactDetails = BusContacts::where('bus_id',$busId)
                             ->where('status','1')
                             ->where('cancel_sms_send','1')
                             ->get('phone');
                            if($busContactDetails->isNotEmpty()){
                            $contact_number = collect($busContactDetails)->implode('phone',',');
                            $sms_gateway = OdbusCharges::where('user_id',$defUserId)->first()->sms_gateway;

            
                         if($sms_gateway ==1){
                            $this->channelRepository->sendSmsTicketCancelCMO($smsData,$contact_number);
                         }
                            }  
                            unset($data['bookingDetails'][0]->bus->cancellationslabs); 
                            unset($data['bookingDetails'][0]->bus->cancellationslabs_id);   
                              return $data;
          
                          }elseif($min <= $interval && $interval <= $max){ 
                           
                            $data['totalfare'] = $paidAmount;
                            $data['Percentage'] = $deduction;
                            $data['deductionPercentage'] = $deduction."%";
                            $deductAmt = round(($paid_amount_without_gst/100)*$deduction,2);
                            $data['deductAmount'] = $deductAmt;
                         
                            $refundAmt = round(($paid_amount_without_gst - $deductAmt),2) ;  
                            $gstOnRefund=round($refundAmt * 0.05 ,2);  // 5% GST on Refund amount
                            $refundAmt = round(($refundAmt + $gstOnRefund),2);  

                            $data['refundAmount'] = $refundAmt;
                            $data['gstOnRefund'] = $gstOnRefund;

                              //$data['cancelCommission'] = $deductAmt/2; 
                              
                              
                                     
                              $cancelComCal = $this->cancelCommission($userId,$deductAmt,$client_commission);
                              $data['OdbusCancelCommission'] = $cancelComCal['OdbusCancelProfit']; 
                              $data['ClientCancelCommission'] = $cancelComCal['clientCancelProfit'];
                            
                              $clientWallet = $this->clientBookingRepository->updateClientCancelTicket($bookingId,$userId,$data); 

                             // Log::Info("line 1035");
                             // Log::Info($data);
                              
                              $smsData['refundAmount'] = $refundAmt; 
                              $emailData['deductionPercentage'] = $deduction;
                              $emailData['refundAmount'] = $refundAmt;
                              $emailData['totalfare'] = $paidAmount;
                          
                               $this->cancelTicketRepository->sendAdminEmailTicketCancel($emailData); 

                              ////////////////////////////CMO SMS SEND ON TICKET CANCEL////////////////
                             $busContactDetails = BusContacts::where('bus_id',$busId)
                                                                ->where('status','1')
                                                                ->where('cancel_sms_send','1')
                                                                ->get('phone');
                             if($busContactDetails->isNotEmpty()){
                              $contact_number = collect($busContactDetails)->implode('phone',',');

                              $sms_gateway = OdbusCharges::where('user_id',$defUserId)->first()->sms_gateway;

            
                         if($sms_gateway ==1){
                            $this->channelRepository->sendSmsTicketCancelCMO($smsData,$contact_number);
                         }
                             
                             }
                              unset($data['bookingDetails'][0]->bus->cancellationslabs); 
                              unset($data['bookingDetails'][0]->bus->cancellationslabs_id);  
                              unset($data['Percentage']);  
                              return $data;   
                          }
                      }                          
            } 
            else{            
              return "INV_CLIENT";            
            }
          }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }    
    }
    ////////ticketDetails(client use)//////////
    public function ticketDetails($request)
    {
        try {
            $pnr = $request['pnr'];
            $mobile = $request['mobile'];

            $pnr_dt = $this->bookingManageRepository->getPnrInfo($pnr);


            if($pnr_dt && $pnr_dt->origin=='DOLPHIN'){

                $booking_detail = $this->bookingManageRepository->getDolphinBookingDetails($mobile,$pnr); 

                if(isset($booking_detail[0])){ 
                    if(isset($booking_detail[0]->booking[0]) && !empty($booking_detail[0]->booking[0])){ 
                      
                        $departureTime = $booking_detail[0]->booking[0]->boarding_time;
                        $arrivalTime = $booking_detail[0]->booking[0]->dropping_time;
                        $depTime = date("h:i A",strtotime($departureTime));
                        $arrTime = date("h:i A",strtotime($arrivalTime)); 
    
                        $jdays=0;

                        if(stripos($depTime,'AM') > -1 && stripos($arrTime,'PM') > -1){
                            $jdays = 1;                           
                            $departureTime =date("Y-m-d ".$departureTime);
                            $arrivalTime =date("Y-m-d ".$arrivalTime);
                        }
    
                        if(stripos($depTime,'PM') > -1 && stripos($arrTime,'AM') > -1){
                            $jdays = 2;
                            $tomorrow = date("Y-m-d", strtotime("+1 day"));
                            $departureTime =date("Y-m-d ".$departureTime);
                            $arrivalTime =$tomorrow." ".$arrivalTime; 
                        }

                        $j_endDate = $booking_detail[0]->booking[0]->journey_dt;

                        $arr_time = new DateTime($arrivalTime);
                        $dep_time = new DateTime($departureTime);
                        $totalTravelTime = $dep_time->diff($arr_time);
                        $totalJourneyTime = ($totalTravelTime->format("%a") * 24) + $totalTravelTime->format(" %h"). "h". $totalTravelTime->format(" %im");

    
                        switch($jdays)
                        {
                            case(1):
                                $j_endDate = $booking_detail[0]->booking[0]->journey_dt;
                                break;
                            case(2):
                                $j_endDate = date('Y-m-d', strtotime('+1 day', strtotime($booking_detail[0]->booking[0]->journey_dt)));
                                break;
                            case(3):
                                $j_endDate = date('Y-m-d', strtotime('+2 day', strtotime($booking_detail[0]->booking[0]->journey_dt)));
                                break;
                        }
    
    
                         $booking_detail[0]->booking[0]['source']=$this->bookingManageRepository->GetLocationName($booking_detail[0]->booking[0]->source_id);
                         $booking_detail[0]->booking[0]['destination']=$this->bookingManageRepository->GetLocationName($booking_detail[0]->booking[0]->destination_id);  
                         $booking_detail[0]->booking[0]['journeyDuration'] =  $totalJourneyTime;
                         $booking_detail[0]->booking[0]['journey_end_dt'] =  $j_endDate;           
                         $booking_detail[0]->booking[0]['created_date'] = date('Y-m-d',strtotime($booking_detail[0]->booking[0]['created_at']));           
                                          
                         
                       // return $booking_detail;                  
                    }                
                    else{                
                         return "PNR_NOT_MATCH";                
                    }
                }            
                else{            
                    return "MOBILE_NOT_MATCH";            
                }

            }else{

            $booking_detail = $this->clientBookingRepository->bookingDetails($mobile,$pnr); 

            if(isset($booking_detail[0])){ 
                if(isset($booking_detail[0]->booking[0]) && !empty($booking_detail[0]->booking[0])){ 
                    
                    $ticketPriceRecords = TicketPrice::where('bus_id', $booking_detail[0]->booking[0]->bus_id)
                    ->where('source_id', $booking_detail[0]->booking[0]->source_id)
                    ->where('destination_id', $booking_detail[0]->booking[0]->destination_id)
                    ->get(); 
    
                    $departureTime = $ticketPriceRecords[0]->dep_time;
                    $arrivalTime = $ticketPriceRecords[0]->arr_time;
                    $depTime = date("H:i",strtotime($departureTime));
                    $arrTime = date("H:i",strtotime($arrivalTime)); 
                    $jdays = $ticketPriceRecords[0]->j_day;
                    $arr_time = new DateTime($arrivalTime);
                    $dep_time = new DateTime($departureTime);
                    $totalTravelTime = $dep_time->diff($arr_time);
                    $totalJourneyTime = ($totalTravelTime->format("%a") * 24) + $totalTravelTime->format(" %h"). "h". $totalTravelTime->format(" %im");

                    switch($jdays)
                    {
                        case(1):
                            $j_endDate = $booking_detail[0]->booking[0]->journey_dt;
                            break;
                        case(2):
                            $j_endDate = date('Y-m-d', strtotime('+1 day', strtotime($booking_detail[0]->booking[0]->journey_dt)));
                            break;
                        case(3):
                            $j_endDate = date('Y-m-d', strtotime('+2 day', strtotime($booking_detail[0]->booking[0]->journey_dt)));
                            break;
                    }

                     $booking_detail[0]->booking[0]['source']=$this->bookingManageRepository->GetLocationName($booking_detail[0]->booking[0]->source_id);
                     $booking_detail[0]->booking[0]['destination']=$this->bookingManageRepository->GetLocationName($booking_detail[0]->booking[0]->destination_id);  
                     $booking_detail[0]->booking[0]['journeyDuration'] =  $totalJourneyTime;
                     $booking_detail[0]->booking[0]['journey_end_dt'] =  $j_endDate;           
                     //$booking_detail[0]->booking[0]['created_date'] = date('Y-m-d',strtotime($booking_detail[0]->booking[0]['created_at']));           
                     //$booking_detail[0]->booking[0]['updated_date'] =   date('Y-m-d',strtotime($booking_detail[0]->booking[0]['updated_at']));                    
                     
                   // return $booking_detail;                  
                }                
                else{                
                     return "PNR_NOT_MATCH";                
                }
            }            
            else{            
               // return "MOBILE_NOT_MATCH";            
                return "RECORD_NOT_FOUND";            
            }
         }

         $response['name']=$booking_detail[0]->name;
         $response['email']=$booking_detail[0]->email;
         $response['phone']=$booking_detail[0]->phone;
         $response['transaction_id']=$booking_detail[0]->booking[0]->transaction_id;
         $response['pnr']=$booking_detail[0]->booking[0]->pnr;
         $response['journey_dt']=$booking_detail[0]->booking[0]->journey_dt;
         $response['source']=$booking_detail[0]->booking[0]->source[0]->name;
         $response['destination']=$booking_detail[0]->booking[0]->destination[0]->name;
         $response['boarding_point']=$booking_detail[0]->booking[0]->boarding_point;
         $response['dropping_point']=$booking_detail[0]->booking[0]->dropping_point;
         $response['boarding_time']=$booking_detail[0]->booking[0]->boarding_time;
         $response['dropping_time']=$booking_detail[0]->booking[0]->dropping_time;
         $response['origin']=$booking_detail[0]->booking[0]->origin;
         $response['status']=$booking_detail[0]->booking[0]->status;
         if($booking_detail[0]->booking[0]->status==1){
            $response['booking_status']='Confirmed';
         }  
         elseif($booking_detail[0]->booking[0]->status==2){
            $response['booking_status']='Cancelled';
            $response['deduction_percent']=$booking_detail[0]->booking[0]->deduction_percent;
            $response['refund_amount']=$booking_detail[0]->booking[0]->refund_amount;
            $response['cancel_comission']=$booking_detail[0]->booking[0]->client_comission;
            
         } 

         elseif($booking_detail[0]->booking[0]->status==0){
            $response['booking_status']='Pending';
         } 
         
         $response['total_fare']=$booking_detail[0]->booking[0]->payable_amount;

         if(is_array($booking_detail[0]->booking[0]->bus)){
            $response['bus_name']=$booking_detail[0]->booking[0]->bus['name'];
            $response['bus_number']=$booking_detail[0]->booking[0]->bus['bus_number'];
            $response['cancellationslabs']=$booking_detail[0]->booking[0]->bus['cancellationslabs']['cancellation_slab_info'];
            $response['bus_contact_detail']=$booking_detail[0]->booking[0]->pickup_details;
         }

         if(is_object($booking_detail[0]->booking[0]->bus)){
            $response['bus_name']=$booking_detail[0]->booking[0]->bus->name;
            $response['bus_number']=$booking_detail[0]->booking[0]->bus->bus_number;

            $cancellationslabsInfo=[];

        if($booking_detail[0]->booking[0]->bus->cancellationslabs->cancellationSlabInfo){
            foreach($booking_detail[0]->booking[0]->bus->cancellationslabs->cancellationSlabInfo as $p){
   
                $plc["duration"]=$p->duration;
                $plc["deduction"]=(int)$p->deduction;

                $cancellationslabsInfo[]=$plc;       
            }         
           } 

            $response['cancellationslabs']=$cancellationslabsInfo;
            $response['bus_contact_detail']=$booking_detail[0]->booking[0]->bus->busContacts->phone;
         }

       
        
         $response['journeyDuration']=$booking_detail[0]->booking[0]->journeyDuration;

         $passenger_details=[];

         foreach($booking_detail[0]->booking[0]->bookingDetail as $bd){
            $ps['name']=$bd->passenger_name;
            $ps['gender']=$bd->passenger_gender;
            $ps['age']=$bd->passenger_age;

            if($booking_detail[0]->booking[0]->origin=='DOLPHIN'){
                $ps['seat_name']=$bd->seat_name;
            }

            if($booking_detail[0]->booking[0]->origin=='ODBUS'){
                $ps['seat_name']=$bd->busSeats->seats->seatText;
            }

            $passenger_details[]= $ps;
            
         }

         $response['passenger_details']=$passenger_details;

         return $response;
            
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }
       
    }  


    public function genderValidate($request,$clientRole,$clientId){
    	$bookingInfo = $request['bookingInfo'];
                ////////////////////////busId validation////////////////////////////////////
                $sourceID = $bookingInfo['source_id'];
                $destinationID = $bookingInfo['destination_id'];
                $origin = $bookingInfo['origin'];
                $ReferenceNumber = (isset($bookingInfo['ReferenceNumber'])) ? $bookingInfo['ReferenceNumber'] : '';


                	$arrvst['sourceId']=$sourceID;
                	$arrvst['destinationId']=$destinationID;
                	$arrvst['busId']=$bookingInfo['bus_id'];
                	$arrvst['entry_date']=$bookingInfo['journey_dt'];
                	$arrvst['origin']=$origin;
                	$arrvst['ReferenceNumber']=$ReferenceNumber;

                	$seatArray=$this->viewSeatsService->getAllViewSeats($arrvst,$clientRole,$clientId);


            ///////// logic for seat select gender restriction

            $prevGender = null;
            $genderRestrictSeatarray=[];


            $seatIds = Arr::pluck($bookingInfo['bookingDetail'], 'bus_seats_id');

            $selectedAray=[];

             if(isset($seatArray['lower_berth'])){

                 foreach($seatArray['lower_berth'] as $sat){ 
                    foreach ($seatIds as $st) {
                        if($sat['id']==$st){             			
                        $selectedAray[]=$sat;
                        }
                    }
                }  

            }
           
            if(isset($seatArray['upper_berth'])){
                foreach($seatArray['upper_berth'] as $sat){ 
                    foreach ($seatIds as $st) {
                        if($sat['id']==$st){             			
                        $selectedAray[]=$sat;
                        }
                    }
                }
            }

                foreach ($selectedAray as $k => $itm) { 
                    if(isset($seatArray['lower_berth'])){
                        foreach($seatArray['lower_berth'] as $at){ 

                        if( $itm['colNumber'] == $at['colNumber'] && 
                            ($itm['rowNumber']- $at['rowNumber'] == -1 || $itm['rowNumber'] - $at['rowNumber'] == 1)  
                            && $at['seatText']!='' && $itm['id'] !=$at['id'] && $at['Gender']){ 

                                $sst=[
                                "seat_id" => $itm['id'],
                                "canSelect" => $at['Gender'],
                                "seat_name" => $itm['seatText']
                                ];


                                $genderRestrictSeatarray[]=$sst;
        
                            }		                           
                        }
		            }

                    if(isset($seatArray['upper_berth'])){
                        foreach($seatArray['upper_berth'] as $at){
                            if( $itm['colNumber'] == $at['colNumber'] && 
                            ($itm['rowNumber']- $at['rowNumber'] == -1 || $itm['rowNumber'] - $at['rowNumber'] == 1)  
                            && $at['seatText']!='' && $itm['id'] !=$at['id'] && $at['Gender']){ 

                                $sst=[
                                "seat_id" => $itm['id'],
                                "canSelect" => $at['Gender'],
                                "seat_name" => $itm['seatText']
                                ];
                                $genderRestrictSeatarray[]=$sst;
        
                            }		                           

                        }
                    }

                }
                

          if($genderRestrictSeatarray){
          	foreach ($genderRestrictSeatarray as $value) {
          		foreach ($bookingInfo['bookingDetail'] as $b) {
          			if($value['seat_id'] == $b['bus_seats_id'] && $value['canSelect'] =='F' &&($b['passenger_gender'] =='M' || $b['passenger_gender'] =='male' || $b['passenger_gender'] =='Male') ){
          				$msg= 'Male is not allowed for seat no '.$value['seat_name'];

          				 return $arr=['status'=>'Gender Error','message' => $msg];
          			}

          			if($value['seat_id'] == $b['bus_seats_id'] && $value['canSelect'] =='M' && ($b['passenger_gender'] =='F' || $b['passenger_gender'] =='female' || $b['passenger_gender'] =='Female' ) ){
          				 $msg= 'Female is not allowed for seat no '.$value['seat_name'];

          				 return $arr=['status'=>'Gender Error','message' => $msg];
          			}

          		}
          		
          	}

          }
           
           /////////////////////////////
    }   

    public function walletBalance($request){

        $ClientId=$request['ClientId'];

        if(Auth()->user()->client_id ==  $ClientId){
           $res=DB::table('client_wallet as c')->select('c.balance')->leftJoin('user as u','c.user_id','=','u.id')->where('u.client_id',$ClientId)->orderBy('c.id','DESC')->limit(1)->first();

            return $res;

        }else{
            return "Unauthorized";
        }

    }
   
}