<?php

namespace App\Http\Controllers;

use Artisaninweb\SoapWrapper\SoapWrapper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use App\Transformers\DolphinTransformer;

class SoapController
{
  /**
   * @var SoapWrapper
   */
  protected $soapWrapper;
  protected $DolphinTransformer;

  use ApiResponser;

  /**
   * SoapController constructor.
   *
   * @param SoapWrapper $soapWrapper
   */
  public function __construct(SoapWrapper $soapWrapper, DolphinTransformer $DolphinTransformer)
  {
    $this->soapWrapper = $soapWrapper;
    $this->DolphinTransformer = $DolphinTransformer;
  }

  public function DolphinCancelPolicy(){
    return $this->DolphinTransformer->GetCancellationPolicy();
  }

  public function DolphinCronJobEmailSms(){
    return $this->DolphinTransformer->FetchTicketPrintData();
  }

  

  /**
   * Use the SoapWrapper
   */
  public function getCountries(Request $request) 
  {
    $this->soapWrapper->add('Countries', function ($service) {
      $service
        ->wsdl('http://webservices.oorsprong.org/websamples.countryinfo/CountryInfoService.wso?WSDL')
        ->trace(true);
    });

    $response = $this->soapWrapper->call('Countries.ListOfCountryNamesByName', [$request]);

    return $this->successResponse($response->ListOfCountryNamesByNameResult->tCountryCodeAndName,Config::get('constants.RECORD_FETCHED'), Response::HTTP_ACCEPTED);
  
  }

  public function pdfdownload($pnr){
    $view = "Lima";
    header("Content-type: text/html");
    header("Content-Disposition: attachment; filename=view.html");
    return $view;
  }
}
