<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Bus;
use App\Repositories\ViewSeatsRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use App\Models\TicketPrice;
use App\Models\BusSeats;
use App\Models\SpecialFare;
use App\Models\ClientFeeSlab;
use App\Models\BusSpecialFare;
use App\Models\OwnerFare;
use App\Models\User;
use App\Models\BusOwnerFare;
use App\Models\Location;
use App\Transformers\DolphinTransformer;
use App\Models\IncomingApiCompany;
use App\Transformers\MantisTransformer;
use Illuminate\Support\Str;
use App\Services\ListingService;



class ViewSeatsService
{
    protected $dolphinTransformer;
    protected $viewSeatsRepository;    
    protected $mantisTransformer;    
    protected $listingService;   
    protected $location;

    public function __construct(ViewSeatsRepository $viewSeatsRepository,DolphinTransformer $dolphinTransformer,MantisTransformer $mantisTransformer,ListingService $listingService,Location $location)
    {
        $this->dolphinTransformer = $dolphinTransformer;
        $this->viewSeatsRepository = $viewSeatsRepository;
        $this->mantisTransformer = $mantisTransformer;
        $this->listingService = $listingService;
         $this->location = $location;
    }
    public function getAllViewSeats($request,$clientRole,$clientId)
    {
        $booked = Config::get('constants.BOOKED_STATUS');
        $seatHold = Config::get('constants.SEAT_HOLD_STATUS');
        $lowerBerth = Config::get('constants.LOWER_BERTH');
        $upperBerth = Config::get('constants.UPPER_BERTH');

        $sourceId = $request['sourceId'];
        $destinationId = $request['destinationId'];
        $busId = $request['busId'];
        $journeyDate = $request['entry_date'];
        $journeyDate = date("Y-m-d", strtotime($journeyDate));

        $ReferenceNumber = (isset($request['ReferenceNumber'])) ? $request['ReferenceNumber'] : '';
        $origin = (isset($request['origin'])) ? $request['origin'] : 'ODBUS';

        if($origin !='DOLPHIN' && $origin != 'ODBUS' && $origin != 'MANTIS'){
            return 'Invalid Origin';
        }
        ////////////////Mantis changes/////////////////
        else if($origin == 'MANTIS'){
            $mantisSeatresult = $this->mantisTransformer->MantisSeatLayout($sourceId,$destinationId,$journeyDate,$busId,$clientRole,$clientId);
            return $mantisSeatresult;
        }
        //////////////////////////////////////////////
        else if($origin=='DOLPHIN'){

            if($ReferenceNumber ==''){

                return 'ReferenceNumber_empty';

            }else{
                return $dolphinSeatresult= $this->dolphinTransformer->seatLayout($ReferenceNumber,$clientRole,$clientId);
            }
        }else if($origin=='ODBUS'){

            $source_nm=$this->location->where('id',$sourceId)->first()->name;
            $destination_nm=$this->location->where('id',$destinationId)->first()->name;


             $reqInfo = array(
                "source" => $source_nm,
                "destination" => $destination_nm,
                "entry_date" => $journeyDate,
                "bus_operator_id" => Null,
                "user_id" => Null,
                "origin" =>'ODBUS'
            ); 
           
            $busRecords = $this->listingService->getAll($reqInfo,$clientRole,$clientId);
         

                if ($busRecords) {
                    $validBus = $busRecords->pluck('busId')->contains($busId);
                }

                if (!$validBus) {
                    return "Bus_not_running";
                }



        $busDet = Bus::where('id', $busId)->first();
        $lower_sleeper_extra_fare =$busDet->lower_sleeper_extra_fare ;
        $user_id =$busDet->user_id ;
        $miscfares = $this->viewSeatsRepository->miscFares($busId,$journeyDate);

        $requestedSeq = $this->viewSeatsRepository->busLocationSequence($sourceId,$destinationId,$busId);

        $reqRange = Arr::sort($requestedSeq);
        $bookingIds = $this->viewSeatsRepository->bookingIds($busId,$journeyDate,$booked,$seatHold,$sourceId,$destinationId);
       
         /////// 15-sep-2024 :: date wise fare slab
            //$ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
            $ticketFareSlabs = getTicketFareslab($busId,$journeyDate); // common.php
            
        
        if (sizeof($bookingIds)){
            $blockedSeats=array();
            foreach($bookingIds as $bookingId){
                //$seatsIds = array();
                $seatIDsPerbooking = array();
                $bookedSeatIds = $this->viewSeatsRepository->bookingDetail($bookingId);
                foreach($bookedSeatIds as $bookedSeatId){
                    $seatIDsPerbooking[] = $this->viewSeatsRepository->busSeats($bookedSeatId);
                    $seatsIds[] = $this->viewSeatsRepository->busSeats($bookedSeatId);
                    $gender[] = $this->viewSeatsRepository->bookingGenderDetail($bookingId,$bookedSeatId);     
                }  

                $srcId=  $this->viewSeatsRepository->getSourceId($bookingId);
                $destId=  $this->viewSeatsRepository->getDestinationId($bookingId);
                $bookedSequence = $this->viewSeatsRepository->bookedSequence($srcId,$destId,$busId);
                $bookedRange = Arr::sort($bookedSequence);
                
                 //>>1
                //seat available on requested seq so blocked seats are none.
                // if((last($reqRange)<=head($bookedRange)) || (last($bookedRange)<=head($reqRange))){
                //    //$blockedSeats=array();
                    
                // }
                // else{   //seat not available on requested seq so blocked seats are calculated   
                //     $blockedSeats = array_merge($blockedSeats,$seatsIds);
                // } 

                //>>2
                // //seat not available on requested seq so blocked seats are calculated 
                // if((last($reqRange)>head($bookedRange)) || (last($bookedRange)>head($reqRange))){
                //     //return $bookedRange;
                //     $blockedSeats = array_merge($blockedSeats,$seatsIds);
                //  }

                //>>3 seat available on requested seq so blocked seats are none.
                if((last($reqRange)<=head($bookedRange))){
                    $blockedSeats=array();
                 }
                 elseif((last($bookedRange)<=head($reqRange))){  
                     $blockedSeats=array(); 
                 }else{
                     //seat not available on requested seq so blocked seats are calculated 
                     $blockedSeats = array_merge($blockedSeats,$seatIDsPerbooking);
                 } 
            }
        }else{          //no booking on that specific date, so all seats are available
                $blockedSeats=array();
        }
        
           $lowerBerth = Config::get('constants.LOWER_BERTH');
           $upperBerth = Config::get('constants.UPPER_BERTH');
           $viewSeat['bus']=$busRecord= $this->viewSeatsRepository->busRecord($busId);
            //////////////////////
            $clientRoleId = Config::get('constants.CLIENT_ROLE_ID');
            /////////////////////

           // Lower Berth seat Calculation
           $viewSeat['lower_berth']=$this->viewSeatsRepository->getBerth($busRecord[0]->bus_seat_layout_id,$lowerBerth,$busId,$blockedSeats,$journeyDate,$sourceId,$destinationId);
            //return $viewSeat;
           if(($viewSeat['lower_berth'])->isEmpty()){
               unset($viewSeat['lower_berth']);  
           }else{
               $rowsColumns = $this->viewSeatsRepository->seatRowColumn($busRecord[0]->bus_seat_layout_id, $lowerBerth);

               $viewSeat['lowerBerth_totalRows']=$rowsColumns->max('rowNumber')+1;       
               $viewSeat['lowerBerth_totalColumns']=$rowsColumns->max('colNumber')+1; 
           } 
          // Upper Berth seat Calculation
           $viewSeat['upper_berth']=$this->viewSeatsRepository->getBerth($busRecord[0]->bus_seat_layout_id,$upperBerth,$busId,$blockedSeats,$journeyDate,$sourceId,$destinationId);
        
           if(($viewSeat['upper_berth'])->isEmpty()){
               unset($viewSeat['upper_berth']); 
           }else{
               $rowsColumns = $this->viewSeatsRepository->seatRowColumn($busRecord[0]->bus_seat_layout_id, $upperBerth);
               
               $viewSeat['upperBerth_totalRows']=$rowsColumns->max('rowNumber')+1;       
               $viewSeat['upperBerth_totalColumns']=$rowsColumns->max('colNumber')+1;  
           }
                // Add Gender into Booked seat List
                    $i=0; 
                    if(isset($viewSeat['upper_berth'])){  
                      foreach($viewSeat['upper_berth'] as &$ub){
                        if(collect($ub)->has(['bus_seats'])){
                        // if(isset($ub->busSeats)){

                        $data = array(
                            'busId' => $busId,
                            'sourceId' => $sourceId,
                            'destinationId' => $destinationId,
                            'seater' => [],
                            'sleeper' => [$ub->id],
                            'entry_date' => $journeyDate,
                            'origin' => $origin
                        );
        
                        $ub->busSeats->PDetails = $this->getPriceOnSeatsSelection($data,$clientRole,$clientId);

                            $ub->busSeats->ticket_price = $this->viewSeatsRepository->busWithTicketPrice($sourceId,$destinationId,$busId);

                            $ub->busSeats->ticket_price->base_sleeper_fare+=$miscfares[1]+ $miscfares[3]+ $miscfares[5];                           

                            
                            $base_sleeper_fare=$ub->busSeats->ticket_price->base_sleeper_fare;

                            /////////// add odbus gst to seat fare

                            //$ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
                            $ticketFareSlabs = getTicketFareslab($busId,$journeyDate); // common.php
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $base_sleeper_fare && $uptoFare >= $base_sleeper_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($base_sleeper_fare * ($percentage/100));
                                    $ub->busSeats->ticket_price->base_sleeper_fare = nbf($base_sleeper_fare + $odbusServiceCharges);

                                    $ub->busSeats->ticket_price->base_seat_fare =0;
                                    }     
                                }
                            if($ub->busSeats->new_fare > 0){
                                $ub->busSeats->new_fare +=$miscfares[1]+ $miscfares[3]+ $miscfares[5];
                                $new_fare=$ub->busSeats->new_fare;

                                /////////// add odbus gst to seat fare
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $new_fare && $uptoFare >= $new_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($new_fare * ($percentage/100));
                                    $ub->busSeats->new_fare = nbf($new_fare + $odbusServiceCharges);
                                    }     
                                }
                            }
                            
                            //// changes by Lima 21 Nov,2022 (as per Rabti)
                            // if($clientRole == $clientRoleId){
                            //     unset($ub->busSeats->ticket_price);
                            //     unset($ub->busSeats->new_fare);
                            // } 
                        }
                        if (sizeof($bookingIds)){
                            if(in_array($ub['id'], $blockedSeats)){
                            //if(collect($blockedSeats)->has($ub['id'])){
                                $key = array_search($ub['id'], $seatsIds);
                                $viewSeat['upper_berth'][$i]['Gender'] =  $gender[$key];
                            } 
                        }
                        $i++;
                       }     
                    } 
                    $i=0;
                    if(isset($viewSeat['lower_berth'])){          
                      foreach($viewSeat['lower_berth'] as &$lb){    
                        if(collect($lb)->has(['bus_seats'])){                  
                        // if(isset($lb->busSeats)){ 
                        
                        $data = array(
                            'busId' => $busId,
                            'sourceId' => $sourceId,
                            'destinationId' => $destinationId,
                            'seater' => [$lb->id],
                            'sleeper' => [],
                            'entry_date' => $journeyDate,
                            'origin' => $origin
                        );
        
                        $lb->busSeats->PDetails = $this->getPriceOnSeatsSelection($data,$clientRole,$clientId);


                            $lb->busSeats->ticket_price = $this->viewSeatsRepository->busWithTicketPrice($sourceId,$destinationId,$busId);

                            if($lb->seat_class_id==2 && $lb->berthType==1){ // added by Lima 29-sep-2024
                               
                                $lb->busSeats->ticket_price->base_seat_fare =  $lb->busSeats->ticket_price->base_sleeper_fare + $miscfares[1]+$miscfares[3]+$miscfares[5] + $lower_sleeper_extra_fare;
                          

                            }else{
                                $lb->busSeats->ticket_price->base_seat_fare+=$miscfares[0]+ $miscfares[2]+ $miscfares[4];
                                
                            }
                           
                            $base_seat_fare=$lb->busSeats->ticket_price->base_seat_fare;
                           
                            /////////// add odbus gst to seat fare
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $base_seat_fare && $uptoFare >= $base_seat_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($base_seat_fare * ($percentage/100));
                                   // Log::Info($base_seat_fare);

                                    $lb->busSeats->ticket_price->base_seat_fare = nbf($base_seat_fare + $odbusServiceCharges);

                                    $lb->busSeats->ticket_price->base_sleeper_fare = 0;
                                    }     
                                }

                            if($lb->busSeats->new_fare > 0){
                              $lb->busSeats->new_fare += ($lb->seat_class_id==2 && $lb->berthType==1) ? ($miscfares[1]+$miscfares[3]+$miscfares[5] + $lower_sleeper_extra_fare )  : ($miscfares[0]+$miscfares[2]+$miscfares[4]); 


                               $new_fare= $lb->busSeats->new_fare;

                                 /////////// add odbus gst to seat fare

                           // $ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
                           $ticketFareSlabs = getTicketFareslab($busId,$journeyDate); // common.php
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $new_fare && $uptoFare >= $new_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($new_fare * ($percentage/100));
                                    $lb->busSeats->new_fare = nbf($new_fare + $odbusServiceCharges);
                                    }     
                                }           
                            } 
                            // if($clientRole == $clientRoleId){
                            //     unset($lb->busSeats->ticket_price);
                            //     unset($lb->busSeats->new_fare);
                            // }   
                        }
                        if (sizeof($bookingIds)){    
                            if(in_array($lb['id'], $blockedSeats)){
                                $key = array_search($lb['id'], $seatsIds);
                                $viewSeat['lower_berth'][$i]['Gender'] = $gender[$key];
                            } 
                        }
                        $i++;
                    } 
                  }
                  return $viewSeat;    
        }
    }

