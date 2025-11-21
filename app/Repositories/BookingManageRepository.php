<?php
namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Location;
use App\Models\Users;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\CustomerPayment;
use App\Models\BusType;
use App\Models\BusClass;
use App\Models\BusSeats;
use App\Models\BusContacts;
use App\Models\Seats;
use App\Models\TicketPrice;
use App\Jobs\SendEmailTicketJob;
use App\Jobs\SendFeedbackEmailJob;
use App\Models\Credentials;
use App\Models\AgentWallet;
use App\Models\Notification;
use App\Models\UserNotification;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use DateTime;
use App\Transformers\DolphinTransformer;
use App\Transformers\MantisTransformer;
use Illuminate\Support\Str;
use App\Services\ValueFirstService;



class BookingManageRepository
{
    protected $bus;
    protected $dolphinTransformer;

    protected $ticketPrice;
    protected $location;
    protected $users;
    protected $booking;
    protected $busSeats;
    protected $seats;
    protected $bookingDetail;
    protected $busType;
    protected $busClass;
    protected $credentials;
    protected $customerPayment;
    protected $mantisTransformer;

    public function __construct(Bus $bus,TicketPrice $ticketPrice,Location $location,Users $users,
    BusSeats $busSeats,Booking $booking,BookingDetail $bookingDetail, Seats $seats,BusClass $busClass
    ,BusType $busType,Credentials $credentials,CustomerPayment $customerPayment,DolphinTransformer $dolphinTransformer,MantisTransformer $mantisTransformer)
    {
        $this->bus = $bus;
        $this->ticketPrice = $ticketPrice;
        $this->location = $location;
        $this->users = $users;
        $this->busSeats = $busSeats;
        $this->seats = $seats;
        $this->booking = $booking;
        $this->bookingDetail = $bookingDetail;
        $this->busType = $busType;
        $this->busClass = $busClass;
        $this->credentials = $credentials;
        $this->customerPayment = $customerPayment;
        $this->dolphinTransformer = $dolphinTransformer;
        $this->mantisTransformer = $mantisTransformer;

    }   
    
