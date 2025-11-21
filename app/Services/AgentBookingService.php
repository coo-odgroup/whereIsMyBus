<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Repositories\AgentBookingRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Transformers\DolphinTransformer;
use App\Models\Location;
use App\Services\ListingService;
use Illuminate\Support\Arr;
use App\Models\Seats;
use App\Services\ViewSeatsService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AgentBookingService
{
    
    protected $agentBookingRepository;  
    protected $dolphinTransformer;
    protected $listingService; 
    protected $viewSeatsService; 

    public function __construct(AgentBookingRepository $agentBookingRepository,DolphinTransformer $dolphinTransformer,ListingService $listingService,ViewSeatsService $viewSeatsService)
    {
        $this->agentBookingRepository = $agentBookingRepository;
        $this->dolphinTransformer = $dolphinTransformer;
        $this->viewSeatsService = $viewSeatsService;
        $this->listingService = $listingService;


    }
    public function agentBooking($request,$clientRole,$clientId)
    {
        try {

            $bookingInfo = $request['bookingInfo'];
            ////////////////////////busId validation////////////////////////////////////
            $sourceID = $bookingInfo['source_id'];
            $destinationID = $bookingInfo['destination_id'];
            $source = Location::where('id',$sourceID)->first()->name;
            $destination = Location::where('id',$destinationID)->first()->name;

            $ReferenceNumber = $request['bookingInfo']['ReferenceNumber'];
            $origin = $request['bookingInfo']['origin'];

            if($origin !='DOLPHIN' && $origin != 'ODBUS' && $origin !='MANTIS'){
                return 'Invalid Origin';
            }else if($origin=='DOLPHIN'){
                if($ReferenceNumber ==''){
                    return 'ReferenceNumber_empty';
                }
            }

            

            if($origin == 'ODBUS'){

                $reqInfo= array(
                    "source" => $source,
                    "destination" => $destination,
                    "entry_date" => $bookingInfo['journey_date'],
                    "bus_operator_id" => Null,
                    "user_id" => Null,
                    "origin" =>'ODBUS'
                ); 

                $busRecords = $this->listingService->getAll($reqInfo,$clientRole,$clientId);
            
                if($busRecords){
                    $busId = $bookingInfo['bus_id'];
                    $busRecords->pluck('busId');
                    $validBus = $busRecords->pluck('busId')->contains($busId);
                }
                if(!$validBus){
                    return "Bus_not_running";
                }

                $bookingDetail = $request['bookingInfo']['bookingDetail'];//in request passing seats_id with key as bus_seats_id
                $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id');
                $seater = Seats::whereIn('id',$seatIds)->where('berthType',1)->pluck('id');
                $sleeper = Seats::whereIn('id',$seatIds)->where('berthType',2)->pluck('id');
                $entry_date = $bookingInfo['journey_date'];
                $busId = $bookingInfo['bus_id'];
                $sourceId = $bookingInfo['source_id'];
                $destinationId =  $bookingInfo['destination_id'];
                
                $data = array(
                    'busId' => $busId,
                    'sourceId' => $sourceId,
                    'destinationId' => $destinationId,
                    'seater' => $seater,
                    'sleeper' => $sleeper,
                    'entry_date' => $entry_date,
                );
               
                $priceDetails = $this->viewSeatsService->getPriceCalculationOdbus($data,$clientId);
              //  Log::Info($priceDetails);

            }

            if($origin=='DOLPHIN'){
                $bookingDetail = $request['bookingInfo']['bookingDetail'];//in request passing seats_id with key as bus_seats_id
                $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id');

                $entry_date = $bookingInfo['journey_date'];
                $busId = $bookingInfo['bus_id'];
                $sourceId = $bookingInfo['source_id'];
                $destinationId =  $bookingInfo['destination_id'];

                $seatTypArr=$this->dolphinTransformer->GetSeatType($ReferenceNumber,$seatIds,$clientRole,$clientId);
                
                $data = array(
                    'busId' => $busId,
                    'sourceId' => $sourceId,
                    'destinationId' => $destinationId,
                    'seater' => $seatTypArr['seater'],
                    'sleeper' => $seatTypArr['sleeper'],
                    'entry_date' => $entry_date,
                    'ReferenceNumber' => $ReferenceNumber,
                    'origin' => $origin,
                );

                $priceDetails= $this->viewSeatsService->getPriceOnSeatsSelection($data,$clientRole,$clientId);

               // Log::info($priceDetails);
            }
            /////////mantis changes///////
            if($origin =='MANTIS'){
                $bookingDetail = $request['bookingInfo']['bookingDetail'];//in request passing seats_id with key as bus_seats_id
                $seatIds = Arr::pluck($bookingDetail, 'bus_seats_id');
                
                $entry_date = $bookingInfo['journey_date'];
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
                //$sleeper = array_merge($lbSleeper->toArray(), $ubSleeper->toArray());
                
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
            }
          

            $bookTicket = $this->agentBookingRepository->agentBooking($request,$clientRole,$clientId,$priceDetails);
            return $bookTicket;

        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }
       
    }   
   
}