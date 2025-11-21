<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Location;
use App\Models\Users;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use App\Transformers\DolphinTransformer;
use App\Transformers\MantisTransformer;


class BookTicketRepository
{
    protected $bus;
    protected $ticketPrice;
    protected $location;
    protected $users;
    protected $booking;
    protected $busSeats;
    protected $busLocationSequence;
    protected $channelRepository; 
    protected $dolphinTransformer;


    public function __construct(Bus $bus,TicketPrice $ticketPrice,Location $location,Users $users,BusSeats $busSeats,Booking $booking,BusLocationSequence $busLocationSequence,ChannelRepository $channelRepository,DolphinTransformer $dolphinTransformer,MantisTransformer $mantisTransformer)
    {
        $this->bus = $bus;
        $this->ticketPrice = $ticketPrice;
        $this->location = $location;
        $this->users = $users;
        $this->busSeats = $busSeats;
        $this->booking = $booking;
        $this->channelRepository = $channelRepository;
        $this->busLocationSequence = $busLocationSequence;    
        $this->dolphinTransformer = $dolphinTransformer;
        $this->mantisTransformer = $mantisTransformer;
    } 

    public function CheckExistingUser($phone){
        return $this->users->where('phone',$phone)
        //->orWhere('email', $customerInfo['email'])
        ->exists(); 
    }
    public function GetUserId($phone){
      return $this->users->where('phone',$phone)
      //->orWhere('email',$customerInfo['email'])
      ->first('id');
    }
  
    public function UpdateInfo($userId,$customerInfo){
      return $this->users->whereIn('id', $userId)->update($customerInfo);  
    }
  
    public function CreateUser($customerInfo){
      return $this->users->create($customerInfo)->latest('id')->first('id');   ;  
    }

    public function SaveBooking($bookingInfo,$userId,$needGstBill,$priceDetails,$clientRole,$clientId){
     
        $defUserId = Config::get('constants.USER_ID'); 

	    $bookingInfo['origin'] = (isset($bookingInfo['origin'])) ? $bookingInfo['origin']: 'ODBUS';
        
        $booking = new $this->booking;
        do {
           $transactionId = date('YmdHis') . gettimeofday()['usec'];
           } while ( $booking->where('transaction_id', $transactionId )->exists());
        $booking->transaction_id =  $transactionId;
      
        do {
          switch($bookingInfo['app_type'])
          {
            case("WEB"):
                $PNR = 'ODW'.rand(1000000,9999999);
                break;
            case("MOB"):
                $PNR = 'ODM'.rand(1000000,9999999);
                break;
            case("ANDROID"):
                $PNR = 'ODA'."".rand(1000000,9999999);
                break;
          }
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
        $booking->boarding_time = date('H:i',strtotime($bookingInfo['boarding_time']));
        $booking->dropping_time =  date('H:i',strtotime($bookingInfo['dropping_time']));
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
        //$booking->owner_fare = $bookingInfo['owner_fare'];
        //$booking->total_fare = $bookingInfo['total_fare'];
        //$booking->additional_special_fare = $bookingInfo['specialFare'];
        //$booking->additional_owner_fare = $bookingInfo['addOwnerFare'];
        //$booking->additional_festival_fare = $bookingInfo['festiveFare'];
        //$booking->odbus_Charges = $bookingInfo['odbus_service_Charges'];
        //$booking->transactionFee = $bookingInfo['transactionFee'];

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

        $user_id = Bus::where('id', $busId)->first()->user_id;
        $busOperatorId = Bus::where('id', $busId)->first()->bus_operator_id;
        
        $odbusChargesRecord = OdbusCharges::where('user_id',$user_id)->get();
        if(isset($odbusChargesRecord[0])){
            $odbusGstPercent = OdbusCharges::where('user_id',$user_id)->first()->odbus_gst_charges;
        }else{
            $odbusGstPercent = OdbusCharges::where('user_id',$defUserId)->first()->odbus_gst_charges;
        }
        
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

        $booking->CompanyID = (isset($bookingInfo['CompanyID'])) ? $bookingInfo['CompanyID']: '';
        $booking->ReferenceNumber =(isset($bookingInfo['ReferenceNumber'])) ? $bookingInfo['ReferenceNumber']: '';
        $booking->RouteTimeID = (isset($bookingInfo['RouteTimeID'])) ? $bookingInfo['RouteTimeID'] : '';
        $booking->PickupID =(isset($bookingInfo['PickupID'])) ? $bookingInfo['PickupID'] : '';
        $booking->DropID = (isset($bookingInfo['DropID'])) ? $bookingInfo['DropID'] : '';

        $booking->created_by = $bookingInfo['created_by'];
        $userId->booking()->save($booking);

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
        $bookingDetail = $bookingInfo['bookingDetail'];
        $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id');  //in request passing seats_id with key as bus_seats_id
        foreach ($seatIds as $seatId){
            $busSeatsId[] = $this->busSeats
                ->where('bus_id',$busId)
                ->where('ticket_price_id',$ticketPriceId)
                ->where('seats_id',$seatId)
                ->where('status','1')
                ->first()->id;
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
                $merged = ($collection->merge(['bus_seats_id' => $busSeatsId[$i]]))->toArray();
                $bookingDetailModels[] = new BookingDetail($merged);

            }elseif($bookingInfo['origin'] == 'DOLPHIN'){ // dolphin related changes{
                // get real seat name from dolphin transformer
                $ReferenceNumber=$bookingInfo['ReferenceNumber'];
                //Log::info($ReferenceNumber.",".$bDetail['bus_seats_id'].",".$clientRole,$clientId);
                $seat_name= $this->dolphinTransformer->GetseatLayoutName($ReferenceNumber,$bDetail['bus_seats_id'],$clientRole,$clientId);
                //Log::info($seat_name);
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

   
}