    public function getJourneyDetails($mobile,$pnr)
    { 
       return $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
            $u->where('booking.pnr', '=', $pnr);
            $u->with(["bus" => function($bs){
                $bs->with('BusType.busClass');
                $bs->with('BusSitting');
            }]); 
        }])->get();   
    }

    public function GetLocationName($location_id){
        return $this->location->where('id',$location_id)->get();
    }

    public function getPassengerDetails($mobile,$pnr)
    { 
       return $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
                                                $u->where('booking.pnr', '=', $pnr);
                                                $u->with(["bookingDetail" => function($b){
                                                    $b->with(["busSeats" => function($s){
                                                        $s->with("seats");
                                                    } ]);
                                                } ]);
                                            }])->get();
       
    }

    public function getPnrInfo($pnr){

        return $this->booking->with('users')->where("pnr",$pnr)->first();
        
    }

    public function GetPnr($trans_id){

        $dd= $this->booking->where("transaction_id",$trans_id)->where('status',1)->first();

       return $dd;
        
    }

    

    public function all_pnr(){

        $before2days=date('Y-m-d',strtotime(" - 24 hours"));

       $all=  $this->booking->with('users')
                             ->where("status",1)
                             ->where("feedback_status",0)
                             ->where("journey_dt",$before2days)
                             ->get();
       //send email  
       
       $ins=0;
       
        if($all){
        foreach($all as $a){

            if($a->users->email != ''){ 
                
                $ins++;


                $this->booking->where('id', $a->id)->update(['feedback_status' => 1]);    


                $sendEmailTicket = $this->sendFeedbackEmail($a->users->name,$a->pnr,$a->journey_dt,$a->users->email); 
           }
            
        }
        }

        //Log::info($ins." email has been sent");

        
    }

    public function sendFeedbackEmail($name,$pnr,$journey_dt,$email){

        return  SendFeedbackEmailJob::dispatch($name,$pnr,$journey_dt,$email);



    }

    public function getMantisBookingDetails($mobile,$pnr){

        $bookingDtls = $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
            $u->where('booking.pnr', '=', $pnr); 
            $u->with("bookingDetail"); 
              }])->get();    
        $sourceId = $bookingDtls[0]->booking[0]->source_id;
        $destId = $bookingDtls[0]->booking[0]->destination_id;
        $busId = $bookingDtls[0]->booking[0]->bus_id;
        $jDt = $bookingDtls[0]->booking[0]->journey_dt;
        $dropPoint = $bookingDtls[0]->booking[0]->dropping_point;
        $slice = Str::before($dropPoint, '|');
        $slice = Str::of($slice)->trim();
        $busDetails = $this->mantisTransformer->searchBus($sourceId,$destId,$jDt,$busId);
        $bus["name"] = $busDetails['data']['Buses'][0]['CompanyName']; 
        $bus["bus_number"] = "";  
        $dropDateTime = collect($busDetails['data']['Buses'][0]['Dropoffs'])->where('DropoffName', $slice)->pluck('DropoffTime'); 
        if($dropDateTime){
        $bookingDtls[0]->booking[0]['journey_end_dt'] = date('Y-m-d',strtotime($dropDateTime[0]));
        }    
        $cancellationslabs = $busDetails['data']['Buses'][0]['Canc'];
        $cancellationslabsInfo = [];
        $collectCancPol = collect([]);
        if($cancellationslabs){
            foreach($cancellationslabs as $cs){
                $cancDed["deduction"] = $cs['Pct'];
                $collectCancPol->push($cs['Mins']/60);
                $cancellationslabsInfo[] = $cancDed;       
            }   
                $collectCancPol->push(9999);
                $chunks = $collectCancPol->sliding(2);
                $i =0;
                foreach($chunks as $chunk){
                    $cancellationslabsInfo[$i]["duration"] = $chunk->implode('-');
                    $i++;
                }   
        }  

        $bus["cancellationslabs"]["cancellation_slab_info"] = $cancellationslabsInfo;
        $bus["bus_type"]["name"] = $busDetails['data']['Buses'][0]['BusType']['IsAC'];;
        $bus["bus_type"]["bus_class"]=[
            "class_name" => ""
        ];

        $bus["bus_sitting"]["name"] = $busDetails['data']['Buses'][0]['BusType']['Seating'];
        $bus["bus_contacts"]["phone"] = ""; 

        $bookingDtls[0]->booking[0]['bus'] = $bus;
        $bookingDetail = $bookingDtls[0]->booking[0]->bookingDetail;
        
        foreach($bookingDetail as $k => $bd){
            $st["seatText"] = $bd->seat_name;  
            $stx["seats"] = $st;            
            $bookingDtls[0]->booking[0]['bookingDetail'][$k]["bus_seats"] = $stx;   
        }
        return $bookingDtls;

    }


    public function getDolphinBookingDetails($mobile,$pnr){

        $ar= $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
            $u->where('booking.pnr', '=', $pnr); 
            $u->with("bookingDetail"); 
              }])->get();

        $bus["bus_number"]=$ar[0]->booking[0]->bus_number;      
        $bus["name"]=$ar[0]->booking[0]->bus_name; 


        $cancellationslabs=$this->dolphinTransformer->GetCancellationPolicy();

        $cancellationslabsInfo=[];

        if($cancellationslabs){
            foreach($cancellationslabs as $p){
   
                $plc["duration"]=$p->duration;
                $plc["deduction"]=(int)$p->deduction;

                $cancellationslabsInfo[]=$plc;       
            }         
           } 

        $bus["cancellationslabs"]["cancellation_slab_info"]=$cancellationslabsInfo;
        $bus["bus_type"]["name"]='';
        $bus["bus_type"]["bus_class"]=[
            "class_name" => ""
        ];

        $bus["bus_sitting"]["name"]=""; 
        $bus["bus_contacts"]["phone"]=""; 

        $ar[0]->booking[0]['bus']= $bus;

        $bookingDetail=$ar[0]->booking[0]->bookingDetail;

        foreach($bookingDetail as $k => $bd){
            
            $st["seatText"]=$bd->seat_name;  
            $stx["seats"]= $st;            
            $ar[0]->booking[0]['bookingDetail'][$k]["bus_seats"]=$stx;
            
        }

        return $ar;

    }

    public function getBookingDetails($mobile,$pnr)
    { 
      return $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
        $u->where('booking.pnr', '=', $pnr);            
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
    }

    public function emailSms($request)
    { 
        $b= $this->getBookingDetails($request['mobile'],$request['pnr']);
      
        if($b && isset($b[0])){

            $main_source='';
            $main_destination='';

            $b=$b[0];
            $seat_arr=[];
            $seat_no='';
            foreach($b->booking[0]->bookingDetail as $bd){
                array_push($seat_arr,$bd->busSeats->seats->seatText);                 
            } 

            if($b->booking[0]->origin=='ODBUS') {
            
            $ticketPrice=DB::table('ticket_price')->where('bus_id', $b->booking[0]->bus_id)->first();
            
            $main_source=$this->GetLocationName($ticketPrice->source_id);
            $main_destination =$this->GetLocationName($ticketPrice->destination_id);

            }

            if($main_source!='' && $main_destination!=''){
                $main_source=$main_source[0]->name;
                $main_destination=$main_destination[0]->name;
            }else{
                $main_source= $b->booking[0]->source[0]->name;
                $main_destination=$b->booking[0]->destination[0]->name ;   
            }


            
            $body = [
                'name' => $b->name,
                'phone' => $b->phone,
                'email' => $b->email,
                'pnr' => $b->booking[0]->pnr,
                'bookingdate'=> $b->booking[0]->created_at,
                'journeydate' => $b->booking[0]->journey_dt ,
                'boarding_point'=> $b->booking[0]->boarding_point,
                'dropping_point' => $b->booking[0]->dropping_point,
                'departureTime'=> $b->booking[0]->boarding_time,
                'arrivalTime'=> $b->booking[0]->dropping_time,
                'seat_no' => $seat_arr,
                'busname'=> $b->booking[0]->bus->name,
                'source'=> $b->booking[0]->source[0]->name,  // changed on 13 April,2024
                'destination'=> $b->booking[0]->destination[0]->name, // changed on 13 April,2024
                'busNumber'=> $b->booking[0]->bus->bus_number,
                'bustype' => $b->booking[0]->bus->busType->name,
                'busTypeName' => $b->booking[0]->bus->busType->busClass->class_name,
                'sittingType' => $b->booking[0]->bus->busSitting->name, 
                'conductor_number'=> $b->booking[0]->bus->busContacts->phone,
                'passengerDetails' => $b->booking[0]->bookingDetail ,
                'totalfare'=> $b->booking[0]->total_fare,
                'discount'=> $b->booking[0]->coupon_discount,
                'payable_amount'=> $b->booking[0]->payable_amount,
                'odbus_gst'=> $b->booking[0]->odbus_gst_amount,
                'odbus_charges'=> $b->booking[0]->odbus_charges,
                'owner_fare'=> $b->booking[0]->owner_fare,
                'routedetails' => $main_source.' To '.$main_destination// $b->booking[0]->source[0]->name.' To '.$b->booking[0]->destination[0]->name    
            ];


            $cancellationslabs = $b->booking[0]->bus->cancellationslabs->cancellationSlabInfo;

            $transactionFee=$b->booking[0]->transactionFee;

            $customer_gst_status=$b->booking[0]->customer_gst_status;
            $customer_gst_number=$b->booking[0]->customer_gst_number;
            $customer_gst_business_name=$b->booking[0]->customer_gst_business_name;
            $customer_gst_business_email=$b->booking[0]->customer_gst_business_email;
            $customer_gst_business_address=$b->booking[0]->customer_gst_business_address;
            $customer_gst_percent=$b->booking[0]->customer_gst_percent;
            $customer_gst_amount=$b->booking[0]->customer_gst_amount;
            $coupon_discount=$b->booking[0]->coupon_discount;
            $totalfare=$b->booking[0]->total_fare;
            $discount=$b->booking[0]->coupon_discount;
            $payable_amount=$b->booking[0]->payable_amount;
            $odbus_charges = $b->booking[0]->odbus_charges;
            $odbus_gst = $b->booking[0]->odbus_gst_charges;
            $owner_fare = $b->booking[0]->owner_fare;

            if($b->phone != ''){
                $sendEmailTicket = $this->sendSmsTicket($body,$b->booking[0]->pnr); 
           }
          
            if($b->email != ''){  
                 $sendEmailTicket = $this->sendEmailTicket($totalfare,$discount,$payable_amount,$odbus_charges,$odbus_gst,$owner_fare,$body,$b->booking[0]->pnr,$cancellationslabs,$transactionFee,$customer_gst_status,$customer_gst_number,$customer_gst_business_name,$customer_gst_business_email,$customer_gst_business_address,$customer_gst_percent,$customer_gst_amount,$coupon_discount); 
            }
           
            return "Email & SMS has been sent to ".$b->email." & ".$b->phone;

        }else{
            return "Invalid request";   
        }
    }
    public function MantisCancelTicketInfo($mobile,$pnr){

        $bookingDtls = $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
         $u->where('booking.pnr', '=', $pnr); 
         $u->with(["customerPayment" => function($b){
             $b->where('payment_done',1);
         }]);          
         $u->with('bookingDetail');
        }])->get();    

        $bus["bus_number"] = "";      
        $bus["bus_description"]=""; 
        $sourceId = $bookingDtls[0]->booking[0]->source_id;
        $destId = $bookingDtls[0]->booking[0]->destination_id;
        $busId = $bookingDtls[0]->booking[0]->bus_id;
        $jDt = $bookingDtls[0]->booking[0]->journey_dt;
        $busDetails = $this->mantisTransformer->searchBus($sourceId,$destId,$jDt,$busId);
           
        $bus["name"] = $busDetails['data']['Buses'][0]['CompanyName']; 

        $cancellationslabs = $busDetails['data']['Buses'][0]['Canc'];
        $cancellationslabsInfo = [];
        $collectCancPol = collect([]);
        if($cancellationslabs){
            foreach($cancellationslabs as $cs){
                $cancDed["deduction"] = $cs['Pct'];
                $collectCancPol->push($cs['Mins']/60);
                $cancellationslabsInfo[] = $cancDed;       
            }   
                $collectCancPol->push(9999);
                $chunks = $collectCancPol->sliding(2);
                $i =0;
                foreach($chunks as $chunk){
                    $cancellationslabsInfo[$i]["duration"] = $chunk->implode('-');
                    $i++;
                }   
        } 
        $bus["cancellationslabs"]["cancellation_slab_info"] = $cancellationslabsInfo;
        $bus["bus_type"]["name"] = $busDetails['data']['Buses'][0]['BusType']['IsAC'];;
        $bus["bus_type"]["bus_class"]=[
            "class_name" => ""
        ];

        $bus["bus_sitting"]["name"] = $busDetails['data']['Buses'][0]['BusType']['Seating'];
        $bus["bus_contacts"]["phone"] = ""; 

        $bookingDtls[0]->booking[0]['bus'] = $bus;
        $bookingDetail = $bookingDtls[0]->booking[0]->bookingDetail;
        
        foreach($bookingDetail as $k => $bd){
            $st["seatText"] = $bd->seat_name;  
            $stx["seats"] = $st;            
            $bookingDtls[0]->booking[0]['bookingDetail'][$k]["bus_seats"] = $stx;   
        }
        return $bookingDtls;
     }
    public function DolphinCancelTicketInfo($mobile,$pnr){

        $ar= $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
         $u->where('booking.pnr', '=', $pnr); 
         $u->with(["customerPayment" => function($b){
             $b->where('payment_done',1);
         }]);          
         $u->with('bookingDetail');
        }])->get();    


        $bus["bus_number"]=$ar[0]->booking[0]->bus_number;  
        $bus["bus_description"]="";    
        $bus["name"]=$ar[0]->booking[0]->bus_name; 


        $cancellationslabs=$this->dolphinTransformer->GetCancellationPolicy();

        $cancellationslabsInfo=[];

        if($cancellationslabs){
            foreach($cancellationslabs as $p){
   
                $plc["duration"]=$p->duration;
                $plc["deduction"]=(int)$p->deduction;

                $cancellationslabsInfo[]=$plc;       
            }         
           } 

        $bus["cancellationslabs"]["cancellation_slab_info"]=$cancellationslabsInfo;
        $bus["bus_type"]["name"]='';
        $bus["bus_type"]["bus_class"]=[
            "class_name" => ""
        ];

        $bus["bus_sitting"]["name"]=""; 
        $bus["bus_contacts"]["phone"]=""; 

        $ar[0]->booking[0]['bus']= $bus;

        $bookingDetail=$ar[0]->booking[0]->bookingDetail;

        foreach($bookingDetail as $k => $bd){
            
            $st["seatText"]=$bd->seat_name;  
            $stx["seats"]= $st;            
            $ar[0]->booking[0]['bookingDetail'][$k]["bus_seats"]=$stx;
            
        }

        return $ar;

     }

    public function cancelTicketInfo($mobile,$pnr){

       return $this->users->where('phone',$mobile)->with(["booking" => function($u) use($pnr){
        $u->where('booking.pnr', '=', $pnr); 
        $u->with(["customerPayment" => function($b){
            $b->where('payment_done',1);
        }]);           
        $u->with(["bus" => function($bs){
            $bs->with('cancellationslabs.cancellationSlabInfo');
          }]);          
        $u->with(["bookingDetail" => function($b){
            $b->with(["busSeats" => function($s){
                $s->with("seats");
              }]);
         }]);
       }])->get();    
    }

    public function DolphinAgentCancelTicket($phone,$pnr,$booked){

        $ar= $this->users->where('phone',$phone)->with(["booking" => function($u) use($pnr,$booked){
            $u->where([
                ['booking.pnr', '=', $pnr],
                ['status', '=', $booked],
            ]);       
              $u->with('bookingDetail'); 
        }])->get();

        $bus["bus_number"]=$ar[0]->booking[0]->bus_number;      
        $bus["name"]=$ar[0]->booking[0]->bus_name; 


        $cancellationslabs=$this->dolphinTransformer->GetCancellationPolicy();

        $cancellationslabsInfo=[];

        if($cancellationslabs){
            foreach($cancellationslabs as $p){
   
                $plc["duration"]=$p->duration;
                $plc["deduction"]=(int)$p->deduction;

                $cancellationslabsInfo[]=$plc;       
            }         
           } 

        $bus["cancellationslabs"]["cancellation_slab_info"]=$cancellationslabsInfo;
        $bus["bus_type"]["name"]='';
        $bus["bus_type"]["bus_class"]=[
            "class_name" => ""
        ];

        $bus["bus_sitting"]["name"]=""; 
        $bus["bus_contacts"]["phone"]=""; 

        $ar[0]->booking[0]['bus']= $bus;

        $bookingDetail=$ar[0]->booking[0]->bookingDetail;

        foreach($bookingDetail as $k => $bd){
            
            $st["seatText"]=$bd->seat_name;  
            $stx["seats"]= $st;            
            $ar[0]->booking[0]['bookingDetail'][$k]["bus_seats"]=$stx;
            
        }

        return $ar;

     }

    //////////////////////////Agent Booking details////////////////////////
    public function agentCancelTicket($phone,$pnr,$booked)
    { 
        return Users::where('phone',$phone)->with(["booking" => function($u) use($pnr,$booked){
            $u->where([
                ['booking.pnr', '=', $pnr],
                ['status', '=', $booked],
            ]);
            //$u->with("customerPayment");           
            $u->with(["bus" => function($bs){
                $bs->with('cancellationslabs.cancellationSlabInfo');
              }]);          
            $u->with(["bookingDetail" => function($b){
                $b->with(["busSeats" => function($s){
                    $s->with("seats");
                  }]);
            }]);    
        }])->get();
    }