////////////////////////////////////////////////
public function checkBlockedSeats($request)
{
    $booked = Config::get('constants.BOOKED_STATUS');
    $seatHold = Config::get('constants.SEAT_HOLD_STATUS');

    $sourceId = $request['sourceId'];
    $destinationId = $request['destinationId'];
    $busId = $request['busId'];
    $journeyDate = $request['entry_date'];
    $journeyDate = date("Y-m-d", strtotime($journeyDate));

    $user_id = Bus::where('id', $busId)->first()->user_id;
    $requestedSeq = $this->viewSeatsRepository->busLocationSequence($sourceId,$destinationId,$busId);
    $reqRange = Arr::sort($requestedSeq);
    $bookingIds = $this->viewSeatsRepository->bookingIds($busId,$journeyDate,$booked,$seatHold,$sourceId,$destinationId);
    
    if (sizeof($bookingIds)){
        $blockedSeats=array();
        foreach($bookingIds as $bookingId){
            $seatIDsPerbooking = array();
            $bookedSeatIds = $this->viewSeatsRepository->bookingDetail($bookingId);
            foreach($bookedSeatIds as $bookedSeatId){
                $seatIDsPerbooking[] = $this->viewSeatsRepository->busSeats($bookedSeatId);
                $seatsIds[] = $this->viewSeatsRepository->busSeats($bookedSeatId);   
            }   
             $srcId=  $this->viewSeatsRepository->getSourceId($bookingId);
             $destId=  $this->viewSeatsRepository->getDestinationId($bookingId);
             $bookedSequence = $this->viewSeatsRepository->bookedSequence($srcId,$destId,$busId);
             $bookedRange = Arr::sort($bookedSequence);
             
             if((last($reqRange)<=head($bookedRange))){
                $blockedSeats=array();
             }
             elseif((last($bookedRange)<=head($reqRange))){  
                 $blockedSeats=array(); 
             }else{
                 //seat not available on requested seq so blocked seats are calculated 
                 $blockedSeats = array_merge($blockedSeats,$seatIDsPerbooking);
             } 
        }
    }else{          //no booking on that specific date, so all seats are available
            $blockedSeats=array();
    }
    return  $blockedSeats;
}
////////////////////////////////////////////


