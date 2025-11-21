<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Location;
use App\Models\BusOperator;
use App\Models\BoardingDroping;
use App\Models\BusStoppageTiming;
use App\Models\BusType;
use App\Models\BusClass;
use App\Models\SeatClass;
use App\Models\Amenities;
use App\Models\BusAmenities;
use App\Models\BusSeats;
use App\Models\Seats;
use App\Models\CancellationSlab;
use App\Models\CancellationSlabInfo;
use App\Models\BusContacts;
use App\Models\TicketPrice;
use App\Models\BusScheduleDate;
use App\Models\Booking;
use App\Models\BusSchedule;
use App\Models\CouponRoute;
use App\Models\Coupon;
use App\Models\AssocAssignBus;
use App\Models\BookingSequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Repositories\CommonRepository;
use App\Models\BusLocationSequence;
use App\Repositories\ViewSeatsRepository;
use App\Models\BookingDetail;
use Illuminate\Support\Str;
use App\Services\DolphinService;
use App\Services\MantisService;


use DateTime;
use Time;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Config;

class ListingRepository
{
    protected $bus;
    protected $location;
    protected $busOperator;
    protected $busStoppageTiming;
    protected $busType;
    protected $busClass;
    protected $seatClass;
    protected $amenities;
    protected $boardingDroping;
    protected $busSeats;
    protected $ticketPrice;
    protected $busScheduledate;
    protected $busSchedule;
    protected $booking;
    protected $commonRepository;
    protected $viewSeatsRepository;
    protected $busLocationSequence;
    protected $dolphinService;
    protected $mantisService;
    

    public function __construct(Bus $bus,Location $location,BusOperator $busOperator,BusStoppageTiming $busStoppageTiming,BusType $busType,Amenities $amenities,BoardingDroping $boardingDroping,BusClass $busClass,SeatClass $seatClass,BusSeats $busSeats,TicketPrice $ticketPrice,BusScheduleDate $busScheduleDate,BusSchedule $busSchedule, Booking $booking,CommonRepository $commonRepository, ViewSeatsRepository $viewSeatsRepository, BusLocationSequence $busLocationSequence, DolphinService $dolphinService,MantisService $mantisService)
    {
        $this->bus = $bus;
        $this->location = $location;
        $this->busOperator = $busOperator;
        $this->busStoppageTiming = $busStoppageTiming;
        $this->busType = $busType;
        $this->amenities = $amenities;
        $this->boardingDroping = $boardingDroping;
        $this->busClass = $busClass;
        $this->seatClass = $seatClass; 
        $this->ticketPrice = $ticketPrice;
        $this->busScheduleDate = $busScheduleDate;
        $this->busSchedule = $busSchedule;
        $this->booking=$booking;
        $this->commonRepository = $commonRepository;
        $this->viewSeatsRepository = $viewSeatsRepository;
        $this->busLocationSequence=$busLocationSequence;
        $this->dolphinService=$dolphinService;
        $this->mantisService = $mantisService;
     }   

     public function getLocation($searchValue)
     {        
        //  return $this->location
        //  ->where('name', 'like', '%' .$searchValue . '%')
        //  ->where('status','1')  
        //  ->orWhere('synonym', 'like', '%' .$searchValue . '%')
        //  ->orderBy('name','ASC')
        //  ->where('status','1')  
        //  ->get(['id','name','synonym','url']);

       // DB::connection()->enableQueryLog();

         $list=  DB::table('location as l')
            ->leftJoin('state as s', 'l.state_id', '=', 's.id')
            ->where('l.status','1')  
            ->Where(function ($query) use ($searchValue) {
                $query->where('l.name', 'like', '%' .$searchValue . '%')
                      ->orWhere('l.synonym', 'like', '%' .$searchValue . '%');
            })            
            ->orderBy('l.name','ASC')            
            ->get(['l.id','l.name','l.synonym','l.url','l.state_id','s.state_name']);

            //$queries = DB::getQueryLog();
           // Log::info($queries);
            return $list;
     }


     public function getLocationID($name)
     {
         return $this->location->where("name", $name)->where("status", 1)->get();
     }

     public function getLocationResult($id)
     {
         return $this->location->where("id", $id)->where("status", 1)->get();
     }

