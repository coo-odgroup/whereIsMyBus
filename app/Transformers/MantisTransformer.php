<?php

namespace App\Transformers;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use App\Services\MantisService;
use App\Repositories\ListingRepository;
use App\Models\IncomingApiCompany;
use App\Models\Bus;
use App\Models\Location;
use App\Models\Users;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Jobs\SendEmailTicketJob;
use App\Models\Credentials;
use App\Models\ClientFeeSlab;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MantisTransformer
{

    protected $listingRepository; 
    protected $mantisService;
    protected $booking;
    protected $credentials;
    public $pickuppoints;

    public function __construct(ListingRepository $listingRepository,MantisService $mantisService,Booking $booking,Credentials $credentials)
    {
        $this->listingRepository = $listingRepository;
        $this->mantisService = $mantisService;
        $this->booking = $booking;
        $this->credentials = $credentials;   
    }
    
    public function BusLists($request,$clientRole,$clientId){
        $srcResult = $this->listingRepository->getLocationID($request['source']);
        $destResult = $this->listingRepository->getLocationID($request['destination']);
        $entry_date = date("Y-m-d", strtotime($request['entry_date']));

        $mantisresult=[];

        if($srcResult[0]->is_mantis==1 && $destResult[0]->is_mantis==1){

            $mantis_source = $srcResult[0]->mantis_id;
            $mantis_dest = $destResult[0]->mantis_id;

            $data = $this->mantisService->search($mantis_source,$mantis_dest,$entry_date);
            
            $mantisresult = $this->BusListProcess($data,$srcResult[0]->id,$destResult[0]->id,$clientRole,$clientId);
        }
        return $mantisresult;
    }

    public function Filter($request,$clientRole,$clientId){

        $sourceID = $request['sourceID'];      
        $destinationID = $request['destinationID'];
        $entry_date = date("Y-m-d", strtotime($request['entry_date']));

        $srcResult = $this->listingRepository->getLocationResult($sourceID);
        $destResult = $this->listingRepository->getLocationResult($destinationID);

        $mantisresult = [];

        if($srcResult[0]->is_mantis == 1 && $destResult[0]->is_mantis == 1){

            $mantis_source = $srcResult[0]->mantis_id;
            $mantis_dest = $destResult[0]->mantis_id;
            
            $data = $this->mantisService->search($mantis_source,$mantis_dest,$entry_date);
            
            $mantisresult = $this->BusListProcess($data,$sourceID,$destinationID,$clientRole,$clientId);
        } 
        return $mantisresult;
    }

    public function BusListProcess($data,$src_id,$dest_id,$clientRole,$clientId){
        
        $mantisesult['regular'] = [];
        $mantisresult['soldout'] = [];
        $mantisresult = [];

       // 
    
        if (!empty($data->data->Buses)){
            foreach($data->data->Buses as $bus)
            {
                $busarr = [];
                $pickuppoints = null;
                foreach($bus->Pickups as $pickuppoint){

                    $datetime = Carbon::createFromFormat('Y-m-d H:i:s', $pickuppoint->PickupTime);
                    $picktime = $datetime->format('H:i:s A');
                    $pickuppoints = $pickuppoints.'#'.$pickuppoint->PickupCode.'|'.$pickuppoint->PickupName.'|'.$picktime;
                }
                $dropPoints = null;
                foreach($bus->Dropoffs as $dropPoint){

                    $datetime = Carbon::createFromFormat('Y-m-d H:i:s', $dropPoint->DropoffTime);
                    $dropTime = $datetime->format('H:i:s A');
                    $dropPoints = $dropPoints.'#'.$dropPoint->DropoffCode.'|'.$dropPoint->DropoffName.'|'.$dropTime;
                }

                $arr_time = new DateTime($bus->ArrTime);
                $dep_time = new DateTime($bus->DeptTime);
                $totalTravelTime = $dep_time->diff($arr_time);
                $totalJourneyTime = ($totalTravelTime->format("%a") * 24) + $totalTravelTime->format(" %h"). "h". $totalTravelTime->format(" %im");

                $cancellationDuration=[];
                $cancellationDuduction=[];

                    foreach($bus->Canc as $c){
                        $cancellationDuration[] = $c->Mins/60;          
                        $cancellationDuduction[] = $c->Pct;
                    }
                    $collection = collect($cancellationDuration); 
                    $collection->push(9999);
                    $chunks = $collection->sliding(2);
                    foreach($chunks as $chunk){
                        $cancArray[] = $chunk->implode('-');
                    }
                $busarr =array(
                    "origin"=> "MANTIS",
                    "srcId"=> $src_id,
                    "destId"=> $dest_id, 
                    "display"=> "show",
                    "CompanyID"=> (int)$bus->CompanyId,
                    "ReferenceNumber" => "",
                    "BoardingPoints" => $pickuppoints,
                    "DroppingPoints" => $dropPoints,
                    "busId"=> (int)$bus->RouteBusId,
                    "busName"=> $bus->CompanyName,
                    "via"=> "",
                    "RouteTimeID"=> "",
                    "popularity"=> null,
                    "busNumber"=> "",
                    "maxSeatBook"=> 6,
                    "conductor_number"=> "",
                    "couponCode"=> [],
                    "couponDetails"=> [],
                    "operatorId"=> 0,
                    "operatorUrl"=> "",
                    "operatorName"=> $bus->CompanyName,
                    "sittingType"=> $bus->BusLabel,
                    "bus_description"=>($bus->BusType->IsAC == 'AC') ? 'AC' : 'NON AC', 
                    "busType"=> ($bus->BusType->IsAC == 'AC') ? 'AC' : 'NON AC', 
                    "busTypeName"=> $bus->BusLabel,
                    "totalSeats"=> $bus->BusStatus->Availability,
                    "seaters"=> '',
                    "sleepers"=> '',
                    "startingFromPrice"=> implode(".", $bus->BusStatus->BaseFares), 
                    "departureTime"=> date("H:i",strtotime($bus->DeptTime)),
                    "arrivalTime"=> date("H:i",strtotime($bus->ArrTime)),
                    "bookingCloseTime" =>'',
                    "totalJourneyTime"=> $totalJourneyTime, 
                    "amenity"=> [],
                    "safety"=> [],
                    "busPhotos"=> [],
                    "cancellationDuration"=> $cancArray,
                    "cancellationDuduction"=> $cancellationDuduction,
                    "cancellationPolicyContent"=> null,
                    "TravelPolicyContent"=> null,
                    "Totalrating"=> 0,
                    "Totalrating_5star"=> 0,
                    "Totalrating_4star"=> 0,
                    "Totalrating_3star"=> 0,
                    "Totalrating_2star"=> 0,
                    "Totalrating_1star"=> 0,
                    "reviews"=> []
                );
                
                //$mantisesult['regular'][] = $busarr;
                if($bus->BusStatus->Availability>0){
                    $mantisesult['regular'][] = $busarr;
                }else{
                    $mantisesult['soldout'][] = $busarr;
                }
            }   
        } 

        return $mantisesult;
    }

    public function MantisSeatLayout($sourceId,$destinationId,$journeyDate,$busId,$clientRole,$clientId){
        $srcResult = $this->listingRepository->getLocationResult($sourceId);
        $destResult = $this->listingRepository->getLocationResult($destinationId);

        $mantisSeatResult = [];
        
        if($srcResult[0]->is_mantis == 1 && $destResult[0]->is_mantis == 1){

            $mantis_source = $srcResult[0]->mantis_id;
            $mantis_dest = $destResult[0]->mantis_id;
            
            $mantisSeatResult = $this->mantisService->chart($mantis_source,$mantis_dest,$journeyDate,$busId);    
        } 
        //return $mantisSeatResult; 
        $viewSeat['bus'][] = [ 
            "id"=> 0,
            "name"=> "",
            "bus_seat_layout_id"=> 0
         ];
        if (isset($mantisSeatResult->data->ChartLayout->Info->Lower)){
            $lowerberthArr = $this->seatBerthArr($mantisSeatResult,'Lower');
            $viewSeat['lower_berth'] = $lowerberthArr[0];
            $viewSeat['lowerBerth_totalRows'] = $lowerberthArr[1];
            $viewSeat['lowerBerth_totalColumns'] = $lowerberthArr[2];
        }
        if (isset($mantisSeatResult->data->ChartLayout->Info->Upper)){
            $upperberthArr = $this->seatBerthArr($mantisSeatResult,'Upper');
            $viewSeat['upper_berth'] = $upperberthArr[0];
            $viewSeat['upperBerth_totalRows'] = $upperberthArr[1];
            $viewSeat['upperBerth_totalColumns'] = $upperberthArr[2];
        }
        return $viewSeat;
    }
   
    public function seatArray($seatStatus,$seatFares,$i,$type,$j){
        $lowerberthArr = [];
        $seaterArr = [];
        switch($seatStatus){
            case '1':
                $seaterArr['bus_seats'] = [
                    "ticket_price_id"=> 0,
                    "seats_id"=>$type+($j+1),
                    "new_fare"=> $seatFares[0],
                    "mantis_base_fare" => $seatFares[1]
                ];
                break;
            case '2':
                $seaterArr['bus_seats'] = [
                    "ticket_price_id" => 0,
                    "seat_for" => "male",
                    "seats_id"=>$type+($j+1),
                    "new_fare"=> $seatFares[0],
                    "mantis_base_fare" => $seatFares[1]
                ];
                break;
            case '3':
                 $seaterArr['bus_seats'] = [
                    "ticket_price_id" => 0,
                    "seat_for" => "female",
                    "seats_id"=>$type+($j+1),
                    "new_fare"=> $seatFares[0],
                    "mantis_base_fare" => $seatFares[1]
                ];
                break;
            case '-2':
                $seaterArr['Gender'] = "M";
                break;
            case '-3':
                $seaterArr['Gender']="F";
                break;
            
        }
        array_push($lowerberthArr,$seaterArr);
        return $lowerberthArr;
    }

public function seatBerthArr($mantisSeatResult,$berthType){
        $seatTypeArr = $mantisSeatResult->data->ChartLayout->Layout->$berthType;
		$seatTextArr = $mantisSeatResult->data->ChartSeats->Seats;
		$seatStatus = $mantisSeatResult->data->SeatsStatus->Status;
		$lRows = $mantisSeatResult->data->ChartLayout->Info->$berthType->MaxRows;
		$lCols = $mantisSeatResult->data->ChartLayout->Info->$berthType->MaxCols;
		$seatFares = $mantisSeatResult->data->SeatsStatus->Fares;

		$rowArray = [];
		$colArray = [];
		$berthTypeArr = [];
		$matrixArr = [];
		$seatmatrixArr = [];
		
		for ($row = 0; $row < $lRows; $row++) {
			$rowArray[] = $row;
		}
		for ($col = 0; $col < $lCols; $col++) {
			$colArray[] = $col;
		}
		$matrixArr = Arr::crossJoin($rowArray, $colArray);
        if($berthType=='Lower'){
            $i = $j = 0;
            $type = 100;
        }else{
            $i = $seatTypeArr[0][0];
            $j = 0;
            $type = 200;
        }
		foreach($seatTypeArr as $sta){
			$seaterArr = [
				"id"=> $type+($j+1),
				"bus_seat_layout_id"=> 0,
				"seatText"=> $seatTextArr[$i],
				"rowNumber"=> $sta[1],
				"colNumber"=> $sta[2],                          
			]; 
		   
			$width = $sta[3];
			$height = $sta[4]; 
			switch ([$width, $height]) {
				case ['1', '1']:
					////Seater
					$seaterArr["seat_class_id"]= 1;
                    $seaterArr["berthType"]= "1";
					$seatmatrix = [$sta[1],$sta[2]];
                    $seatmatrixArr = Arr::prepend($seatmatrixArr,$seatmatrix);
				break;
				case ['1', '2']:
					////vertical sleeper
					$seaterArr["seat_class_id"]= 2;
                    $seaterArr["berthType"]= "2";
					$seatmatrix = Arr::crossJoin( [$sta[1], $sta[1]+1],[$sta[2]]);
					
				break;
				case ['2', '1']:
					////Horizontal sleeper
					$seaterArr["seat_class_id"]= 2;
                    $seaterArr["berthType"]= "2";
					$seatmatrix = Arr::crossJoin([$sta[1]],[$sta[2],$sta[2]+1]);
				break;
			}
			$berthArray = $this->seatArray($seatStatus[$i],$seatFares[$i],$i,$type,$j);

			$seatmatrixArr =  Arr::prepend($seatmatrixArr,$seatmatrix);
			$seaterArr["matrix"]= $seatmatrix;
			
			$key1 = Arr::has($berthArray[0], 'bus_seats');
			$key2 = Arr::has($berthArray[0], 'Gender');
			if($key1){
				$seaterArr["bus_seats"]=$berthArray[0]["bus_seats"];
			}
			if($key2){
				$seaterArr["Gender"]=$berthArray[0]["Gender"];   
			}
			$berthTypeArr = Arr::prepend($berthTypeArr,$seaterArr);
			$berthArray=[];
			$seatmatrix=[];
			$seaterArr = [];
			$i++;  
            $j++;
		}
		$chkArrMatrix = [];
		foreach($seatmatrixArr as $sma){
			$count = count(collect($sma)->flatten());
			if($count==2){
				$chkArrMatrix = Arr::prepend($chkArrMatrix,$sma);
			}else{ 
				$chkArrMatrix = Arr::prepend($chkArrMatrix,$sma[0]);
				$chkArrMatrix = Arr::prepend($chkArrMatrix,$sma[1]);
			}
		}
		foreach($chkArrMatrix as $sa){
			foreach( $matrixArr as $sm){
				if($sa!=$sm){
					$sao[]=$sm;
				}
			}
			$matrixArr = $sao;
			$sao=[];
		}
		foreach($matrixArr as $b){
			$blank = [
				"id"=> $type+($j + 1),
				"bus_seat_layout_id"=> 0,
				"seat_class_id"=> 4,
				"seatText"=> "",
				"rowNumber"=> $b[0],
				"colNumber"=> $b[1],                         
			]; 
            if($berthType=='Lower'){
                $blank["berthType"] = "1";
            }else{
                $blank["berthType"] = "2";
            }
			$berthTypeArr = Arr::prepend($berthTypeArr,$blank);
			$j++;
		}
		//return collect($seatmatrixArr)->flatten(1);
        return [$berthTypeArr,$lRows,$lCols];
}
    public function GetseatText($sourceId,$destinationId,$journeyDate,$busId,$id,$clientRole,$clientId){
        $res = $this->MantisSeatLayout($sourceId,$destinationId,$journeyDate,$busId,$clientRole,$clientId);
        /////need to check//////
        // if($res['lower_berth']){
        // return collect($res['lower_berth'])->whereIn('id', $id)->pluck('seatText');
        // }

        $key = array_search($id, array_column($res['lower_berth'], 'id'));

        if($key > -1){
        return $res['lower_berth'][$key]['seatText'];
        }

        $key2 = array_search($id, array_column($res['upper_berth'], 'id'));

        if($key2 > -1){
        return $res['upper_berth'][$key2]['seatText'];
        }
    }  

    public function GetseatFare($sourceId,$destinationId,$journeyDate,$busId,$id,$clientRole,$clientId){
        $res = $this->MantisSeatLayout($sourceId,$destinationId,$journeyDate,$busId,$clientRole,$clientId);
        
        $key = array_search($id, array_column($res['lower_berth'], 'id'));

        if($key > -1){
        return $res['lower_berth'][$key]['bus_seats']['new_fare'];
        }

        $key2 = array_search($id, array_column($res['upper_berth'], 'id'));

        if($key2 > -1){    
        return $res['upper_berth'][$key2]['bus_seats']['new_fare'];
        }
    }  

    public function HoldSeats($seatIds,$sourceId,$destinationId,$entry_date,$busId,$records,$clientRole,$IsAcBus){
       
        $clientId = 1;
        $srcResult = $this->listingRepository->getLocationResult($sourceId);
        $destResult = $this->listingRepository->getLocationResult($destinationId);

        if($srcResult[0]->is_mantis == 1 && $destResult[0]->is_mantis == 1){
            $mantis_source_id = $srcResult[0]->mantis_id;
            $mantis_dest_id = $destResult[0]->mantis_id;     
        } 
        $mantisSeatResult = [];
        $mantisSeatresult = $this->MantisSeatLayout($sourceId,$destinationId,$entry_date,$busId,$clientRole,$clientId);
        $seatR = [];
        $lbcollection = [];
        $ubcollection = [];
        //return $mantisSeatresult;
        if(!empty($seatIds)){
            if (isset($mantisSeatresult['lower_berth'])){
                $lbcollection = collect($mantisSeatresult['lower_berth']);
            }
            if (isset($mantisSeatresult['upper_berth'])){
                $ubcollection = collect($mantisSeatresult['upper_berth']);
            }
            $seatR = $lbcollection->merge($ubcollection);
          }
        $passengers = [];
        
        if(!empty($records[0]->bookingDetail)){
            foreach($records[0]->bookingDetail as $bdt){
                $collection = collect($bdt);
                //$filtered = $collection->only(['passenger_name', 'passenger_gender','passenger_age',seat_name']);
                $seatTxt = $collection->get('seat_name');
                $seatType = $seatR->where('seatText', $seatTxt)->pluck('berthType');
                //$fare = $seatR->where('seatText', $seatTxt)->pluck('bus_seats.new_fare');
                $filtered = [
                    'Name' => $collection->get('passenger_name'),
                    'Age' => (int)$collection->get('passenger_age'),
                    'Gender' => $collection->get('passenger_gender'),
                    'SeatNo' => $collection->get('seat_name'),
                    'Fare' => (int)$collection->get('seat_fare'),
                    'SeatTypeId' => (int)$seatType[0], 
                    'IsAcSeat' => $IsAcBus
                ];
                $passengers = Arr::prepend($passengers, $filtered);
            }
        }
        $PickUpid = $records[0]->PickupID;
        $DropOffid = $records[0]->DropID;

        $bookingDet = [
            'FromCityId' => $mantis_source_id,
            'ToCityId' => $mantis_dest_id,
            'JourneyDate' => $entry_date,
            'BusId' => $records[0]->bus_id,
            'PickUpID' => "$PickUpid",
            'DropOffID' => "$DropOffid",
            'ContactInfo' =>[
                'CustomerName' => $records[0]->users->name,
                'Email' => $records[0]->users->email,
                'Phone' => '9583918888',//$records[0]->users->phone,
                'Mobile' => '9583918888',//$records[0]->users->phone,
            ],
            'Passengers' => $passengers
        ]; 
        //return $bookingDet;
        $res = $this->mantisService->HoldSeats($bookingDet);
        return $res;
    }
    public function HoldSeatsClient($sourceId,$destinationId,$entry_date,$busId,$records,$clientRole,$clientId,$IsAcBus){

        $srcResult = $this->listingRepository->getLocationResult($sourceId);
        $destResult = $this->listingRepository->getLocationResult($destinationId);
        if($srcResult[0]->is_mantis == 1 && $destResult[0]->is_mantis == 1){
            $mantis_source_id = $srcResult[0]->mantis_id;
            $mantis_dest_id = $destResult[0]->mantis_id;     
        } 
        $mantisSeatResult = [];
        $mantisSeatresult = $this->MantisSeatLayout($sourceId,$destinationId,$entry_date,$busId,$clientRole,$clientId);
        $seatR = [];
        $lbcollection = [];
        $ubcollection = [];
            if (isset($mantisSeatresult['lower_berth'])){
                $lbcollection = collect($mantisSeatresult['lower_berth']);
            }
            if (isset($mantisSeatresult['upper_berth'])){
                $ubcollection = collect($mantisSeatresult['upper_berth']);
            }
            $seatR = $lbcollection->merge($ubcollection);
        $passengers = [];
        
        if(!empty($records[0]->bookingDetail)){
            foreach($records[0]->bookingDetail as $bdt){
                $collection = collect($bdt);
                $seatTxt = $collection->get('seat_name');
                $seatType = $seatR->where('seatText', $seatTxt)->pluck('berthType');
                $filtered = [
                    'Name' => $collection->get('passenger_name'),
                    'Age' => (int)$collection->get('passenger_age'),
                    'Gender' => $collection->get('passenger_gender'),
                    'SeatNo' => $collection->get('seat_name'),
                    'Fare' => (int)$collection->get('seat_fare'),
                    'SeatTypeId' => (int)$seatType[0], 
                    'IsAcSeat' => $IsAcBus
                ];
                $passengers = Arr::prepend($passengers, $filtered);
            }
        }
        $PickUpid = $records[0]->PickupID;
        $DropOffid = $records[0]->DropID;

        $bookingDet = [
            'FromCityId' => $mantis_source_id,
            'ToCityId' => $mantis_dest_id,
            'JourneyDate' => $entry_date,
            'BusId' => $records[0]->bus_id,
            'PickUpID' => "$PickUpid",
            'DropOffID' => "$DropOffid",
            'ContactInfo' =>[
                'CustomerName' => $records[0]->users->name,
                'Email' => $records[0]->users->email,
                'Phone' => '9583918888',//$records[0]->users->phone,
                'Mobile' => '9583918888',//$records[0]->users->phone,
            ],
            'Passengers' => $passengers
        ]; 
        //return $bookingDet;
        $res = $this->mantisService->HoldSeats($bookingDet);
        return $res;
    }

    public function BookSeat($records,$holdId){
       
        $res= $this->mantisService->BookSeats($holdId);
        return $res;
       

    }
    public function searchBus($sourceId,$destId,$jDate,$busId){
        
        $srcResult = $this->listingRepository->getLocationResult($sourceId);
        $destResult = $this->listingRepository->getLocationResult($destId);
        $busDtls = [];

        if($srcResult[0]->is_mantis == 1 && $destResult[0]->is_mantis == 1){

            $mantis_source = $srcResult[0]->mantis_id;
            $mantis_dest = $destResult[0]->mantis_id;
            
            $busDtls = $this->mantisService->searchBus($mantis_source,$mantis_dest,$jDate,$busId);
            return  $busDtls;
        }        
    }
    public function isCancellable($pnrNo,$tktNo,$seats){
        return $this->mantisService->isCancellable($pnrNo,$tktNo,$seats);
    }
    public function cancelSeats($pnrNo,$tktNo,$seats){
        return $this->mantisService->cancelSeats($pnrNo,$tktNo,$seats);
    }
}