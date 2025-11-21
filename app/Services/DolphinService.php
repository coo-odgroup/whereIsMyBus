<?php

namespace App\Services;

use Artisaninweb\SoapWrapper\SoapWrapper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DateTime;


class DolphinService
{
 
  protected $soapWrapper;

  use ApiResponser;

  public function __construct(SoapWrapper $soapWrapper)
  {
    $this->soapWrapper = $soapWrapper;
    $this->option=["verifyCall" =>"dK86BF3S7KJbPrdF94qzvjm8xanYN9a7egb84bp59Fw93J8FdwHM"];
    
  }

  

  public function GetCityPair() 
  {

    $result=[];

    $this->soapWrapper->add('GetCityPair', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

       $response = $this->soapWrapper->call('GetCityPair.GetCityPair', [$this->option]);
            
       $data=$this->xmlToArray($response->GetCityPairResult->any);

      if(isset($data['DocumentElement'])){
      
        $data=$data['DocumentElement']['ITSCityPair'];

       if($data){
        foreach($data as $v){            
                $result[]=$v;
        }
       }
    }

      return $result;
  
  }

  public function GetAvailableRoutes($s,$d,$dt) 
  {

    $result=[];

    $dt=date("d-m-Y",strtotime($dt));

    $this->soapWrapper->add('GetAvailableRoutes', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

       $option=array(
              "FromID"=> $s,
              "ToID"=>$d,
              "JourneyDate"=>$dt,
              "VerifyCall"=>$this->option['verifyCall']
            );

          //  Log::info("http://apislvv2.itspl.net/ITSGateway.asmx?wsdl");
           // Log::info($option);

       $response = $this->soapWrapper->call('GetAvailableRoutes.GetAvailableRoutes', [$option]);

         $data=$this->xmlToArray($response->GetAvailableRoutesResult->any);

         //Log::info($data);


      if(isset($data['DocumentElement'])){
      
        return $data['DocumentElement']['AllRouteBusLists'];
          
      }

  }

  

  public function CancelDetails($pnr) 
  {

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('CancelDetails', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['PNRNo']=$pnr;
      $option['VerifyCall']=$this->option['verifyCall'];


      $response = $this->soapWrapper->call('CancelDetails.CancelDetails', [$option]);

      $data=$this->xmlToArray($response->CancelDetailsResult->any);

      if(isset($data['DocumentElement'])){
        return $data=$data['DocumentElement']['ITSTicketCancelDetails'];
      }
  
  }

  public function ConfirmCancellation($pnr) 
  {

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('ConfirmCancellation', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['PNRNo']=$pnr;
      $option['VerifyCall']=$this->option['verifyCall'];


        $response = $this->soapWrapper->call('ConfirmCancellation.ConfirmCancellation', [$option]);

         $data=$this->xmlToArray($response->ConfirmCancellationResult->any);

      if(isset($data['DocumentElement'])){

        //Log::info($data['DocumentElement']);
      
        return $data=$data['DocumentElement']['ConfirmCancellation'];
     }
  
  }


  public function GetSource() 
  {

    $result=[];
    
    $this->soapWrapper->add('GetSources', function ($service) {
        $service
          ->wsdl('http://apislvV2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });
  
      $response = $this->soapWrapper->call('GetSources.GetSources', [$this->option]);
      
      $data=$this->xmlToArray($response->GetSourcesResult->any);
      
       if(isset($data['DocumentElement'])){

         $data=$data['DocumentElement']['ITSSources'];      

       if($data){
        foreach($data as $v){
            
                $result[]=[
                    "id"=> $v['CM_CityID'],
                    "name"=> $v['CM_CityName'],
                    "synonym"=> '',
                    "url"=> '',
                ];

        }
       }

    }

      return $result;
  
  }

  public function GetDestination($s) 
  {

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('GetDestinationsBasedOnSource', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['SourceID']=$s;
      $option['VerifyCall']=$this->option['verifyCall'];


        $response = $this->soapWrapper->call('GetDestinationsBasedOnSource.GetDestinationsBasedOnSource', [$option]);

       $data=$this->xmlToArray($response->GetDestinationsBasedOnSourceResult->any);

      if(isset($data['DocumentElement'])){
      
        $data=$data['DocumentElement']['ITSDestinations'];

        if (count($data) == count($data, COUNT_RECURSIVE)){

          $result[]=[
            "id"=> $data['CM_CityID'],
            "name"=> $data['CM_CityName'],
            "synonym"=> '',
            "url"=> '',
        ];

        } else{

          foreach($data as $v){
            
            $result[]=[
                "id"=> $v['CM_CityID'],
                "name"=> $v['CM_CityName'],
                "synonym"=> '',
                "url"=> '',
            ];
          }

        }

      
    }

      return $result;
  
  }


  public function BlockSeat($array){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('BlockSeatV2', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['ReferenceNumber']=$array['ReferenceNumber'];
      $option['PassengerName']=$array['PassengerName'];
      $option['SeatNames']=$array['SeatNames']; // 15,F|16,M  (This should be the format )
      $option['Email']=$array['Email'];
      $option['Phone']=$array['Phone'];
      $option['PickupID']=$array['PickupID'];
      $option['PayableAmount']=$array['PayableAmount'];
      $option['TotalPassengers']=$array['TotalPassengers'];
      $option['VerifyCall']=$this->option['verifyCall'];

       $response = $this->soapWrapper->call('BlockSeatV2.BlockSeatV2', [$option]);

       $data=$this->xmlToArray($response->BlockSeatV2Result->any);

       if(isset($data['DocumentElement'])){
      
        return $data=$data['DocumentElement']['ITSBlockSeatV2'];
       }

       

  }

  public function BookSeat($array){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('BookSeat', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['ReferenceNumber']=$array['ReferenceNumber'];
      $option['PassengerName']=$array['PassengerName'];
      $option['SeatNames']=$array['SeatNames']; // 15,F|16,M  (This should be the format )
      $option['Email']=$array['Email'];
      $option['Phone']=$array['Phone'];
      $option['PickIpID']=$array['PickupID'];
      $option['PayableAmount']=$array['PayableAmount'];
      $option['TotalPassengers']=$array['TotalPassengers'];
      $option['VerifyCall']=$this->option['verifyCall'];

       $response = $this->soapWrapper->call('BookSeat.BookSeat', [$option]);

       $data=$this->xmlToArray($response->BookSeatResult->any);

       if(isset($data['DocumentElement'])){
      
        return $data=$data['DocumentElement']['ITSBookSeat'];
       }

  }

  
  public function GetBusNo($ar){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('GetBusNo', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['PNRNo']=$ar['PNRNo'];
      $option['CompanyID']=$ar['CompanyID'];
      $option['RouteID']=$ar['RouteID'];
      $option['RouteTimeID']=$ar['RouteTimeID'];
      $option['FromID']=$ar['FromID'];
      $option['JourneyDate']=$ar['JourneyDate']; //dd-mm-yyyy      
      $option['VerifyCall']=$this->option['verifyCall'];


    $response = $this->soapWrapper->call('GetBusNo.GetBusNo', [$option]);

    $data=$this->xmlToArray($response->GetBusNoResult->any);

    if(isset($data['DocumentElement'])){

    return $data=$data['DocumentElement']['ITSGetBusNo'];
    }
  }

  public function FetchTicketPrintData($pnr){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('FetchTicketPrintData', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['PNRNo']=$pnr;
      $option['VerifyCall']=$this->option['verifyCall'];

       $response = $this->soapWrapper->call('FetchTicketPrintData.FetchTicketPrintData', [$option]);


     $data=$this->xmlToArray($response->FetchTicketPrintDataResult->any);  

      if(isset($data['DocumentElement'])){  
         $data=$data['DocumentElement']['TicketPrintData'];
        // Log::info($data);

          return $data;

      }


  }


  public function GetCancellationPolicy(){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('GetCancellationPolicy', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['CompanyID']=251;
      $option['VerifyCall']=$this->option['verifyCall'];

      $response = $this->soapWrapper->call('GetCancellationPolicy.GetCancellationPolicy', [$option]);

      return $response->GetCancellationPolicyResult->CancellationPolicy;

  }

  
  

  public function GetBoardingDropLocationsByCity(){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('GetBoardingDropLocationsByCity', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['CompanyID']='251';
      $option['CityID']='1689';
      $option['VerifyCall']=$this->option['verifyCall'];

      $response = $this->soapWrapper->call('GetBoardingDropLocationsByCity.GetBoardingDropLocationsByCity', [$option]);


      $data=$this->xmlToArray($response->GetBoardingDropLocationsByCityResult->any);     

     if(isset($data['DocumentElement'])){
    
      return $data=$data['DocumentElement']['GetBoardingDropLocationsByCity'];
     }



  }


  

  public function GetAmenities($CompanyID){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('GetAmenities', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['CompanyID']=$CompanyID;
      $option['VerifyCall']=$this->option['verifyCall'];


        $response = $this->soapWrapper->call('GetAmenities.GetAmenities', [$option]);
     

     $data=$this->xmlToArray($response->GetAmenitiesResult->any);

     if(isset($data['DocumentElement'])){
     
       return $data=$data['DocumentElement']['GetAmenities'];     
   }


  }

 

  public function TicketStatus(){

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('TicketStatus', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['PNRNo']='';
      $option['VerifyCall']=$this->option['verifyCall'];


        $response = $this->soapWrapper->call('TicketStatus.TicketStatus', [$option]);


  }

  public function GetSeatArrangementDetails($ref) 
  {

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('GetSeatArrangementDetails', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['ReferenceNumber']=$ref;
      $option['VerifyCall']=$this->option['verifyCall'];


        $response = $this->soapWrapper->call('GetSeatArrangementDetails.GetSeatArrangementDetails', [$option]);

        $data=$this->xmlToArray($response->GetSeatArrangementDetailsResult->any);

      if(isset($data['DocumentElement'])){
      
        return $data=$data['DocumentElement']['ITSSeatDetails'];


      
    }

  
  }

  public function GetBoardingPointDetails($ref_no) 
  {

    $this->soapWrapper= new SoapWrapper();

    $result=[];

    $this->soapWrapper->add('GetBoardingPointDetails', function ($service) {
        $service
          ->wsdl('http://apislvv2.itspl.net/ITSGateway.asmx?wsdl')
          ->trace(true);
      });

      $option['ReferenceNumber']=$ref_no;
      $option['VerifyCall']=$this->option['verifyCall'];


        $response = $this->soapWrapper->call('GetBoardingPointDetails.GetBoardingPointDetails', [$option]);

         $data=$this->xmlToArray($response->GetBoardingPointDetailsResult->any);

      if(isset($data['DocumentElement'])){
      
        return $data=$data['DocumentElement']['ITSBoardingPoint'];

    }

      return $result;
  
  }


  public function xmlToArray($xmlstring){
    
    $result = \explode('</xs:schema>', $xmlstring);
    $array = json_decode(json_encode((array)simplexml_load_string($result[1])),true);
  
    return $array;
  
  }

}