     public function getBusCoupon($busId)
     {
         return Coupon::where('bus_id', $busId)
                        ->where('status','1')
                        ->get();
     }
     public function getrouteCoupon($sourceID,$destinationID,$busId,$entry_date)
     {
         return Coupon::where('source_id', $sourceID)
                        ->where('destination_id', $destinationID)
                        ->where('coupon_type_id', 2)
                        ->where('status',1)
                        ->where('from_date', '<=', $entry_date)
                        ->where('to_date', '>=', $entry_date)
                        ->where('bus_id', $busId)
                        ->get();
     }
     public function getOperatorCoupon($busOperatorId,$busId,$entry_date)
     {
         return Coupon::where('bus_operator_id', $busOperatorId) ////Operator wise coupon
                        ->where('coupon_type_id', 1)
                        ->where('status', 1)
                        ->where('from_date', '<=', $entry_date)
                        ->where('to_date', '>=', $entry_date)
                        ->where('bus_id', $busId)
                        ->get();
     }
     public function getOpRouteCoupon($busOperatorId,$sourceID,$destinationID,$busId,$entry_date)
     {
         return Coupon::where('bus_operator_id', $busOperatorId) ////OperatorRoute wise coupon
                        ->where('coupon_type_id', 3)
                        ->where('source_id', $sourceID)
                        ->where('destination_id', $destinationID)
                        ->where('status', 1)
                        ->where('from_date', '<=', $entry_date)
                        ->where('to_date', '>=', $entry_date)
                        ->where('bus_id', $busId)
                        ->get();
     }

     public function getAllCoupon()
     {
         return Coupon::where('status','1')->get();
     }

     public function getticketPrice($sourceID,$destinationID,$busOperatorId,$journey_date, $userId)
     {
        if($userId != null || isset($userId)){
            $busIds = AssocAssignBus::where('user_id',$userId)->pluck('bus_id');
            return $this->ticketPrice
                        ->where('source_id', $sourceID)
                        ->where('destination_id', $destinationID)
                        ->whereIn('bus_id',$busIds)
                        ->where('status','1') 
                        ->get(['id','bus_id','bus_operator_id','start_j_days','seize_booking_minute','dep_time']);  
        }else{
            return $this->ticketPrice
                        ->where('source_id', $sourceID)
                        ->where('destination_id', $destinationID)
                        ->where('status','1')
                        ->get(['id','bus_id','bus_operator_id','start_j_days','seize_booking_minute','dep_time']); 
        }
        
        // return $this->ticketPrice
        // ->where('source_id', $sourceID)
        // ->where('destination_id', $destinationID)
        // ->where('status','1')
        // ->when($userId != null || isset($userId), function ($query) use ($userId){
        //     $query->where('user_id',$userId);
        //     })
        // ->get(['id','bus_id','bus_operator_id','start_j_days','seize_booking_minute','dep_time']);  
     }

     public function checkBusentry($busId,$new_date)
     {
       return $this->busSchedule->where('bus_id', $busId)->where('status',1)
                                 ->with(['busScheduleDate' => function ($bsd) use ($new_date){
                                     $bsd->where('entry_date',$new_date);
                                     $bsd->where('status','1');
                                 }])->get();
     }

     public function getBusScheduleID($busId)
     {
       return $this->busSchedule->whereIn('bus_id', (array)$busId)->where('status','1')->pluck('id'); 
     }

     public function getBusData($busOperatorId,$busId,$userId,$entry_date)
     {  
        return $this->bus
        // ->when($userId != null || isset($userId), function ($query) use ($userId){
        //     $query->where('user_id',$userId);
        //     })
        ->with('busContacts')       
        ->with(['busAmenities'  => function ($query) {
            $query->with(['amenities' =>function ($a){
                $a->where('status',1);
                $a->select('id','name','amenities_image','android_image');
            }]);
        }]) 
        ->with(['busSafety'  => function ($query) {
            $query->with(['safety' =>function ($a){
                $a->where('status',1);
                $a->select('id','name','safety_image','android_image');
            }]);
        }]) 
        ->with('BusType.busClass')
        ->with(['busSeats' => function ($bs) use ($entry_date) {
                $bs->where('status',1)
                   ->with(['seats' => function ($s) {
                        $s->where('status',1);
                }]);       
            }])
        ->with('BusSitting')
        ->with(['busGallery' => function ($a){
            $a->where('status',1);
            }])
        ->with('cancellationslabs.cancellationSlabInfo')
        ->with(['review' => function ($query) {                    
            $query->where('status',1);
            $query->select('bus_id','users_id','title','rating_overall','rating_comfort','rating_clean','rating_behavior','rating_timing','comments');  
            $query->with(['users' => function ($u){
                $u->select('id','name','profile_image');
            }]); 
            $query->orderBy('id','DESC');                     
            }])
        ->where('status','1')
        ->where('id',$busId)
        ->get();
     }

