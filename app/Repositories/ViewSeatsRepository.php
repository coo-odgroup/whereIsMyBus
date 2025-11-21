<?php
namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\TicketPrice;
use App\Models\BoardingDroping;
use App\Models\Location;
use App\Models\BusSeats;
use App\Models\Seats;
use App\Models\BusStoppageTiming;
use App\Models\BusLocationSequence;
use App\Models\BookingSequence;
use App\Models\BookingDetail;
use App\Models\Booking;
use App\Models\TicketFareSlab;
use App\Models\OdbusCharges;
use App\Models\SeatOpenSeats;
use App\Models\BusScheduleDate;
use App\Models\BusSchedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use DateTime;

class ViewSeatsRepository
{
    protected $bus;
    protected $ticketPrice;
    protected $boardingDroping;
    protected $location;
    protected $busSeats;
    protected $busStoppageTiming;
    protected $busLocationSequence;
    protected $bookingSequence;
    protected $bookingDetail;
    protected $booking;
    protected $ticketFareSlab;
    protected $odbusCharges;
    protected $seats;

    public function __construct(Bus $bus,TicketPrice $ticketPrice,BoardingDroping $boardingDroping,Location $location,BusSeats $busSeats, Seats $seats, BusStoppageTiming $busStoppageTiming,BusLocationSequence $busLocationSequence,BookingSequence $bookingSequence,BookingDetail $bookingDetail,Booking $booking,TicketFareSlab $ticketFareSlab,OdbusCharges $odbusCharges)
    {
        $this->bus = $bus;
        $this->ticketPrice = $ticketPrice;
        $this->boardingDroping = $boardingDroping;
        $this->location = $location;
        $this->busSeats = $busSeats;
        $this->seats=$seats;
        $this->busStoppageTiming=$busStoppageTiming;
        $this->busLocationSequence=$busLocationSequence;
        $this->bookingSequence=$bookingSequence;
        $this->bookingDetail=$bookingDetail;
        $this->booking=$booking;
        $this->ticketFareSlab = $ticketFareSlab;
        $this->odbusCharges = $odbusCharges;  
    } 

    public function getLocationName($id)
     {
         return $this->location->where("id", $id)->where("status", 1)->get();
     }
    
    public function busLocationSequence($sourceId,$destinationId,$busId)
    {
        return $this->busLocationSequence->whereIn('location_id',[$sourceId,$destinationId])
        ->where('bus_id',$busId)
        ->where('status','!=', '2')
        ->pluck('sequence');
    }

    public function bookingIds($busId,$journeyDate,$booked,$seatHold,$sourceId,$destinationId){
        /////////////////////////////////////
        $busEntryPresent =  BusSchedule::where('bus_id', $busId)->where('status',1)
            ->with(['busScheduleDate' => function ($bsd) use ($journeyDate){
                $bsd->where('entry_date',$journeyDate);
                $bsd->where('status','1');
            }])
            ->get();

        ////////////////////////////////////
        $JdayDetails =  $this->ticketPrice->select('start_j_days','j_day')
            ->where('bus_id', $busId)
            ->where('source_id',$sourceId)
            ->where('destination_id',$destinationId)
            ->where('status',1)
            ->first();

            $startJDay =  $JdayDetails->start_j_days;
            $JDay =  $JdayDetails->j_day;
            
        switch($startJDay){  
            case(1):          //// Bus Starting on Day-1        
                $nday = date('Y-m-d', strtotime('+1 day', strtotime($journeyDate))); 
                If($JDay==2){
                    if(isset($busEntryPresent[0]) && $busEntryPresent[0]->busScheduleDate->isNotEmpty()){  
                        $bookingIds =  $this->booking->where('bus_id',$busId)
                            ->where('journey_dt',$journeyDate)
                            ->whereIn('status',[$booked,$seatHold])
                            ->pluck('id');
                    }else{
               
                        $bookingIds = $this->booking->where('bus_id',$busId)
                            ->whereIn('journey_dt',[$nday,$journeyDate])
                            ->whereIn('status',[$booked,$seatHold])
                            ->pluck('id');
                    }
                }else{
                    $bookingIds =  $this->booking->where('bus_id',$busId)
                        ->where('journey_dt',$journeyDate)
                        ->whereIn('status',[$booked,$seatHold])
                        ->pluck('id');
                }
                return  $bookingIds;
                break;
            case(2):           //// Bus Starting on Day-2
                $yday = date('Y-m-d', strtotime('-1 day', strtotime($journeyDate)));
                return $this->booking->where('bus_id',$busId)
                    ->whereIn('journey_dt',[$yday,$journeyDate])
                    ->whereIn('status',[$booked,$seatHold])
                    ->pluck('id');
                break;
            case(3):        //// Bus Starting on Day-3
                $yday = date('Y-m-d', strtotime('-1 day', strtotime($journeyDate)));
                $db4yday = date('Y-m-d', strtotime('-1 day', strtotime($yday)));
                return $this->booking->where('bus_id',$busId)
                    ->whereIn('journey_dt',[$db4yday,$yday,$journeyDate])
                    ->whereIn('status',[$booked,$seatHold])
                    ->pluck('id');
                break;
        }
    }

