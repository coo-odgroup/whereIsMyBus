<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\TicketPrice;
use App\Models\Booking;
use App\Models\Location;
use App\Models\BusOperator;
use App\Models\Amenities;
use App\Models\BusAmenities;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use App\Services\ValueFirstService;
use Carbon\Carbon;
use App\Models\Credentials;


class PopularRepository
{
    protected $bus;
    protected $ticketPrice;
    protected $location;
   protected $review;
   protected $credentials;
   protected  $booking;
  

    public function __construct(Bus $bus,TicketPrice $ticketPrice,Booking $booking,Location $location,Review $review,Credentials $credentials)
    {
        $this->bus = $bus;
        $this->ticketPrice = $ticketPrice;
        $this->booking = $booking;
        $this->location = $location;
        $this->review = $review;
        $this->credentials = $credentials;
    } 
    
    public function getRoutes(){
       return  $this->booking
        ->select('source_id','destination_id',(DB::raw('count(*) as count')))
        ->whereDate('created_at', '>', Carbon::now()->subDays(30))
        ->groupBy('source_id', 'destination_id')
        ->orderBy('count', 'DESC')
        ->limit(28)
        ->get();
    }
 
    public function getRouteNames($sourceId){ 
        $sourceName = $this->location->where('id',$sourceId)->first()->name;
        return $sourceName;
    }

    public function getRoute($sourceId){ 
        return $this->location->select('id','state_id','name','url','synonym')->where('id',$sourceId)->get();
    }

    public function getDolphinRoute($id){ 
        return $this->location->select('id','state_id','name','url','synonym')->where('dolphin_id',$id)->get();
    }

    

    public function getBusIds(){
        return  $this->booking
        ->select('bus_id',(DB::raw('count(*) as count')))
        ->whereDate('created_at', '>', Carbon::now()->subDays(30))
        ->groupBy('bus_id')
        ->orderBy('count', 'DESC')
        ->get();
    }

    public function getOperator($bus_id){ 
       return $this->bus
        ->with('busOperator')
        ->where('id',$bus_id)->get();
        
    }

    public function getAllRoutes(){ 

        return $this->ticketPrice
        ->select('bus_id','source_id','destination_id',(DB::raw('count(*) as count')))
        ->with(['bus' => function($bs){
            $bs->with('busSchedule');
            }])
        ->where("status",1)
        ->groupBy('source_id', 'destination_id')
        ->orderBy('count', 'DESC')
        ->get();
       
    }

    public function getBus($sid,$did){ 

        return DB::select("SELECT b.id,b.name,b.bus_number from ticket_price t left join bus b on t.bus_id=b.id where t.status=1 AND t.source_id=$sid AND t.destination_id=$did");

        // return $this->ticketPrice->with('bus')
        // ->where([
        //     ['source_id', '=', $sid],
        //     ['destination_id', '=', $did],
        // ])
        // ->where("status",1)
        // ->get();
       
    }


    public function sendSmsApp_valueFirst($phone)
        {
            
            $message = "Upgrade to the NEW ODBUS App! Quick bookings & secure payments await you. Install today https://tinyurl.com/ODBUS - Team ODBUS";
            $valueFirstService = new ValueFirstService();
            $response = $valueFirstService->sendSms($phone, $message);
            return $response;
        }

    public function downloadApp($phone)
        {
            
             $SmsGW = config('services.sms.otpservice'); 
            if ($SmsGW === 'valuefirst') {
                return $this->sendSmsApp_valueFirst($phone);
            } else if ($SmsGW === 'textLocal' ) {
                return $this->sendSmsApp_textLocal($phone);
            }
        }
    

    public function sendSmsApp_textLocal($phone){  

        $SmsGW = config('services.sms.otpservice');

        if($SmsGW =='textLocal'){

            //Environment Variables
            //$apiKey = config('services.sms.textlocal.key');
            $apiKey = $this->credentials->first()->sms_textlocal_key;
            $textLocalUrl = config('services.sms.textlocal.url_send');
            $sender = config('services.sms.textlocal.senderid');
            $message = config('services.sms.textlocal.appDownload');
            $apiKey = urlencode( $apiKey);
            $receiver = urlencode($phone);
          
            $message = str_replace("<LINK>",'https://tinyurl.com/ODBUS',$message);
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
            $msgId = $response->messages[0]->id;  // Store msg id in DB
            session(['msgId'=> $msgId]);

         

        }elseif($SmsGW=='IndiaHUB'){
                $IndiaHubApiKey = urlencode('0Z6jDmBiAE2YBcD9kD4hVg');              

        }

    }
    

    public function allOperators($filter){  

        if($filter!=''){
            $operators = BusOperator::where('organisation_name','LIKE', $filter.'%')->where('status',1)
            ->select(['id','operator_name','organisation_name','operator_url','operator_info']);

        }else{
            $operators = BusOperator::where('status',1)->select(['id','operator_name','organisation_name','operator_url','operator_info']);

        }

       
        return $operators;

    }

    public function GetOperatorDetail($operator_url){
        return BusOperator::where('operator_url', $operator_url)->with(['bus' => function ($q){ 
            $q->select('bus_operator_id','id','name');
            // $q->with(['busAmenities' => function ($query) {
            //      $query->select('bus_id','amenities_id');
            //          $query->with(['amenities' =>  function ($a){
            //              $a->select('id','name','icon');
            //          }]);
            //      }]);         
              }])   
            ->with('ticketPrice:bus_operator_id,source_id,destination_id') 
            ->get();
    }

    public function GetOperatorReviews($busIds){
        return $this->review->whereIn('bus_id',$busIds)
        ->where('status',1)
        ->with(['users' =>  function ($u){
         $u->select('id','name','district','profile_image');
        }])->get();
    }

    public function Totalrating($busIds){
        return $this->review->whereIn('bus_id',$busIds)                            
                            ->where('status',1)
                            ->get()->avg('rating_overall');
    }

    public function GetRouteBookings($busIds){
        return Booking::whereIn('bus_id', $busIds)
        ->select('source_id','destination_id',(DB::raw('count(*) as count')))
        ->whereDate('created_at', '>', Carbon::now()->subDays(30))
        ->groupBy('source_id','destination_id')
        ->orderBy('count', 'DESC')
        ->get();      
    }

    public function GetDepartureTime($source_id,$destination_id,$busIds){
        return TicketPrice::where('source_id',$source_id) 
        ->where('destination_id',$destination_id)
        ->whereIn('bus_id', $busIds) 
        ->orderBy('dep_time', 'ASC')  
        ->first()->dep_time;
    }

    public function GetAllBusAmenities($busIds){
        
      return BusAmenities::whereIn('bus_id',$busIds)
                            ->with(['amenities'  => function ($query) {
                                $query->select('id','name','amenities_image');
                            }])  
                            ->groupBy('amenities_id')                          
                            ->get();

    }


}