     public function getFilterBusList($busOperatorId,$busId,$busType,
     $seatType,$boardingPointId,$dropingingPointId,$operatorId,$amenityId,$userId,$entry_date){

        return $this->bus
                ->when($seatType != null && !empty($seatType), function ($query) use ($seatType){
                    $query->whereIn('type',$seatType);
                    })        
         ->with(['busAmenities'  => function ($query) {
            $query->with(['amenities' =>function ($a){
                $a->where('status',1);
                $a->select('id','name','amenities_image','android_image');
            }]);
        }]) 
        ->with(['busSafety'  => function ($query) {
            $query->with(['safety' =>function ($a){
                $a->where('status',1);
                $a->select('id','name','safety_image','android_image');
            }]);
        }]) 
         ->with('BusType.busClass')
        ->with(['busSeats' => function ($bs) use ($entry_date) {
            $bs->where('status',1)
               ->with(['seats' => function ($s) {
                        $s->where('status',1);
                   }]);   
            }])
         ->with('BusSitting')
         ->with(['busGallery' => function ($a){
            $a->where('status',1);
            }])
         ->with('cancellationslabs.cancellationSlabInfo')
         ->with(['review' => function ($query) {                    
             $query->where('status',1);
             $query->select('bus_id','users_id','title','rating_overall','rating_comfort','rating_clean','rating_behavior','rating_timing','comments');  
             $query->with(['users' =>  function ($u){
                 $u->select('id','name','profile_image');
             }]);                      
             }])
         ->whereHas('busType.busClass', function ($query) use ($busType){
             if($busType)
             $query->whereIn('id', (array)$busType);            
             })
         ->whereHas('busStoppageTiming.boardingDroping', function ($query) use ($boardingPointId){  
             if($boardingPointId)                   
             $query->whereIn('id', (array)$boardingPointId);
             })    
         ->whereHas('busStoppageTiming.boardingDroping', function ($query) use ($dropingingPointId){
             if($dropingingPointId)  
             $query->whereIn('id', (array)$dropingingPointId);
             })       
         ->whereHas('busOperator', function ($query) use ($operatorId){
             if($operatorId)
             $query->whereIn('id', (array)$operatorId);            
             })
         ->whereHas('busAmenities.amenities', function ($query) use ($amenityId){
             if($amenityId)
             $query->whereIn('id', (array)$amenityId);            
             })  
         ->where('id',$busId)
         ->where('status','1')
         ->get();
      
     }
    
   
 //Calculate Booked seats and remove it from total count
    public function getBookedSeats($sourceID,$destinationID,$entry_date,$busId){
        $requestedSeq = $this->viewSeatsRepository->busLocationSequence($sourceID,$destinationID,$busId);
        $reqRange = Arr::sort($requestedSeq);
        $booked = Config::get('constants.BOOKED_STATUS');
        $seatHold = Config::get('constants.SEAT_HOLD_STATUS');

        $bookingIds = $this->viewSeatsRepository->bookingIds($busId,$entry_date,$booked,$seatHold,$sourceID,$destinationID);
        
        $sl = 0;
        $st = 0;
        $tot = 0;

        if (sizeof($bookingIds)){
            $blockedSeats=array();
            foreach($bookingIds as $bookingId){
                $seatsIds = array();
                $bookedSeatIds = $this->viewSeatsRepository->bookingDetail($bookingId);
                foreach($bookedSeatIds as $bookedSeatId){
                    $seatsIds[] = $this->viewSeatsRepository->busSeats($bookedSeatId);
                    $gender[] = $this->viewSeatsRepository->bookingGenderDetail($bookingId,$bookedSeatId);     
                }   
                 $srcId=  $this->viewSeatsRepository->getSourceId($bookingId);
                 $destId=  $this->viewSeatsRepository->getDestinationId($bookingId);
                 $bookedSequence = $this->viewSeatsRepository->bookedSequence($srcId,$destId,$busId);
                 $bookedRange = Arr::sort($bookedSequence);

                //seat available on requested seq so blocked seats are none.
                // if((last($reqRange)<=head($bookedRange)) || (last($bookedRange)<=head($reqRange))){
                //     //$blockedSeats=array();
                //     return [$sl,$st,$tot];
                    
                // }
                // else{   //seat not available on requested seq so blocked seats are calculated   
                //     //$blockedSeats = array_merge($blockedSeats,$seatsIds);
                //     $a = $this->verifySeat($busId,$sourceID,$destinationID,$entry_date,$bookingId);
                // } 

 
                //seat not available on requested seq so blocked seats are calculated 
                if((last($reqRange)>head($bookedRange)) || (last($bookedRange)>($reqRange))){
                    $a = $this->verifySeat($busId,$sourceID,$destinationID,$entry_date,$bookingId);
                 }
                if(isset($a) && $a != null){
                    $sl = $sl + $a[0];
                    $st = $st + $a[1];
                    $tot = $tot + $a[2];
                }
            }
        }else{          //no booking on that specific date, so all seats are available
                //$blockedSeats=array();
                return [$sl,$st,$tot];
        }
        return [$sl,$st,$tot];
    }  
    
