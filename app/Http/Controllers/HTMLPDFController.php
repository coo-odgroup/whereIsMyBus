<?php

namespace App\Http\Controllers;

use PDF;
use Illuminate\Http\Request;
use App\Services\BookingManageService;



class HTMLPDFController extends Controller
{

    protected $bookTicketService;

    public function __construct(BookingManageService $bookingManageService)
    {   
        $this->bookingManageService = $bookingManageService;  

    }
    
    public function downlaodTicket($pnr)
    {

        $data= $this->bookingManageService->downlaodTicket($pnr);  

      $pdf = PDF::loadView('htmlPdf',$data);

       // $pdf = PDF::loadView('htmlPdf');
       // return $pdf->download($pnr.'.pdf');

       $path = 'public/ticketpdf/';
      $pdf->save($path  . $pnr.'.pdf');
      return url($path.$fileName);

    }
}