    public function bookingDetail($bookingId)
    {
        return $this->bookingDetail->where('booking_id',$bookingId)
        ->pluck('bus_seats_id');
    }

    public function busSeats($bookedSeatId){
        return $this->busSeats->where('id',$bookedSeatId)->first()->seats_id;
    }

    public function bookingGenderDetail($bookingId,$bookedSeatId){
        return  $this->bookingDetail->where('booking_id',$bookingId)
        ->where('bus_seats_id',$bookedSeatId)
        ->first()->passenger_gender; 
    }

    public function getSourceId($bookingId){
        return $this->booking->where('id',$bookingId)->first()->source_id;
    }

    public function getDestinationId($bookingId){
        return $this->booking->where('id',$bookingId)->first()->destination_id;
    }

    public function bookedSequence($srcId,$destId,$busId){
        return $this->busLocationSequence->whereIn('location_id',[$srcId,$destId])
        ->where('bus_id',$busId)
        ->where('status','!=', '2')
        ->orderBy('id')
        ->pluck('sequence');
    }

    public function busRecord($busId){
        return $this->bus->where('id',$busId)->get(['id','name','bus_seat_layout_id']);
    }

    public function getBerth($bus_seat_layout_id,$Berth,$busId,$bookedSeatIDs,$entry_date,$sourceId,$destinationId){
        $ticketPriceId = TicketPrice::where('bus_id',$busId)
                                    ->where('source_id',$sourceId)
                                    ->where('destination_id',$destinationId)
                                    ->where('status',1)
                                    ->first()->id;
                                 
///////////////Extra seats///////////////

        $depTime = TicketPrice::where('bus_id',$busId)
                                ->where('source_id',$sourceId)
                                ->where('destination_id',$destinationId)
                                ->where('status',1)
                                ->first()->dep_time;  
       
        $extraSeats = BusSeats::where('bus_id',$busId)
                                ->where('status',1)
                                ->where('ticket_price_id',$ticketPriceId)
                                ->where('duration','>',0)
                                ->get(['seats_id','duration']);
        
        $extraSeatsBlock = BusSeats::where('bus_id',$busId)
                                    ->where('status',1)
                                    ->where('ticket_price_id',$ticketPriceId)
                                    ->where('duration','=',0)
                                    ->where('operation_date',$entry_date)
                                    ->where('type',null)
                                    ->get('seats_id');
         ///Seats blocked prior to journey date////////                           
        $oldExtraSeatsBlock = BusSeats::where('bus_id',$busId)
                                    ->where('status',1)
                                    ->where('ticket_price_id',$ticketPriceId)
                                    ->where('duration','=',0)
                                    ->where('operation_date','<' ,$entry_date)
                                    ->where('type',null)
                                    ->pluck('seats_id');                        
       
        //$CurrentDateTime = "2022-01-05 16:48:35";
        $depTime = date("H:i:s", strtotime($depTime));
        $CurrentDateTime = Carbon::now();//->toDateTimeString();
        $depDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $entry_date.' '.$depTime);

        if($depDateTime>=$CurrentDateTime){
            $diff_in_minutes = $depDateTime->diffInMinutes($CurrentDateTime);
        }else{
            $diff_in_minutes = 0;
        }
   