    public function verifySeat($busId,$sourceID,$destinationID,$entry_date,$bookingID)
    { 
        
        $seaterRecords = 0;
        $sleeperRecords = 0;
        $totalBookedCount = 0;
        $booked = Config::get('constants.BOOKED_STATUS');
        $seatHold = Config::get('constants.SEAT_HOLD_STATUS');
        $booked_seats = BookingDetail::where('booking_id',$bookingID) 
                        ->with(["busSeats.seats"])
                        ->get();
        $collection = collect($booked_seats);
        $i = 0;
            foreach($collection as $cid){
                $class = $cid->busSeats->seats->seat_class_id;
                if($class==1){
                    $seaterRecords ++;
                }
                    elseif($class==2 || $class==3){
                    $sleeperRecords ++;
                }
                $i++;
            }
           
        $totalBookedCount = $sleeperRecords+$seaterRecords;
        return [$sleeperRecords,$seaterRecords,$totalBookedCount];
    }

    public function getbusTypes()
    { 
        return $this->busClass->get(['id','class_name']);
    }

    public function getseatTypes()
    {
        return $this->seatClass->where('id',1)->orWhere('id',2)->get(['id','name']);
    }

    public function getboardingPoints($sourceID,$busIds)
    {

        $boardingIds = BusStoppageTiming::whereIn('bus_id',$busIds)->pluck('boarding_droping_id');
        return $this->boardingDroping
                                     ->whereIn('id', $boardingIds)
                                     ->where('location_id', $sourceID)
                                     ->where('status', '1')
                                     ->get(['id','boarding_point']);
    }

    public function getdropingPoints($destinationID,$busIds)
    {
        $dropingIds = BusStoppageTiming::whereIn('bus_id',$busIds)->pluck('boarding_droping_id');
        return $this->boardingDroping->whereIn('id', $dropingIds)
                                     ->where('location_id', $destinationID)
                                     ->where('status', '1')
                                     ->get(['id','boarding_point']);       
    }

    public function getbusOperator($busIds)
    {
        $busOperatorIds = Bus::whereIn('id',$busIds)->pluck('bus_operator_id');
        return $this->busOperator
                    ->whereIn('id', $busOperatorIds)
                    ->where('status', '1')
                    ->get(['id','operator_name','organisation_name']);
    }

    public function getamenities($busIds)
    {
        $path= $this->commonRepository->getPathurls();
        $path= $path[0];

        $amenityIds = BusAmenities::whereIn('bus_id',$busIds)->pluck('amenities_id');
        $amenityDatas = $this->amenities
                             ->whereIn('id', $amenityIds)
                             ->where('status', '1')->get(['id','name','amenities_image','android_image']);
        foreach($amenityDatas as $a){
            if($a != null && isset($a->amenities_image) )
            {
                $a->amenities_image = $path->amenity_url.$a->amenities_image;   
            }
            if($a != null && isset($a->android_image) )
            {
                $a->android_image = $path->amenity_url.$a->android_image;   
            }
        }
       return $amenityDatas;

    }