public function getPriceOnSeatsSelection($request,$clientRole,$clientId)
{
    
    $clientRoleId = Config::get('constants.CLIENT_ROLE_ID'); 
    $seaterIds = (isset($request['seater'])) ? $request['seater'] : [];
    $sleeperIds = (isset($request['sleeper'])) ? $request['sleeper'] : [];
    $busId = $request['busId'];
    $sourceId = $request['sourceId'];
    $destinationId = $request['destinationId'];
    $entry_date = $request['entry_date'];
    $entry_date = date("Y-m-d", strtotime($entry_date));
    //$busOperatorId = $request['busOperatorId'];
    $clientDetails = User::where('id', $clientId)->first();
    $ReferenceNumber = (isset($request['ReferenceNumber'])) ? $request['ReferenceNumber'] : '';
    $origin = (isset($request['origin'])) ? $request['origin'] : 'ODBUS';
    /// added 10-may-2025 for dolphin bus - customer gst added    
    $odbusCharges = $this->viewSeatsRepository->odbusCharges(1);
    ///////////////////////////////////////
    $seatWithPriceRecords=[];

    if($origin !='DOLPHIN' && $origin != 'ODBUS' && $origin != 'MANTIS'){
        return 'Invalid Origin';
    ////////////Mantis changes///////////////
    }else if($origin=='MANTIS'){
        $mantisSeatresult = $this->mantisTransformer->MantisSeatLayout($sourceId,$destinationId,$entry_date,$busId,$clientRole,$clientId);
        //return $mantisSeatresult;
        $total_fare = 0;
        $additional_charge = 0;
        $total_base_fare = 0;
        if(!empty($seaterIds)){
            foreach($seaterIds as $sId){
                $lbcollection = collect($mantisSeatresult['lower_berth']);
                $total_fare += $lbcollection->where('id', $sId)->pluck('bus_seats.new_fare')[0];
                $total_base_fare += $lbcollection->where('id', $sId)->pluck('bus_seats.mantis_base_fare')[0];
            }
          }
          if(!empty($sleeperIds)){
            foreach($sleeperIds as $slId){
                /////need to check//////
                $ubcollection = array_merge($mantisSeatresult['lower_berth'], $mantisSeatresult['upper_berth']);
                $total_fare += collect($ubcollection)->where('id', $slId)->pluck('bus_seats.new_fare')[0];
                $total_base_fare += collect($ubcollection)->where('id', $slId)->pluck('bus_seats.mantis_base_fare')[0];
            }  
          }  
          
          $mantis_data = IncomingApiCompany::where("name","MANTIS")->first();
          if($mantis_data){
             $additional_charge = $mantis_data->additional_charge;
          }

          if($clientRole == $clientRoleId){

            $total_fare = $total_fare + nbf($total_fare * ($additional_charge/100));

          $seatWithPriceRecords[] = array(
            "totalFare" => nbf($total_fare),
            "baseFare" => nbf($total_base_fare) ,
            "serviceCharge" => nbf($total_fare - $total_base_fare),
            "gst" => 0
            ); 

        }else{

         $seatWithPriceRecords[] = array(
             "baseFare" => nbf($total_base_fare),
             "ownerFare" => nbf($total_fare),
             "odbus_charges_ownerFare" => nbf($total_fare),
             "specialFare" => 0,
             "addOwnerFare" => 0,
             "festiveFare" => 0,
             "odbusServiceCharges" => 0,
             "transactionFee" => nbf($total_fare * ($additional_charge/100)), 
             "totalFare" =>  nbf(($total_fare + $total_fare * ($additional_charge/100))),
             "gst" => 0
             );

        }
            return $seatWithPriceRecords;
    }else if($origin=='DOLPHIN'){

        if($ReferenceNumber ==''){

            return 'ReferenceNumber_empty';

        }else{
              $seatResult= $this->dolphinTransformer->seatLayout($ReferenceNumber,$clientRole,$clientId);

            // return $seatResult; 

              $total_fare=0;

              if(!empty($seaterIds)){

                foreach($seaterIds as $st){

                    $key = array_search($st, array_column($seatResult['lower_berth'], 'id'));
                   $total_fare += $seatResult['lower_berth'][$key]['bus_seats']['new_fare'];

                }

              }


              if(!empty($sleeperIds)){

                foreach($sleeperIds as $sl){

                    $key2 = array_search($sl, array_column($seatResult['upper_berth'], 'id'));

                    $total_fare += $seatResult['upper_berth'][$key2]['bus_seats']['new_fare'];

                }
                
              }

        if($clientRole == $clientRoleId){
          $dolphinFare=  $total_fare ;//+ round($total_fare * (10/100)); // 10% extra as per santosh

                /////client extra service charge added to seatfare////////////////
               $clientCommissions = ClientFeeSlab::where('user_id', $clientId)
                                                   ->where('status', '1')
                                                   ->get(); 
                       
               $client_service_charges = 0;
               $addCharge = 0;
               if($clientCommissions){
                   foreach($clientCommissions as $clientCom){
                       $startFare = $clientCom->starting_fare;
                       $uptoFare = $clientCom->upto_fare;
                       if($dolphinFare >= $startFare && $dolphinFare <= $uptoFare){
                           $addCharge = $clientCom->dolphinaddationalCharges;
                           break;
                       }  
                   }   
               } 
               $client_service_charges = ($addCharge/100 * $dolphinFare);
              // $newSeatFare = $dolphinFare + $client_service_charges;
              $hasGst = $clientDetails->has_gst;
                $gst = 0;
                if($hasGst == 1){
                    $gst=$total_fare * 0.05 ;////gst calculation 5% on 30th JAN 2023
                }
               $seatWithPriceRecords[] = array(
                   "totalFare" => nbf($total_fare + $gst),
                   "baseFare" => nbf($total_fare - $client_service_charges) ,
                   "serviceCharge" => nbf($client_service_charges),
                   "gst" => nbf($gst)
                   ); 
           }else{ 

            $additional_charge=0;

             $dolphin_data= IncomingApiCompany::where("name","DOLPHIN")->first();

             if($dolphin_data){
                $additional_charge= $dolphin_data->additional_charge;
             }
             /// added on 10-may-2025 for dolphin bus customer gst
             $transactionFee= nbf($total_fare * ($additional_charge/100));

             $customer_gst = $odbusCharges[0]->customer_gst;  
             $customer_gst = nbf(($total_fare * $customer_gst)/100);
             $totalFare = nbf($total_fare + $transactionFee + $customer_gst);

            

            $seatWithPriceRecords[] = array(
                "ownerFare" => nbf($total_fare),
                "odbus_charges_ownerFare" => nbf($total_fare),
                "specialFare" => 0,
                "addOwnerFare" => 0,
                "festiveFare" => 0,
                "odbusServiceCharges" => 0,
                "transactionFee" =>$transactionFee ,
                "totalFare" =>   nbf($totalFare),
                "gst" => 0,
                "customerGst" => nbf($customer_gst)
                );
            }  

              return $seatWithPriceRecords;  


        }
    }else if($origin=='ODBUS'){

    $busDet = Bus::where('id', $busId)->first();
    $user_id =  $busDet->user_id; 
    $lower_sleeper_extra_fare=$busDet->lower_sleeper_extra_fare; 

    $miscfares = $this->viewSeatsRepository->miscFares($busId,$entry_date);

    $busWithTicketPrice = $this->viewSeatsRepository->busWithTicketPrice($sourceId, $destinationId,$busId);
    $ticket_new_fare=array();
    if($seaterIds){
        $ticket_new_fare[] = $this->viewSeatsRepository->newFare($seaterIds,$busId,$busWithTicketPrice->id);
    }             
    if($sleeperIds){
        $ticket_new_fare[] = $this->viewSeatsRepository->newFare($sleeperIds,$busId,$busWithTicketPrice->id);   
    }
    //$ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
    $ticketFareSlabs = getTicketFareslab($busId,$entry_date); // common.php

    $ownerFare=0;
    $odbus_charges_ownerFare=0;
    $totalSplFare =0;
    $totalOwnFare =0;
    $totalFestiveFare =0;
    $PriceDetail=[];
    $service_charges=0;

    if(count($ticket_new_fare) > 0){
        foreach($ticket_new_fare as $tktprc){

            foreach($tktprc as $tkt){
                if( $tkt->type==2 || ($tkt->type==null && $tkt->operation_date != null )){
                    // do nothing (this logic is to avoid extra seat block , seat block seats )
                }  else{
                    if($tkt->operation_date== $entry_date || $tkt->operation_date==null ){
                       
                        if($tkt->new_fare == 0 ){
                            if($seaterIds && in_array($tkt->seats_id,$seaterIds)){
                       
                                $tkt->new_fare =($tkt->seat_class_id==2 && $tkt->berthType==1) ? $busWithTicketPrice->base_sleeper_fare : $busWithTicketPrice->base_seat_fare;

                            }
                            else if($sleeperIds && in_array($tkt->seats_id,$sleeperIds)){                     
                              $tkt->new_fare = $busWithTicketPrice->base_sleeper_fare;
                                
                              //Log::Info('sleeper fare - '.$tkt->new_fare);
                            }

                           // Log::Info($busWithTicketPrice);


                            
                            array_push($PriceDetail,$tkt);
                        }
                        if($seaterIds && in_array($tkt->seats_id,$seaterIds)){
                            $totalSplFare +=($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[1] : $miscfares[0];
                            $totalOwnFare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[3] : $miscfares[2];
                            $totalFestiveFare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[5] : $miscfares[4];
                            $tkt->new_fare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? ($miscfares[1]+$miscfares[3]+$miscfares[5] + $lower_sleeper_extra_fare)  : ($miscfares[0]+$miscfares[2]+$miscfares[4])
                           ; 

                        }
                        else if($sleeperIds && in_array($tkt->seats_id,$sleeperIds)){
                            $totalSplFare +=$miscfares[1];
                            $totalOwnFare +=$miscfares[3];
                            $totalFestiveFare +=$miscfares[5];
                            $tkt->new_fare += $miscfares[1]+$miscfares[3]+$miscfares[5]; 
                        }
                        $seat_fare=$tkt->new_fare;
                        $ownerFare +=$tkt->new_fare;

                        ////////// add odbus service chanrges to seat fare

                        $odbusServiceCharges = 0;

                        
                       // Log::Info('price'.$seat_fare);

                        foreach($ticketFareSlabs as $ticketFareSlab){
                
                            $startingFare = $ticketFareSlab->starting_fare;
                            $uptoFare = $ticketFareSlab->upto_fare;
                            if($startingFare <= $seat_fare && $uptoFare >= $seat_fare){


                                $percentage = $ticketFareSlab->odbus_commision;
                                $odbusServiceCharges = nbf($seat_fare * ($percentage/100));                                
                                $tkt->new_fare = nbf($seat_fare + $odbusServiceCharges);
                                $service_charges += $odbusServiceCharges;

                                }     
                            } 

                           // Log::Info($tkt->new_fare);

                        $odbus_charges_ownerFare +=$tkt->new_fare; 
                    }                         
                }       
            }  
        }
    }
    $odbusServiceCharges = 0;
    $transactionFee = 0;
    $totalFare = $odbus_charges_ownerFare; 

    $odbusCharges = $this->viewSeatsRepository->odbusCharges($user_id);
    $gwCharges = $odbusCharges[0]->payment_gateway_charges + $odbusCharges[0]->email_sms_charges;
    $customer_gst = $odbusCharges[0]->customer_gst;   
    $transactionFee = nbf(($odbus_charges_ownerFare * $gwCharges)/100);
    $customer_gst = nbf(($odbus_charges_ownerFare * $customer_gst)/100);
    $totalFare = nbf($odbus_charges_ownerFare + $transactionFee + $customer_gst);

    if($clientRole == $clientRoleId){
         /////client extra service charge added to seatfare////////////////
        $clientCommissions = ClientFeeSlab::where('user_id', $clientId)
                                            ->where('status', '1')
                                            ->get(); 
                
        $client_service_charges = 0;
        $addCharge = 0;
        if($clientCommissions){
            foreach($clientCommissions as $clientCom){
                $startFare = $clientCom->starting_fare;
                $uptoFare = $clientCom->upto_fare;
                if($odbus_charges_ownerFare >= $startFare && $odbus_charges_ownerFare <= $uptoFare){
                    $addCharge = $clientCom->addationalcharges;
                    //Log::Info($addCharge);
                    break;
                }  
            }   
        } 
        $hasGst = $clientDetails->has_gst;
        $client_service_charges = ($addCharge/100 * $odbus_charges_ownerFare);
        $newSeatFare = $odbus_charges_ownerFare + $client_service_charges;
        $gst = 0;
        if($hasGst == 1){
            $gst=$newSeatFare * 0.05 ;  ////gst calculation 5% on 30th JAN 2023
        }
        $seatWithPriceRecords[] = array(
            "totalFare" => nbf($newSeatFare + $gst),
            "baseFare" => nbf($odbus_charges_ownerFare) ,
            "serviceCharge" => nbf($newSeatFare - $odbus_charges_ownerFare),
            "gst" => nbf($gst)
            ); 
    }else{
        $seatWithPriceRecords[] = array(
            "PriceDetail" => $PriceDetail,
            "ownerFare" => nbf($ownerFare - $totalOwnFare),
            "odbus_charges_ownerFare" => nbf($odbus_charges_ownerFare),
            "specialFare" => nbf($totalSplFare),
            "addOwnerFare" => nbf($totalOwnFare),
            "festiveFare" => nbf($totalFestiveFare),
            "odbusServiceCharges" => nbf($service_charges),
            "transactionFee" => nbf($transactionFee),
            "totalFare" =>  nbf($totalFare),
            "customerGst" => nbf($customer_gst)
            );    
    }
   // Log::info($seatWithPriceRecords);
    return $seatWithPriceRecords;
   }
    
}

public function getBoardingDroppingPoints(Request $request,$clientRole,$clientId)
{
    $busId = $request['busId'];
    $sourceId = $request['sourceId'];
    $destinationId = $request['destinationId'];
    $journey_date = $request['journey_date'];
    $ReferenceNumber = $request['ReferenceNumber'];
    $origin = $request['origin'];
    
    $boardingArray=[];
    $droppingArray=[];
    $boardingDroppings = array(); 

    $destination_row=$this->viewSeatsRepository->getLocationName($destinationId);
    $destination_name=$destination_row[0]->name;
   
    if($origin !='DOLPHIN' && $origin != 'ODBUS' && $origin != 'MANTIS' ){
        return 'Invalid Origin';
    ////////////Mantis changes///////////////
    }else if($origin=='MANTIS'){
        $arr = [
            "sourceID" => $sourceId,
            "destinationID" => $destinationId,
            "entry_date" => $journey_date
        ];
        $mantisBusList = $this->mantisTransformer->Filter($arr,$clientRole,$clientId);
        $mantisBusList = (isset($mantisBusList['regular'])) ? $mantisBusList['regular'] : [];
    
        $key = array_search($busId, array_column($mantisBusList, 'busId'));
        $boarding_arr = explode("#",Str::after($mantisBusList[$key]['BoardingPoints'],'#'));
        $dropping_arr = explode("#",Str::after($mantisBusList[$key]['DroppingPoints'],'#'));

        if($boarding_arr){
            foreach($boarding_arr as $b){
                $b_ar = explode("|",$b);
                 $boardingArray[]=[
                    "id"=> $b_ar[0],
                    "boardingPoints"=> $b_ar[1],
                    "boardingTimes"=> date('H:i',strtotime($b_ar[2]))
                 ];
            }                    
         }
        if($dropping_arr){
            foreach($dropping_arr as $d){
                $d_ar = explode("|",$d);
                 $droppingArray[]=[
                    "id" => $d_ar[0],
                    "droppingPoints" => $d_ar[1],
                    "droppingTimes" => date('H:i',strtotime($d_ar[2]))
                 ];
            }                    
        } 
        if(!empty($boardingArray) && !empty($droppingArray)){

            $boardingDroppings[] = array(   
                "boardingPoints" => $boardingArray,
                "droppingPoints" => $droppingArray,
            ); 
            return $boardingDroppings;
        }else{
            return '';
        }      
    }else if($origin=='DOLPHIN'){

        if($ReferenceNumber ==''){

            return 'ReferenceNumber_empty';

        }else{
            $arr=[
                "sourceID"=>$sourceId,
                "destinationID"=>$destinationId,
                "entry_date"=>$journey_date
            ];

           

             $dolphinBusList= $this->dolphinTransformer->Filter($arr,$clientRole,$clientId);

             $dolphinBusList=(isset($dolphinBusList['regular'])) ? $dolphinBusList['regular'] : [];

             

             $key = array_search($ReferenceNumber, array_column($dolphinBusList, 'ReferenceNumber'));

             $b_ar=[];
             
             if($key > -1){
             
                $b_ar=explode("#",$dolphinBusList[$key]['BoardingPoints']);
                
                if($dolphinBusList[$key]['DroppingPoints']){
  
                  $d_ar=explode("#",$dolphinBusList[$key]['DroppingPoints']);
  
                }

             }

            
             

             if($b_ar){
                foreach($b_ar as $b){
                    $b_ar2=explode("|",$b);
                     $boardingArray[]=[
                        "id"=> $b_ar2[0],
                        "boardingPoints"=> $b_ar2[1],
                        "boardingTimes"=> date('H:i',strtotime($b_ar2[2]))
                     ];
                }                    
             }

             if(isset($d_ar)){
                foreach($d_ar as $d){
                    $d_ar2=explode("|",$d);
                     $droppingArray[]=[
                        "id"=> $d_ar2[0],
                        "droppingPoints"=> $d_ar2[1],
                        "droppingTimes"=> date('H:i',strtotime($d_ar2[2]))
                     ];
                }                    
             }

            if(!empty($boardingArray)){

                if(empty($droppingArray)){
                    $droppingArray[]=  [
                        "id"=> 0,
                        "droppingPoints"=> $destination_name,
                        "droppingTimes"=> date('H:i',strtotime($dolphinBusList[$key]['arrivalTime']))
                     ];
                }

                $boardingDroppings[] = array(   
                    "boardingPoints" => $boardingArray,
                    "droppingPoints" => $droppingArray,
                ); 
              
                return $boardingDroppings;

            }else{
                return '';
            }
             
            
        }
    }else if($origin=='ODBUS'){

        $Records =  $this->viewSeatsRepository->busStoppageTiming($busId);

    
    if($Records) {
    foreach($Records as $Record){  
        if($Record->boardingDroping != null){
            $boardingPoints = $Record->boardingDroping->boarding_point;
            $boardingDroppingTimes = $Record->stoppage_time;
            $boardingDroppingTimes = date("H:i",strtotime($boardingDroppingTimes));
            $locationId = $Record->boardingDroping->location_id;
            $boardDropId = $Record->boardingDroping->id;
            if($locationId==$sourceId)
            {
                $boardingArray[] = array(
                    "id" =>  $boardDropId,
                    "boardingPoints" => $boardingPoints,
                    "boardingTimes" => $boardingDroppingTimes,
                );
            }
            elseif($locationId==$destinationId)
            {
                $droppingArray[] = array(
                    "id" =>  $boardDropId,
                    "droppingPoints" => $boardingPoints,
                    "droppingTimes" => $boardingDroppingTimes,
            );
        }                
    }
    }

        $boardingDroppings[] = array(   
        "boardingPoints" => $boardingArray,
        "droppingPoints" => $droppingArray,
    );  
    return $boardingDroppings;

   }
   else{
        return '';
    }

  }


}

 //////////////////used for client API booking///////////////

 public function DolphinPriceCalculation($request,$clientRole,$clientId)
 {
    $clientDetails = User::where('id', $clientId)->first();

    $clientRoleId = Config::get('constants.CLIENT_ROLE_ID');
    $seaterIds = (isset($request['seater'])) ? $request['seater'] : [];
    $sleeperIds = (isset($request['sleeper'])) ? $request['sleeper'] : [];
    $busId = $request['busId'];
    $sourceId = $request['sourceId'];
    $destinationId = $request['destinationId'];
    $entry_date = $request['entry_date'];
    $entry_date = date("Y-m-d", strtotime($entry_date));
    //$busOperatorId = $request['busOperatorId'];
       $ReferenceNumber = (isset($request['ReferenceNumber'])) ? $request['ReferenceNumber'] : '';
        $origin = (isset($request['origin'])) ? $request['origin'] : 'ODBUS';

        $seatWithPriceRecords=[];

        $seatResult= $this->dolphinTransformer->seatLayout($ReferenceNumber,$clientRole,$clientId);

            // return $seatResult; 

              $total_fare=0;

              if(!empty($seaterIds)){

                foreach($seaterIds as $st){

                    $key = array_search($st, array_column($seatResult['lower_berth'], 'id'));
                   $total_fare += $seatResult['lower_berth'][$key]['bus_seats']['new_fare'];

                }

              }


              if(!empty($sleeperIds)){

                foreach($sleeperIds as $sl){

                    $key2 = array_search($sl, array_column($seatResult['upper_berth'], 'id'));

                    $total_fare += $seatResult['upper_berth'][$key2]['bus_seats']['new_fare'];

                }
                
              }


          $dolphinFare=  $total_fare ;//+ round($total_fare * (10/100)); // 10% extra as per santosh

                /////client extra service charge added to seatfare////////////////
               $clientCommissions = ClientFeeSlab::where('user_id', $clientId)
                                                   ->where('status', '1')
                                                   ->get(); 
                       
               $client_service_charges = 0;
               $addCharge = 0;
               if($clientCommissions){
                   foreach($clientCommissions as $clientCom){
                       $startFare = $clientCom->starting_fare;
                       $uptoFare = $clientCom->upto_fare;
                       if($dolphinFare >= $startFare && $dolphinFare <= $uptoFare){
                           $addCharge = $clientCom->dolphinaddationalCharges;
                           break;
                       }  
                   }   
               } 
             
               $client_service_charges = ($addCharge/100 * $dolphinFare);

        /// below code is to get actual dolphin seat price By Lima (21 Oct,2022)
        $ActualSeatResult= $this->dolphinTransformer->seatLayout($ReferenceNumber,$clientRole,$clientId,true);

              $owner_fare=0;

              if(!empty($seaterIds)){

                foreach($seaterIds as $st){
                    $key = array_search($st, array_column($ActualSeatResult['lower_berth'], 'id'));
                   $owner_fare += $ActualSeatResult['lower_berth'][$key]['bus_seats']['new_fare'];

                }

              }


              if(!empty($sleeperIds)){

                foreach($sleeperIds as $sl){

                    $key2 = array_search($sl, array_column($ActualSeatResult['upper_berth'], 'id'));

                    $owner_fare += $ActualSeatResult['upper_berth'][$key2]['bus_seats']['new_fare'];

                }
                
              }


              $gst = 0;

              $hasGst = $clientDetails->has_gst;
        
              if($hasGst == 1){
                $gst=$total_fare * 0.05 ;////gst calculation 5% on 30th JAN 2023
              }

              
              // $newSeatFare = $dolphinFare + $client_service_charges;
               $seatWithPriceRecords[] = array(
                   "totalFare" => nbf($total_fare + $gst),
                   "baseFare" => nbf($total_fare - $client_service_charges) ,
                   "serviceCharge" => nbf($client_service_charges),
                   "ownerFare" => nbf($owner_fare), // actual dolphin fare
                    "odbus_charges_ownerFare" => nbf($owner_fare),
                    "specialFare" => 0,
                    "addOwnerFare" => 0,
                    "festiveFare" => 0,
                    "odbusServiceCharges" => 0,
                    "gst" => nbf($gst)
                   ); 

        return $seatWithPriceRecords;  

     
 }

    //////////////////used for client API booking///////////////

    public function getPriceCalculation($request,$clientId)
    {
       
        $seaterIds = (isset($request['seater'])) ? $request['seater'] : [];
        $sleeperIds = (isset($request['sleeper'])) ? $request['sleeper'] : [];
        
        $busId = $request['busId'];
        $sourceId = $request['sourceId'];
        $destinationId = $request['destinationId'];
        $entry_date = $request['entry_date'];
     
        $entry_date = date("Y-m-d", strtotime($entry_date));
        
        //$busOperatorId = $request['busOperatorId'];
        $clientDetails = User::where('id', $clientId)->first();
        $busDet = Bus::where('id', $busId)->first();
        $user_id =  $busDet->user_id; 
        $lower_sleeper_extra_fare=$busDet->lower_sleeper_extra_fare; 
    
        $miscfares = $this->viewSeatsRepository->miscFares($busId,$entry_date);
    
        $busWithTicketPrice = $this->viewSeatsRepository->busWithTicketPrice($sourceId, $destinationId,$busId);
        $ticket_new_fare=array();
        if($seaterIds){
            $ticket_new_fare[] = $this->viewSeatsRepository->newFare($seaterIds,$busId,$busWithTicketPrice->id);
        }             
        if($sleeperIds){
            $ticket_new_fare[] = $this->viewSeatsRepository->newFare($sleeperIds,$busId,$busWithTicketPrice->id);   
        }
       // $ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
       $ticketFareSlabs = getTicketFareslab($busId,$entry_date); // common.php
    
        $ownerFare=0;
        $odbus_charges_ownerFare=0;
        $totalSplFare =0;
        $totalOwnFare =0;
        $totalFestiveFare =0;
        $PriceDetail=[];
        $service_charges=0;
        $baseFare = 0;
        $tktprc = Arr::flatten($ticket_new_fare);
    
        $collectionSeater = collect($seaterIds);
        $collectionSleeper = collect($sleeperIds);
    
        if(count($tktprc) > 0){
                foreach($tktprc as $tkt){
                    if( $tkt->type==2 || ($tkt->type==null && $tkt->operation_date != null )){
                        // do nothing (this logic is to avoid extra seat block , seat block seats )
                    }  else{
                        if($tkt->operation_date== $entry_date || $tkt->operation_date==null ){
                            
                            if($tkt->new_fare == 0 ){
     
                                if($collectionSeater && $collectionSeater->contains($tkt->seats_id))
                                {
                                    $tkt->new_fare =($tkt->seat_class_id==2 && $tkt->berthType==1) ? $busWithTicketPrice->base_sleeper_fare + $lower_sleeper_extra_fare: $busWithTicketPrice->base_seat_fare;

                                   // $tkt->new_fare = $busWithTicketPrice->base_seat_fare;
                                    $baseFare = ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $busWithTicketPrice->base_sleeper_fare + $lower_sleeper_extra_fare : $busWithTicketPrice->base_seat_fare;
                                }
    
                                else if($collectionSleeper && $collectionSleeper->contains($tkt->seats_id))
                                {
                                    $tkt->new_fare = $busWithTicketPrice->base_sleeper_fare;
                                    $baseFare = $busWithTicketPrice->base_sleeper_fare;
                                }
                                array_push($PriceDetail,$tkt);
                            }
                            
                            if($collectionSeater && $collectionSeater->contains($tkt->seats_id)){
                               
                                $totalSplFare +=($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[1] : $miscfares[0];
                                $totalOwnFare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[3] : $miscfares[2];
                                $totalFestiveFare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[5] : $miscfares[4];

                                $baseFare=$tkt->new_fare;
                                
                                if($tkt->seat_class_id==2 && $tkt->berthType==1){
                                    $ownerFare += $baseFare+ $miscfares[3];
                                }else{
                                    $ownerFare += $baseFare+ $miscfares[2];
                                }
                                
                                $tkt->new_fare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? ($miscfares[1]+$miscfares[3]+$miscfares[5] )  : ($miscfares[0]+$miscfares[2]+$miscfares[4])
                               ; 
                            }
                            else if($collectionSleeper && $collectionSleeper->contains($tkt->seats_id)){
                                
                                $totalSplFare +=$miscfares[1];
                                $totalOwnFare +=$miscfares[3];
                                $totalFestiveFare +=$miscfares[5];
                                $ownerFare += $baseFare+$miscfares[3];
                                $tkt->new_fare +=$miscfares[1]+$miscfares[3]+$miscfares[5]; 
                            }
                          
                            $seat_fare=$tkt->new_fare;
                            //$ownerFare +=$tkt->new_fare;
    
                            ////////// add odbus service chanrges to seat fare
    
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                    
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $seat_fare && $uptoFare >= $seat_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($seat_fare * ($percentage/100));                                
                                    $tkt->new_fare = nbf($seat_fare + $odbusServiceCharges);
                                    $service_charges += $odbusServiceCharges;
                                    }     
                                } 
                            $odbus_charges_ownerFare +=$tkt->new_fare; 
                        }                        
                    }       
                }  
        }
        $transactionFee = 0;
        
        /////client extra service charge added to seatfare////////////////
        $clientCommissions = ClientFeeSlab::where('user_id', $clientId)
                                            ->where('status', '1')
                                            ->get(); 
    
        $client_service_charges = 0;
        $addCharge = 0;
            if($clientCommissions){
                foreach($clientCommissions as $clientCom){
                $startFare = $clientCom->starting_fare;
                $uptoFare = $clientCom->upto_fare;
                    if($odbus_charges_ownerFare >= $startFare && $odbus_charges_ownerFare <= $uptoFare){
                    $addCharge = $clientCom->addationalcharges;
                    break;
                    }  
                }   
            } 
        $hasGst = $clientDetails->has_gst;
        $gst = 0;
        $client_service_charges = ($addCharge/100 * $odbus_charges_ownerFare);
        $newSeatFare = $odbus_charges_ownerFare + $client_service_charges;
        if($hasGst == 1){
            $gst=$newSeatFare * 0.05 ;////gst calculation 5% on 30th JAN 2023
        }
    
        //$odbusCharges = $this->viewSeatsRepository->odbusCharges($user_id);
        //$gwCharges = $odbusCharges[0]->payment_gateway_charges + $odbusCharges[0]->email_sms_charges;
        //$transactionFee = round(($odbus_charges_ownerFare * $gwCharges)/100,2);
        //$totalFare = round($odbus_charges_ownerFare + $transactionFee,2);
    
        $seatWithPriceRecords[] = array(
                //"PriceDetail" => $PriceDetail,
                "ownerFare" =>nbf($ownerFare - $totalOwnFare),
                "odbus_charges_ownerFare" => nbf($odbus_charges_ownerFare),
                "specialFare" => nbf($totalSplFare),
                "addOwnerFare" => nbf($totalOwnFare),
                "festiveFare" => nbf($totalFestiveFare),
                "odbusServiceCharges" => nbf($service_charges + $client_service_charges),
                //"transactionFee" => $transactionFee,
                "totalFare" => nbf($newSeatFare + $gst),
                "gst" => nbf($gst)
                ); 
    
        return $seatWithPriceRecords;
        
    }


     //////////////////used for client API booking///////////////

 public function MantisPriceCalculation($request,$clientRole,$clientId)
 {
    $clientDetails = User::where('id', $clientId)->first();

    $clientRoleId = Config::get('constants.CLIENT_ROLE_ID');
    $seaterIds = (isset($request['seater'])) ? $request['seater'] : [];
    $sleeperIds = (isset($request['sleeper'])) ? $request['sleeper'] : [];
    $busId = $request['busId'];
    $sourceId = $request['sourceId'];
    $destinationId = $request['destinationId'];
    $entry_date = $request['entry_date'];
    $entry_date = date("Y-m-d", strtotime($entry_date));
    //$busOperatorId = $request['busOperatorId'];
      
        $origin = (isset($request['origin'])) ? $request['origin'] : 'ODBUS';

        $seatWithPriceRecords=[];

        $mantisSeatresult = $this->mantisTransformer->MantisSeatLayout($sourceId,$destinationId,$entry_date,$busId,$clientRole,$clientId);
        //return $mantisSeatresult;
        $total_fare = 0;
        $additional_charge = 0;
        $total_base_fare = 0;
        if(!empty($seaterIds)){
            foreach($seaterIds as $sId){
                $lbcollection = collect($mantisSeatresult['lower_berth']);
                $total_fare += $lbcollection->where('id', $sId)->pluck('bus_seats.new_fare')[0];
                $total_base_fare += $lbcollection->where('id', $sId)->pluck('bus_seats.mantis_base_fare')[0];
            }
          }
          if(!empty($sleeperIds)){
            foreach($sleeperIds as $slId){
                /////need to check//////
                $ubcollection = array_merge($mantisSeatresult['lower_berth'], $mantisSeatresult['upper_berth']);
                $total_fare += collect($ubcollection)->where('id', $slId)->pluck('bus_seats.new_fare')[0];
                $total_base_fare += collect($ubcollection)->where('id', $slId)->pluck('bus_seats.mantis_base_fare')[0];
            }  
          }  
          
          $mantis_data = IncomingApiCompany::where("name","MANTIS")->first();
          if($mantis_data){
             $additional_charge = $mantis_data->additional_charge;
          }

          $seatWithPriceRecords[] = array(
             "baseFare" => nbf($total_base_fare),
             "ownerFare" => nbf($total_fare),
             "odbus_charges_ownerFare" => nbf($total_fare),
             "specialFare" => 0,
             "addOwnerFare" => 0,
             "festiveFare" => 0,
             "odbusServiceCharges" => 0,
             "transactionFee" => nbf($total_fare * ($additional_charge/100)), 
             "totalFare" =>  nbf($total_fare +$total_fare * ($additional_charge/100)),
             "gst" => 0
             );

        return $seatWithPriceRecords;  

     
 }

    

    //////////////////used for ODBUS booking PRICE CALCULATION ///////////////

    public function getPriceCalculationOdbus($request,$clientId)
    {       
        $seaterIds = (isset($request['seater'])) ? $request['seater'] : [];
        $sleeperIds = (isset($request['sleeper'])) ? $request['sleeper'] : [];
        
        $busId = $request['busId'];
        $sourceId = $request['sourceId'];
        $destinationId = $request['destinationId'];
        $entry_date = $request['entry_date'];
     
        $entry_date = date("Y-m-d", strtotime($entry_date));

        $busDet = Bus::where('id', $busId)->first();
        $user_id =  $busDet->user_id; 
        $lower_sleeper_extra_fare=$busDet->lower_sleeper_extra_fare; 

        $miscfares = $this->viewSeatsRepository->miscFares($busId,$entry_date);
    
        $busWithTicketPrice = $this->viewSeatsRepository->busWithTicketPrice($sourceId, $destinationId,$busId);
        $ticket_new_fare=array();
        if($seaterIds){
            $ticket_new_fare[] = $this->viewSeatsRepository->newFare($seaterIds,$busId,$busWithTicketPrice->id);
        }             
        if($sleeperIds){
            $ticket_new_fare[] = $this->viewSeatsRepository->newFare($sleeperIds,$busId,$busWithTicketPrice->id);   
        }
      //  $ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);

      $ticketFareSlabs = getTicketFareslab($busId,$entry_date); // common.php
    
        $ownerFare=0;
        $odbus_charges_ownerFare=0;
        $totalSplFare =0;
        $totalOwnFare =0;
        $totalFestiveFare =0;
        $PriceDetail=[];
        $service_charges=0;
        $baseFare = 0;
        $tktprc = Arr::flatten($ticket_new_fare);
    
        $collectionSeater = collect($seaterIds);
        $collectionSleeper = collect($sleeperIds);
    
        if(count($tktprc) > 0){
                foreach($tktprc as $tkt){
                    if( $tkt->type==2 || ($tkt->type==null && $tkt->operation_date != null )){
                        // do nothing (this logic is to avoid extra seat block , seat block seats )
                    }  else{
                        if($tkt->operation_date== $entry_date || $tkt->operation_date==null ){
                            
                            if($tkt->new_fare == 0 ){
     
                                if($collectionSeater && $collectionSeater->contains($tkt->seats_id))
                                {

                                    $tkt->new_fare =($tkt->seat_class_id==2 && $tkt->berthType==1) ? $busWithTicketPrice->base_sleeper_fare + $lower_sleeper_extra_fare: $busWithTicketPrice->base_seat_fare;

                                   // $tkt->new_fare = $busWithTicketPrice->base_seat_fare;
                                    $baseFare = ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $busWithTicketPrice->base_sleeper_fare + $lower_sleeper_extra_fare: $busWithTicketPrice->base_seat_fare;
                                }
    
                                else if($collectionSleeper && $collectionSleeper->contains($tkt->seats_id))
                                {
                                    $tkt->new_fare = $busWithTicketPrice->base_sleeper_fare;
                                    $baseFare = $busWithTicketPrice->base_sleeper_fare;
                                }
                                array_push($PriceDetail,$tkt);
                            }

                            if($collectionSeater && $collectionSeater->contains($tkt->seats_id)){
                               
                                $totalSplFare +=($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[1] : $miscfares[0];
                                $totalOwnFare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[3] : $miscfares[2];
                                $totalFestiveFare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? $miscfares[5] : $miscfares[4];

                                $baseFare=$tkt->new_fare;
                                if($tkt->seat_class_id==2 && $tkt->berthType==1){
                                    $ownerFare += $baseFare+ $miscfares[3];
                                }else{
                                    $ownerFare += $baseFare+ $miscfares[2];
                                }
                                $tkt->new_fare += ($tkt->seat_class_id==2 && $tkt->berthType==1) ? ($miscfares[1]+$miscfares[3]+$miscfares[5])  : ($miscfares[0]+$miscfares[2]+$miscfares[4])
                               ;  // extra fare  + $lower_sleeper_extra_fare should not add :: 12-jan-2025

                            }
                            else if($collectionSleeper && $collectionSleeper->contains($tkt->seats_id)){ 
                                $totalSplFare +=$miscfares[1];
                                $totalOwnFare +=$miscfares[3];
                                $totalFestiveFare +=$miscfares[5];
                                $ownerFare += $baseFare+$miscfares[3];
                                $tkt->new_fare +=$miscfares[1]+$miscfares[3]+$miscfares[5]; 
                            }
                            $seat_fare=$tkt->new_fare;
                            //$ownerFare +=$tkt->new_fare;
    
                            ////////// add odbus service chanrges to seat fare
    
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                    
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $seat_fare && $uptoFare >= $seat_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($seat_fare * ($percentage/100));                                
                                    $tkt->new_fare = nbf($seat_fare + $odbusServiceCharges);
                                    $service_charges += $odbusServiceCharges;
                                    }     
                                } 

                               // Log::info('new fare - '.$tkt->new_fare);
                            $odbus_charges_ownerFare +=$tkt->new_fare; 
                        }                        
                    }       
                }  
        }
        $transactionFee = 0;
    
        $odbusCharges = $this->viewSeatsRepository->odbusCharges($user_id);
         $gwCharges = $odbusCharges[0]->payment_gateway_charges + $odbusCharges[0]->email_sms_charges;
        $customer_gst = $odbusCharges[0]->customer_gst;
        $transactionFee = nbf(($odbus_charges_ownerFare * $gwCharges)/100);
        $customer_gst = nbf(($odbus_charges_ownerFare * $customer_gst)/100);
        $totalFare = nbf($odbus_charges_ownerFare + $transactionFee + $customer_gst );

       
        $seatWithPriceRecords[] = array(
                //"PriceDetail" => $PriceDetail,
                "ownerFare" => nbf($ownerFare - $totalOwnFare),
                "odbus_charges_ownerFare" => nbf($odbus_charges_ownerFare),
                "specialFare" => nbf($totalSplFare),
                "addOwnerFare" => nbf($totalOwnFare),
                "festiveFare" => nbf($totalFestiveFare),
                "odbusServiceCharges" => nbf($service_charges),
                "transactionFee" => nbf($transactionFee),
                "customerGst" => nbf($customer_gst),
                "totalFare" => nbf($totalFare)
                ); 
    
        return $seatWithPriceRecords;
        
    }
    /////////////for client api use(not in use)///////////////////
    public function checkSeatStatus($request,$clientRole,$clientId)
    {
        $booked = Config::get('constants.BOOKED_STATUS');
        $seatHold = Config::get('constants.SEAT_HOLD_STATUS');
        $lowerBerth = Config::get('constants.LOWER_BERTH');
        $upperBerth = Config::get('constants.UPPER_BERTH');
    
        $sourceId = $request['sourceId'];
        $destinationId = $request['destinationId'];
        $busId = $request['busId'];
        $journeyDate = $request['entry_date'];
        $journeyDate = date("Y-m-d", strtotime($journeyDate));
    
        $user_id = Bus::where('id', $busId)->first()->user_id;
        $miscfares = $this->viewSeatsRepository->miscFares($busId,$journeyDate);
        $requestedSeq = $this->viewSeatsRepository->busLocationSequence($sourceId,$destinationId,$busId);
    
        $reqRange = Arr::sort($requestedSeq);
        $bookingIds = $this->viewSeatsRepository->bookingIds($busId,$journeyDate,$booked,$seatHold,$sourceId,$destinationId);
    
       // $ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
       $ticketFareSlabs = getTicketFareslab($busId,$journeyDate); // common.php
        
        if (sizeof($bookingIds)){
            $blockedSeats=array();
            foreach($bookingIds as $bookingId){
                $bookedSeatIds = $this->viewSeatsRepository->bookingDetail($bookingId);
                foreach($bookedSeatIds as $bookedSeatId){
                    $seatsIds[] = $this->viewSeatsRepository->busSeats($bookedSeatId);
                    $gender[] = $this->viewSeatsRepository->bookingGenderDetail($bookingId,$bookedSeatId);     
                }   
                 $srcId=  $this->viewSeatsRepository->getSourceId($bookingId);
                 $destId=  $this->viewSeatsRepository->getDestinationId($bookingId);
                 $bookedSequence = $this->viewSeatsRepository->bookedSequence($srcId,$destId,$busId);
                 $bookedRange = Arr::sort($bookedSequence);
    
                //seat not available on requested seq so blocked seats are calculated 
                if((last($reqRange)>head($bookedRange)) || (last($bookedRange)>($reqRange))){
                    $blockedSeats = array_merge($blockedSeats,$seatsIds);
                 }
            }
        }else{          //no booking on that specific date, so all seats are available
                $blockedSeats=array();
        }
    
           $lowerBerth = Config::get('constants.LOWER_BERTH');
           $upperBerth = Config::get('constants.UPPER_BERTH');
           $viewSeat['bus']=$busRecord= $this->viewSeatsRepository->busRecord($busId);
    
    
           // Lower Berth seat Calculation
           $viewSeat['lower_berth']=$this->viewSeatsRepository->getBerth($busRecord[0]->bus_seat_layout_id,$lowerBerth,$busId,$blockedSeats,$journeyDate,$sourceId,$destinationId);
            //return $viewSeat;
           if(($viewSeat['lower_berth'])->isEmpty()){
               unset($viewSeat['lower_berth']);  
           }else{
               $rowsColumns = $this->viewSeatsRepository->seatRowColumn($busRecord[0]->bus_seat_layout_id, $lowerBerth);
    
               $viewSeat['lowerBerth_totalRows']=$rowsColumns->max('rowNumber')+1;       
               $viewSeat['lowerBerth_totalColumns']=$rowsColumns->max('colNumber')+1; 
           } 
          // Upper Berth seat Calculation
           $viewSeat['upper_berth']=$this->viewSeatsRepository->getBerth($busRecord[0]->bus_seat_layout_id,$upperBerth,$busId,$blockedSeats,$journeyDate,$sourceId,$destinationId);
        
           if(($viewSeat['upper_berth'])->isEmpty()){
               unset($viewSeat['upper_berth']); 
           }else{
               $rowsColumns = $this->viewSeatsRepository->seatRowColumn($busRecord[0]->bus_seat_layout_id, $upperBerth);
               
               $viewSeat['upperBerth_totalRows']=$rowsColumns->max('rowNumber')+1;       
               $viewSeat['upperBerth_totalColumns']=$rowsColumns->max('colNumber')+1;  
           }
                // Add Gender into Booked seat List
                    $i=0; 
                    if(isset($viewSeat['upper_berth'])){  
                      foreach($viewSeat['upper_berth'] as &$ub){
                        if(collect($ub)->has(['bus_seats'])){
                        // if(isset($ub->busSeats)){
                            $ub->busSeats->ticket_price = $this->viewSeatsRepository->busWithTicketPrice($sourceId,$destinationId,$busId);
    
                            $ub->busSeats->ticket_price->base_sleeper_fare+=$miscfares[1]+ $miscfares[3]+ $miscfares[5];
                            
                            $base_sleeper_fare=$ub->busSeats->ticket_price->base_sleeper_fare;
    
                            /////////// add odbus gst to seat fare
    
                           // $ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
                           $ticketFareSlabs = getTicketFareslab($busId,$journeyDate); // common.php

                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $base_sleeper_fare && $uptoFare >= $base_sleeper_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($base_sleeper_fare * ($percentage/100));
                                    $ub->busSeats->ticket_price->base_sleeper_fare = nbf($base_sleeper_fare + $odbusServiceCharges);
                                    }     
                                }
                            if($ub->busSeats->new_fare > 0){
                                $ub->busSeats->new_fare +=$miscfares[1]+ $miscfares[3]+ $miscfares[5];
                                $new_fare=$ub->busSeats->new_fare;
                                /////////// add odbus gst to seat fare
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $new_fare && $uptoFare >= $new_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($new_fare * ($percentage/100));
                                    $ub->busSeats->new_fare = nbf($new_fare + $odbusServiceCharges);
                                    }     
                                }
                            }   
                        }
                        if (sizeof($bookingIds)){
                            if(in_array($ub['id'], $blockedSeats)){
                                $key = array_search($ub['id'], $seatsIds);
                                $viewSeat['upper_berth'][$i]['Gender'] =  $gender[$key];
                            } 
                        }
                        $i++;
                       }     
                    } 
                    $i=0;
                    if(isset($viewSeat['lower_berth'])){          
                      foreach($viewSeat['lower_berth'] as &$lb){    
                        if(collect($lb)->has(['bus_seats'])){                                           
                            $lb->busSeats->ticket_price = $this->viewSeatsRepository->busWithTicketPrice($sourceId,$destinationId,$busId);
                            $lb->busSeats->ticket_price->base_seat_fare+=$miscfares[0]+ $miscfares[2]+ $miscfares[4];
    
                            $base_seat_fare=$lb->busSeats->ticket_price->base_seat_fare;
                            /////////// add odbus gst to seat fare
                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $base_seat_fare && $uptoFare >= $base_seat_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($base_seat_fare * ($percentage/100));
                                    $lb->busSeats->ticket_price->base_seat_fare = nbf($base_seat_fare + $odbusServiceCharges);
                                    }     
                                }
                            if($lb->busSeats->new_fare > 0){
                                $lb->busSeats->new_fare +=$miscfares[0]+ $miscfares[2]+ $miscfares[4];
    
                               $new_fare= $lb->busSeats->new_fare;
    
                                 /////////// add odbus gst to seat fare
    
                            //$ticketFareSlabs = $this->viewSeatsRepository->ticketFareSlab($user_id);
                           $ticketFareSlabs = getTicketFareslab($busId,$journeyDate); // common.php

                            $odbusServiceCharges = 0;
                            foreach($ticketFareSlabs as $ticketFareSlab){
                                $startingFare = $ticketFareSlab->starting_fare;
                                $uptoFare = $ticketFareSlab->upto_fare;
                                if($startingFare <= $new_fare && $uptoFare >= $new_fare){
                                    $percentage = $ticketFareSlab->odbus_commision;
                                    $odbusServiceCharges = nbf($new_fare * ($percentage/100));
                                    $lb->busSeats->new_fare = nbf($new_fare + $odbusServiceCharges);
                                    }     
                                }
                            }   
                        }
                        if (sizeof($bookingIds)){    
                            if(in_array($lb['id'], $blockedSeats)){
                                $key = array_search($lb['id'], $seatsIds);
                                $viewSeat['lower_berth'][$i]['Gender'] = $gender[$key];
                            } 
                        }
                        $i++;
                    } 
                  }
            return $viewSeat;
    }
    
    }