       $blockSeats = BusSeats::where('operation_date', $entry_date)
            ->where('type',2)
            ->where('bus_id',$busId)
            ->where('status',1)
            ->where('ticket_price_id',$ticketPriceId)
            ->pluck('seats_id');
            
        ////////////////////////seat open on specific date//////////////////////
        $seatsOpenOnDate = BusSeats::where('operation_date', $entry_date)
                                ->where('type',1)
                                ->where('bus_id',$busId)
                                ->where('status',1)
                                ->where('ticket_price_id',$ticketPriceId)
                                ->pluck('seats_id');

       $openSeatsHide = BusSeats::where('operation_date','!=', $entry_date)
            ->where('type',1)
            ->where('bus_id',$busId)
            ->where('status',1)
            ->where('ticket_price_id',$ticketPriceId)
            ->pluck('seats_id');
           
        if(isset($seatsOpenOnDate) && $seatsOpenOnDate->isNotEmpty()){
            $openSeatsHide = collect($openSeatsHide)->diff(collect($seatsOpenOnDate));
        }
        $moreAddedSeats = BusSeats::whereNull('operation_date')
            ->whereNull('type')
            ->where('bus_id',$busId)
            ->whereIn('seats_id',$openSeatsHide)
            ->where('status',1)
            ->where('ticket_price_id',$ticketPriceId)
            ->pluck('seats_id');
            $seatsHide = [];
            if(isset($moreAddedSeats) && $moreAddedSeats->isNotEmpty()){
                $seatsHide = collect($openSeatsHide)->diff(collect($moreAddedSeats));
            }else{
                $seatsHide = $openSeatsHide;
            }
///////////////////////////////////////////////////////////////////
        $blockSeatsOnAllDates = BusSeats::where('type',2)
                                        ->where('bus_id',$busId)
                                        ->where('status',1)
                                        ->where('ticket_price_id',$ticketPriceId)
                                        ->pluck('seats_id');   

        $permanentSeats = BusSeats::whereNull('operation_date')
                                ->where('ticket_price_id',$ticketPriceId)
                                ->where('bus_id',$busId)
                                ->where('status',1)
                                ->pluck('seats_id'); 
                       
                       
        $noMoreavailableSeats = collect($blockSeatsOnAllDates)->diff(collect($permanentSeats));                   

        ////////////////////All available seats not booked////////////////////////////////////
        $availableSeats = $this->seats
            ->where('bus_seat_layout_id',$bus_seat_layout_id)
            ->where('berthType', $Berth)
            ->where('status','1')
            //->with("busSeats")->get();
            ->with(["busSeats"=> function ($query) use ($busId,$bookedSeatIDs,$entry_date,              $ticketPriceId){
                    $query->where('status',1)
                            ->where('bus_id',$busId)
                            ->where('ticket_price_id',$ticketPriceId)
                            ->whereNotIn('seats_id',$bookedSeatIDs)
                            ->select('ticket_price_id','seats_id','new_fare');
            }]) 
            ->get();

        
        $totalHideSeats = collect($blockSeats)->concat(collect($seatsHide))->concat(collect($bookedSeatIDs))->concat(collect($noMoreavailableSeats));   
        

        /////////////Check existence of Extra seat closed not in  Permanet seat list/////////
        $oldExtraSeatsBlock = collect($oldExtraSeatsBlock)->diff(collect($permanentSeats));
        if(!$oldExtraSeatsBlock->isEmpty()){ 
            $totalHideSeats = $totalHideSeats->concat(collect($oldExtraSeatsBlock));   
        } 
      
        /////////Hide Extra Seats based on seize time/////////

        if(!$extraSeats->isEmpty()){
            $extraSeatsHide = collect($extraSeats)->pluck('seats_id');
            $seizedTime = $extraSeats[0]->duration;
            if(!$extraSeatsHide->isEmpty() && $seizedTime > $diff_in_minutes){
                $totalHideSeats = $totalHideSeats->concat(collect($extraSeatsHide));
            }
        }

        /////////////Blocked Extra Seats on specific date///////////
        if(!$extraSeatsBlock->isEmpty()){
            $eBlockSeats = collect($extraSeatsBlock)->pluck('seats_id');
            $totalHideSeats = $totalHideSeats->concat(collect($eBlockSeats));
        }