    public function busDetails($request)
    { 
        $busId = $request['bus_id'];
        $sourceID = $request['source_id'];      
        $destinationID = $request['destination_id']; 

        $path= $this->commonRepository->getPathurls();
        $path= $path[0];

        $result['busDetails'] =  $this->bus->where('id',$busId)->where('id',$busId)
                                ->with('cancellationslabs.cancellationSlabInfo')
                                ->with(['busAmenities'  => function ($query) {
                                    $query->with(['amenities' =>function ($a){
                                        $a->where('status',1);
                                        $a->select('id','name','android_image');
                                    }]);
                                }]) 
                                ->with(['busSafety'  => function ($query) {
                                    $query->with(['safety' =>function ($a){
                                        $a->where('status',1);
                                        $a->select('id','name','android_image');
                                    }]);
                                }]) 
                                ->with(['busGallery' => function ($a){
                                    $a->where('status',1);
                                    }])
                                ->with(['review' => function ($query) {                    
                                        $query->where('status',1);
                                        $query->select('bus_id','users_id','title','rating_overall','rating_comfort','rating_clean','rating_behavior','rating_timing','comments');  
                                        $query->with(['users' =>  function ($u){
                                            $u->select('id','name','profile_image');
                                    }]);                      
                                    }])
                                ->where('status','1')
                                ->get();  
                           
        $record = $result['busDetails']; 
            
        if($record[0]->busAmenities){
            $amenityDatas = [];  
            foreach($record[0]->busAmenities as $am_dt){
                if($am_dt->amenities != NULL)
                {
                    $am_android_image='';
                    if($am_dt->amenities->android_image !='')
                    {
                    $am_dt->amenities->android_image = $path->amenity_url.$am_dt->amenities->android_image;   
                    }
                }
            }
        }
        if($record[0]->busSafety){
            foreach($record[0]->busSafety as $sd){
                if($sd->safety != NULL)
                {
                    $safety_android_image='';
                    if($sd->safety->android_image != '' )
                    {
                    $sd->safety->android_image = $path->safety_url.$sd->safety->android_image;   
                    }
                }
            }
        }
        if(count($record[0]->busGallery)>0){
            foreach($record[0]->busGallery as  $k => $bp){
                if($bp->bus_image_1 != null && $bp->bus_image_1!=''){                        
                    $bp->bus_image_1 = $path->busphoto_url.$bp->bus_image_1;                         
                }

                if($bp->bus_image_2 != null && $bp->bus_image_2 !=''){                        
                    $bp->bus_image_2 = $path->busphoto_url.$bp->bus_image_2;                        
                }

                if($bp->bus_image_3 != null && $bp->bus_image_3 !=''){                        
                    $bp->bus_image_3 = $path->busphoto_url.$bp->bus_image_3;                        
                }

                if($bp->bus_image_4 != null && $bp->bus_image_4 !=''){                        
                    $bp->bus_image_4 = $path->busphoto_url.$bp->bus_image_4;                        
                }

                if($bp->bus_image_5 != null && $bp->bus_image_5 !=''){                        
                    $bp->bus_image_5 = $path->busphoto_url.$bp->bus_image_5;                        
                }
            }
        }
        
        $Totalrating=0;
        $Totalrating_5star=0;
        $Totalrating_4star=0;
        $Totalrating_3star=0;
        $Totalrating_2star=0;
        $Totalrating_1star=0;

        if($record[0]->review){
            foreach($record[0]->review as $rv){

                $Totalrating += $rv->rating_overall;  
                if($rv->rating_overall==5){
                 $Totalrating_5star ++;   
                } 
                if($rv->rating_overall==4){
                 $Totalrating_4star ++;   
                } 
                if($rv->rating_overall==3){
                 $Totalrating_3star ++;   
                } 
                if($rv->rating_overall==2){
                 $Totalrating_2star ++;   
                } 
                if($rv->rating_overall==1){
                 $Totalrating_1star ++;   
                }  

                if($rv->users->profile_image != NULL && $rv->users->profile_image != ''){
                    $contains = Str::contains($rv->users->profile_image, 'https');
                    if(!$contains){
                        $rv->users->profile_image = $path->profile_url.$rv->users->profile_image;
                    }  
                }
            }
        }

        if($Totalrating > 0 && count($record[0]->review) > 0 ){
            $Totalrating = number_format($Totalrating/count($record[0]->review),1);
        }

        


        $result["Totalrating"] = $Totalrating;
        $result["Totalrating_5star"] = $Totalrating_5star;
        $result["Totalrating_4star"] = $Totalrating_4star;
        $result["Totalrating_3star"] = $Totalrating_3star;
        $result["Totalrating_2star"] = $Totalrating_2star;
        $result["Totalrating_1star"] = $Totalrating_1star;

        $result['boarding_point'] = $this->busStoppageTiming
                                              ->where('bus_id', $busId)
                                              ->where('location_id', $sourceID)
                                              ->where('status','1')
                                              ->get();
        $result['dropping_point'] = $this->busStoppageTiming
                                              ->where('bus_id', $busId)
                                              ->where('location_id', $destinationID)
                                              ->where('status','1')
                                              ->get();  

        return $result;
    }

