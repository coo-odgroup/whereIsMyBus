<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Services\MantisService;

class MantisController extends Controller
{
   
  protected $mantisService;

  use ApiResponser;

  /**
   * SoapController constructor.
   *
   * @param SoapWrapper $soapWrapper
   */
  public function __construct(MantisService $mantisService)
  {
    $this->mantisService = $mantisService;
  }

  public function getToken(){
    return $this->mantisService->getToken();
  }
}