//created by Subhasis mohanty on 2-09-2025 for Value First OTP Service and textlocal otp service

 public function OTP($phone, $pnr, $otp, $bookingId)
{
   $SmsGW = config('services.sms.otpservice'); 
    if ($SmsGW === 'valuefirst') {
        return $this->OTP_valuefirst($phone, $pnr, $otp, $bookingId);
    } else if ($SmsGW === 'textLocal' ) {
        return $this->OTP_textlocal($phone, $pnr, $otp, $bookingId);
    }
}
    public function OTP_valuefirst($phone, $pnr, $otp, $bookingId)
{
    
    $message = "Your Ticket Cancellation OTP is {$otp} for PNR {$pnr} Plz share with your Agent - ODBUS";

   
    $valueFirstService = new ValueFirstService();
    $response = $valueFirstService->sendSms($phone, $message);
    
    $this->booking->where('id', $bookingId)->update(['cancel_otp' => $otp]);

    return $response;
}

    public function OTP_textlocal($phone,$pnr,$otp,$bookingId)
    {
        $SmsGW = config('services.sms.otpservice');

        if($SmsGW =='textLocal'){

            //Environment Variables
            //$apiKey = config('services.sms.textlocal.key');
            $apiKey = $this->credentials->first()->sms_textlocal_key;
            $textLocalUrl = config('services.sms.textlocal.url_send');
            $sender = config('services.sms.textlocal.senderid');
            $message = config('services.sms.textlocal.cancelTicketOTP');
            $apiKey = urlencode( $apiKey);
            $receiver = urlencode($phone);
          
            $message = str_replace("<otp>",$otp,$message);
            $message = str_replace("<pnr>",$pnr,$message);

            //return $message;
            $message = rawurlencode($message);
            $response_type = "json"; 
            $data = array('apikey' => $apiKey, 'numbers' => $receiver, "sender" => $sender, "message" => $message);
            
            $ch = curl_init($textLocalUrl);   
            curl_setopt($ch, CURLOPT_POST, true);
            //curl_setopt ($ch, CURLOPT_CAINFO, 'D:\ECOSYSTEM\PHP\extras\ssl'."/cacert.pem");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set timeout to 30 seconds
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response);  
            //return $response;
            $this->booking->where('id', $bookingId)->update(['cancel_otp' => $otp]);  
        
        }elseif($SmsGW=='IndiaHUB'){
                $IndiaHubApiKey = urlencode('0Z6jDmBiAE2YBcD9kD4hVg');              
        }
    }

    public function updateCancelTicket($bookingId,$userId,$refundAmt,$percentage,$pnr){
        $bookingCancelled = Config::get('constants.BOOKED_CANCELLED');
        $agentDetails =  AgentWallet::where('user_id',$userId)->orderBy('id','DESC')->where("status",1)->limit(1)->get(); //AgentWallet::where('user_id', $userId)->latest()->first();
        $agentDetails = $agentDetails[0];
   
        $transactionId = date('YmdHis') . gettimeofday()['usec'];
        $agetWallet = new AgentWallet();
        $agetWallet->transaction_id = $transactionId;
        $agetWallet->amount = $refundAmt;
        $agetWallet->type = 'Refund';
        $agetWallet->booking_id = $bookingId;
        $agetWallet->transaction_type = 'c';
        $agetWallet->balance = $agentDetails->balance + $refundAmt;
        $agetWallet->user_id = $userId;
        $agetWallet->created_by = $agentDetails->created_by;
        $agetWallet->status = 1;
        $agetWallet->save();

        $newBalance = $agentDetails->balance + $refundAmt;
        $notification = new Notification;
        $notification->notification_heading = "New Balance is Rs.$newBalance after recive of Refund amount of Rs.$refundAmt for PNR.$pnr";
        $notification->notification_details = "New Balance is Rs.$newBalance after recive of Refund amount of Rs.$refundAmt for PNR.$pnr";
        //$notification->notification_details = "New Balance is Rs.$newBalance after cancellation for Rs.$refundAmt";
        $notification->created_by = 'Agent';
        $notification->save();
       
        $userNotification = new UserNotification();
        $userNotification->user_id = $userId;
        $userNotification->created_by= "Agent"; 
        $notification->userNotification()->save($userNotification);
       
         $this->booking->where('id', $bookingId)->update(['status' => $bookingCancelled,'refund_amount' => $refundAmt, 'deduction_percent' => $percentage, 'cancel_otp' => null]);             
        
        //return $agetWallet;
    }

    public function refundPolicy($percentage,$razorpay_payment_id,$baseFare,$paidAmount){

        // $key = $this->credentials->first()->razorpay_key;
        // $secretKey = $this->credentials->first()->razorpay_secret;
        
        // $api = new Api($key, $secretKey);
        
        // $payment = $api->payment->fetch($razorpay_payment_id);
        // $paidAmount = $payment->amount;
         $paidAmount = $paidAmount * 100;
        //$refundAmount = $paidAmount * ((100-$percentage) / 100);
        $refundAmount = $baseFare * ((100-$percentage) / 100);
        $data = array(
            'refundAmount' => $refundAmount,
            'paidAmount' => $paidAmount,
        );
        return $data;
    }

    public function sendEmailTicket($totalfare,$discount,$payable_amount,$odbus_charges,$odbus_gst,$owner_fare,$request, $pnr,$cancellationslabs,$transactionFee,$customer_gst_status,$customer_gst_number,$customer_gst_business_name,$customer_gst_business_email,$customer_gst_business_address,$customer_gst_percent,$customer_gst_amount,$coupon_discount) {

        return  SendEmailTicketJob::dispatch($totalfare,$discount,$payable_amount,$odbus_charges,$odbus_gst,$owner_fare,$request, $pnr,$cancellationslabs,$transactionFee,$customer_gst_status,$customer_gst_number,$customer_gst_business_name,$customer_gst_business_email,$customer_gst_business_address,$customer_gst_percent,$customer_gst_amount,$coupon_discount);
       
        //return SendEmailTicketJob::dispatch($request, $pnr);
      }