  public function UpdateExternalApiLocation(){

     $dolphindata= $this->dolphinService->GetCityPair();

     $fupdated=0;
     $tupdated=0;
     $fadded=0;
     $tadded=0;

    if($dolphindata){
        foreach($dolphindata as $data){

            $fromLocation = $this->location
            ->where('name',$data['FromCity'])
            ->where('status','!=',2)
            ->get();

            $toLocation = $this->location
            ->where('name',$data['ToCity'])
            ->where('status','!=',2)
            ->get();
    
                if(count($fromLocation) == 0)
                {
                    $location = new $this->location;
                    $insertData['name']=$data['FromCity']; 
                    $insertData['is_dolphin']=1;
                    $insertData['dolphin_id']=$data['FromCityID'];
                    $location=$this->LocationModel($insertData,$location);
                    $location->save();

                    $fadded++;

                    
                }else{
                    $location = $this->location->find($fromLocation[0]->id);
                    $updateData['name']=$data['FromCity']; 
                    $updateData['is_dolphin']=1;
                    $updateData['dolphin_id']=$data['FromCityID'];
                    $location=$this->LocationModel($updateData,$location);
                    $location->update();

                    $fupdated++;

                    
                }  


                if(count($toLocation) == 0)
                {
                    $location = new $this->location;
                    $insertData['name']=$data['ToCity'];                   
                    $insertData['is_dolphin']=1;
                    $insertData['dolphin_id']=$data['ToCityID'];
                    $location=$this->LocationModel($insertData,$location);
                    $location->save();
                    $tadded++;
                    
                }else{
                    $location = $this->location->find($toLocation[0]->id);
                    $updateData['name']=$data['ToCity'];
                    $updateData['is_dolphin']=1;
                    $updateData['dolphin_id']=$data['ToCityID'];
                    $location=$this->LocationModel($updateData,$location);
                    $location->update();
                    $tupdated++;
                    
                }  


        }
    }

    //Log::info(($fadded+$tadded)." added & ".($fupdated+$tadded)." updated");

    return ($fadded+$tadded)." added & ".($fupdated+$tadded)." updated";

  }  

  public function LocationModel($data, Location $location)
    { 
        $trim = trim( $data['name']);
        $remove_space= str_replace(' ', '-', $trim);  
        $remove_special_char = preg_replace('/[^A-Za-z0-9\-]/', '',$remove_space);             
        $url = strtolower($remove_special_char);


      $location->name = $data['name'];
      $location->url = $url;
      $location->is_dolphin = $data['is_dolphin'];
      $location->status = 1;
      $location->dolphin_id = $data['dolphin_id'];
      $location->created_by = 'CRON JOB';
      return $location;
    }

    ////////Mantis Location save/////////////

  public function updateMantisApiLocation(){

        $mantisdata = $this->mantisService->getCityList();
        //return $mantisdata; 
        $locationUpdated=0;
        $locationAdded=0;
   
       if($mantisdata){
           foreach($mantisdata as $data){
            //return $data->City;
               $loc = $this->location
               ->where('name',$data->City)
               ->where('status','!=',2)
               ->get();

                   if(count($loc) == 0)
                   {
                       $location = new $this->location;
                       $insertData['name'] = $data->City;  
                       $insertData['is_mantis'] = 1;
                       $insertData['mantis_id'] = $data->CityId;
                       $location=$this->LocModel($insertData,$location);
                       $location->save();
   
                       $locationAdded++;   
                   }else{
                       $location = $this->location->find($loc[0]->id);
                       $updateData['name'] = $data->City;  
                       $updateData['is_mantis'] = 1;
                       $updateData['mantis_id'] = $data->CityId;
                       $location=$this->LocModel($updateData,$location);
                       $location->update();
   
                       $locationUpdated++;   
                   }  
           }
       }
    }

    public function LocModel($data, Location $location)
    { 
        $trim = trim( $data['name']);
        $remove_space= str_replace(' ', '', $trim);  
        $remove_special_char = preg_replace('/[^A-Za-z0-9\-]/', '',$remove_space);             
        $url = strtolower($remove_special_char);

        $location->name = $data['name'];
        $location->url = $url;
        $location->is_mantis = $data['is_mantis'];
        $location->status = 1;
        $location->mantis_id = $data['mantis_id'];
        $location->created_by = 'CRON JOB';
        return $location;
    }
  }