        if(!$seatsOpenOnDate->isEmpty()){
            $totalHideSeats = collect($totalHideSeats)->diff(collect($seatsOpenOnDate));
        }

        foreach($availableSeats as $seat){ 
            if($totalHideSeats->contains($seat->id)){
                unset($seat['busSeats']); 
            }
        }
        return $availableSeats;
    }

    public function seatRowColumn($bus_seat_layout_id,$Berth){
        return $this->seats
        ->where('bus_seat_layout_id',$bus_seat_layout_id)
        ->where('status','1') 
        ->where('berthType', $Berth);
    }

    
    public function busWithTicketPrice($sourceId,$destinationId,$busId){
        return  $this->ticketPrice
        ->where('source_id', $sourceId)
        ->where('destination_id', $destinationId)
        ->where('bus_id', $busId)
        ->where('status','1') 
        ->select('id','base_seat_fare','base_sleeper_fare')
        ->first();
    }


    public function newFare($seat_ids,$busId,$ticket_price_id){

        return  DB::table('bus_seats as b')->leftJoin('seats as s','b.seats_id','=','s.id')
        ->whereIn('b.seats_id', $seat_ids)
        ->where('b.bus_id', $busId)
        ->where('b.ticket_price_id', $ticket_price_id)
        ->where('b.status','1') 
        ->select('b.id','b.seats_id','b.new_fare','b.ticket_price_id','b.type','b.duration','b.operation_date','s.seat_class_id','s.berthType') 
        ->get();

    }

    public function ticketFareSlab($user_id){
    $defUserId = Config::get('constants.USER_ID'); 
        
    $ticketFareRecord = $this->ticketFareSlab->where('user_id', $user_id)->get();
        if(isset($ticketFareRecord[0])){
            return $ticketFareRecord;
        }else{
            return $this->ticketFareSlab->where('user_id', $defUserId)->get();
        }  
    }

    public function odbusCharges($user_id){
        $defUserId = Config::get('constants.USER_ID');

        $odbusChargesRec = $this->odbusCharges->where('user_id', $user_id)->get();
        if(isset($odbusChargesRec[0])){
            return $this->odbusCharges->where('user_id', $user_id)->get();
        }else{
            return $this->odbusCharges->where('user_id', $defUserId)->get();
        }  

    }
 
    public function busStoppageTiming($busId){
      return  $this->busStoppageTiming
        ->with(['boardingDroping' => function ($a){
            $a->where('status',1);
            }])  
        ->where('bus_id', $busId)
        ->where('status', '1')
        ->orderBy('stoppage_time', 'ASC')
        ->groupBy('boarding_droping_id')
        ->get();
    }
    public function miscFares($busId,$entry_date){

        //////////////////////special Fare calculations/////////////////////
        $bus = Bus::find($busId);	
        $specialFares = $bus->specialFare()->where('date', $entry_date)->where('status',1)->get();
        
        $splSeaterFare=0;
        $splSleeperFare =0;
        if(count( $specialFares) > 0){
            $splSeaterFare = (int)$specialFares[0]->seater_price;
            $splSleeperFare = (int)$specialFares[0]->sleeper_price;
        }    
        ///////////////////////owner Fare calculations///////////////////////
        $ownerFares = $bus->ownerfare()->where('date', $entry_date)->where('status',1)->get();
        $ownSeaterFare=0;
        $ownSleeperFare =0;
        if(count( $ownerFares) > 0){
            $ownSeaterFare = (int)$ownerFares[0]->seater_price;
            $ownSleeperFare = (int)$ownerFares[0]->sleeper_price;
        }  
        ///////////////////////Festive Fare calculations///////////////////////
        $festiveFares = $bus->festiveFare()->where('date', $entry_date)->where('status',1)->get();
        $festiveSeaterFare=0;
        $festiveSleeperFare =0;
        if(count( $festiveFares) > 0){
            $festiveSeaterFare = (int)$festiveFares[0]->seater_price;
            $festiveSleeperFare = (int)$festiveFares[0]->sleeper_price;
        }
        return [$splSeaterFare,$splSleeperFare,$ownSeaterFare,$ownSleeperFare,$festiveSeaterFare,$festiveSleeperFare];  
    }
}