//Created by Subhasis Mohanty on 2-09-2025 for ValueFirst and textlocal SMS Gateway Integration
        public function sendSmsTicket($data, $pnr)
{
   $SmsGW = config('services.sms.otpservice'); 
    if ($SmsGW === 'valuefirst') {
        return $this->sendSmsTicket_valueFirst($data, $pnr);
    } else if ($SmsGW === 'textLocal' ) {
        return $this->sendSmsTicket_textlocal($data, $pnr);
    }
}
//Created by Subhasis Mohanty on 25-06-2024 for ValueFirst SMS Gateway Integration
    //Function to send SMS using ValueFirst Service

public function sendSmsTicket_valueFirst($data, $pnr)
{
   
    $seatList = implode(",", $data['seat_no']);
    $nameList = "";
    $genderList = "";

    $passengerDetails = $data['passengerDetails'];
    foreach ($passengerDetails as $pDetail) {
        $nameList .= "," . $pDetail['passenger_name'];
        $genderList .= "," . $pDetail['passenger_gender'];
    }
    $nameList = ltrim($nameList, ',');
    $genderList = ltrim($genderList, ',');

    
    $busDetails = $data['busname'] . " " . $data['busNumber'];

    
    $doj = date('d-m-Y', strtotime($data['journeydate']));

    
    $message = "PNR: {$pnr}, Bus Details: {$busDetails}, DOJ: {$doj}, "
        . "Route: {$data['routedetails']}, Dep: {$data['departureTime']}, "
        . "Name: {$nameList}, Gender: {$genderList}, Seat: {$seatList}, "
        . "Fare: {$data['payable_amount']}, Conductor Mob: {$data['conductor_number']} - ODBUS.";

   
    $valueFirstService = new ValueFirstService();
    $response = $valueFirstService->sendSms($data['phone'], $message);

    return $response;
}






    public function sendSmsTicket_textlocal($data, $pnr) {

        $seatList = implode(",",$data['seat_no']);
        $nameList = "";
        $genderList = "";
        $passengerDetails = $data['passengerDetails'];
        foreach($passengerDetails as $pDetail){
            $nameList = "{$nameList},{$pDetail['passenger_name']}";
            $genderList = "{$genderList},{$pDetail['passenger_gender']}";
        } 
        $nameList = substr($nameList,1);
        $genderList = substr($genderList,1);
        $busDetails = $data['busname'].'-'.$data['busNumber'];
        $SmsGW = config('services.sms.otpservice');
        if($SmsGW == 'textLocal'){
            //Environment Variables
            //$apiKey = config('services.sms.textlocal.key');
            $apiKey = $this->credentials->first()->sms_textlocal_key;
            $textLocalUrl = config('services.sms.textlocal.url_send');
            $sender = config('services.sms.textlocal.senderid');
            $message = config('services.sms.textlocal.msgTicket');
            $apiKey = urlencode( $apiKey);
            $receiver = urlencode($data['phone']);
            //$message = str_replace("<PNR>",$data['PNR'],$message);
            $message = str_replace("<PNR>",$pnr,$message);
            $message = str_replace("<busdetails>",$busDetails,$message);
            $message = str_replace("<DOJ>",$data['journeydate'],$message);
            $message = str_replace("<routedetails>",$data['routedetails'],$message);
            $message = str_replace("<dep>",$data['departureTime'],$message);
            $message = str_replace("<name>",$nameList,$message);
            $message = str_replace("<gender>",$genderList,$message);
            $message = str_replace("<seat>",$seatList,$message);
            $message = str_replace("<fare>",$data['payable_amount'],$message);
            $message = str_replace("<conmob>",$data['conductor_number'],$message);
            //return $message;
            $message = rawurlencode($message);
            $response_type = "json"; 
            $data = array('apikey' => $apiKey, 'numbers' => $receiver, "sender" => $sender, "message" => $message);

            $ch = curl_init($textLocalUrl);   
            curl_setopt($ch, CURLOPT_POST, true);
            //curl_setopt ($ch, CURLOPT_CAINFO, 'D:\ECOSYSTEM\PHP\extras\ssl'."/cacert.pem");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set timeout to 30 seconds

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            
            curl_close($ch);
            $response = json_decode($response);
            
            return $response;
           // $msgId = $response->messages[0]->id;  // Store msg id in DB
            //session(['msgId'=> $msgId]);

            // $curlhttpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // $err = curl_error($ch);
            
            // if ($err) { 
            //     return "cURL Error #:" . $err;
            // } 

        }elseif($SmsGW=='IndiaHUB'){
                $IndiaHubApiKey = urlencode('0Z6jDmBiAE2YBcD9kD4hVg');
                $otp = $data['otp'];
                // $IndiaHubApiKey = urlencode( $IndiaHubApiKey);
                // //$channel = 'transactional';
                // //$route =  '4';
                // //$dcs = '0';
                // //$flashsms = '0';
                // $smsIndiaUrl = 'http://cloud.smsindiahub.in/vendorsms/pushsms.aspx';
                // $receiver = urlencode($data['phone']);
                // $sender_id = urlencode($data['sender']);
                // $name = $data['name'];
                // $message = $data['message'];
                // $message = str_replace("<otp>",$otp,$message);
                // $message = rawurlencode($message);
    
                // $api = "$smsIndiaUrl?APIKey=".$IndiaHubApiKey."&sid=".$sender_id."&msg=".$message."&msisdn=".$receiver."&fl=0&gwid=2";
    
                // $response = file_get_contents($api);
                //return $response;

        }
      }

     public function getPnrDetails($pnr){

       

        $data= $this->booking
                    ->where("pnr",$pnr)
                    ->where("status",1)
                    ->with('users')
                    ->get();

                  //  Log::Info($data);

                    return $data;

        // return $this->booking
        //             ->where("pnr",$pnr)
        //             ->where("status",1)
        //             ->with('users')
        //             ->with(["bus" => function($bs){
        //                     $bs->with('cancellationslabs.cancellationSlabInfo');
        //                     $bs->with('BusType.busClass');
        //                     $bs->with('BusSitting');                
        //                     $bs->with('busContacts');
        //                    }
        //                 ])
        //             ->with(["bookingDetail" => function($b){
        //                         $b->with(["busSeats" => function($s){
        //                             $s->with("seats");
        //                        }]);
        //                    }])
        //             ->get();
     } 
}