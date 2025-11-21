<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Bus;
use App\Models\Booking;
use App\Models\BusType;
use App\Models\BusClass;

class SendAdminEmailTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $to;
    protected $name;
    protected $email_pnr;
    protected $bookingdate;
    protected $journeydate;
    protected $boarding_point;
    protected $dropping_point;
    protected $departureTime;
    protected $arrivalTime;
    protected $seat_no;
    protected $busname;
    protected $source;
    protected $destination;
    protected $busNumber;      
    protected $bustype;
    protected $busTypeName;
    protected $sittingType;
    protected $totalfare;
    protected $discount;
    protected $payable_amount;
    protected $odbus_gst;
    protected $odbus_charges;
    protected $owner_fare;
    protected $customer_comission;
    protected $conductor_number;
    protected $agent_number;
    protected $customer_number;    
    protected $passengerDetails;
    protected $total_seats;
    protected $seat_names;
    protected $subject;
    protected $qrCodeText;
    protected $qrcode_image_path;
    protected $cancelation_policy;
    protected $transactionFee;
    protected $customer_gst_status;
    protected $customer_gst_number;
    protected $customer_gst_business_name;
    protected $customer_gst_business_email;
    protected $customer_gst_business_address;
    protected $customer_gst_percent;
    protected $customer_gst_amount;
    protected $coupon_discount;
    protected $p_names;
    protected $routedetails;
    

    


    //protected $request = [];
    //public function __construct($to, $name, $email_pnr)
    public function __construct($totalfare,$discount,$payable_amount,$odbus_charges,$odbus_gst,$owner_fare,$request, $email_pnr,$cancelation_policy,$transactionFee,$customer_gst_status,$customer_gst_number,$customer_gst_business_name,$customer_gst_business_email,$customer_gst_business_address,$customer_gst_percent,$customer_gst_amount,$coupon_discount)

    {
        $this->name = $request['name'];
        $this->to =  $request['email'];
        $this->bookingdate = date('d-m-Y',strtotime($request['bookingdate']));
        $this->journeydate = date('d-m-Y',strtotime($request['journeydate']));
        $this->boarding_point = $request['boarding_point'];
        $this->dropping_point = $request['dropping_point'];
        $this->departureTime = $request['departureTime'];
        $this->arrivalTime = $request['arrivalTime'];
        $this->seat_no = $request['seat_no'];
        $this->busname = $request['busname'];
        $this->source = $request['source'];
        $this->destination = $request['destination'];
        $this->routedetails = $request['routedetails'];
        
        $this->busNumber = $request['busNumber'];
        $this->bustype = $request['bustype'];
        $this->busTypeName = $request['busTypeName'];
        $this->sittingType = $request['sittingType'];

        $this->totalfare = $totalfare;
        $this->discount = $discount;
        $this->payable_amount = $payable_amount;
        $this->odbus_gst = $odbus_gst;
        $this->odbus_charges = $odbus_charges;
        $this->owner_fare = $owner_fare;
        $this->transactionFee = $transactionFee;

        $this->customer_gst_status = $customer_gst_status;
        $this->customer_gst_number = $customer_gst_number;
        $this->customer_gst_business_name = $customer_gst_business_name;
        $this->customer_gst_business_email = $customer_gst_business_email;
        $this->customer_gst_business_address = $customer_gst_business_address;
        $this->customer_gst_percent = $customer_gst_percent;
        $this->customer_gst_amount = $customer_gst_amount;
        $this->coupon_discount = $coupon_discount;
        
        $this->cancelation_policy = $cancelation_policy;

        

        $this->conductor_number = $request['conductor_number'];
        $this->agent_number = (isset($request['agent_number'])) ? $request['agent_number'] : '';        
        $this->customer_number = $request['phone'];
        $this->passengerDetails = $request['passengerDetails'];
        $this->total_seats = count($request['passengerDetails']);       
         $collection = collect($request['seat_no']);
        $this->seat_names = $collection->implode(',');
        $this->customer_comission =  (isset($request['customer_comission'])) ? $request['customer_comission'] : 0;
    
        $this->email_pnr= $email_pnr;

        

        $this->subject ='';


        $p_name=[];
        foreach($request['passengerDetails'] as $p){
            $pp = $p['passenger_name']." (".$p['passenger_gender'].") ";
            array_push($p_name,$pp);
        }

        $pp_names='';

        if($p_name){
            $pp_names = implode(',',$p_name);
        }

        $this->p_names=$pp_names;
        //Log::info("step admin");

       
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        


        $data = [
            'name' => $this->name,
            'pnr' => $this->email_pnr,
            'bookingdate'=> $this->bookingdate,
            'journeydate' => $this->journeydate ,
            'boarding_point'=> $this->boarding_point,
            'dropping_point' => $this->dropping_point,
            'departureTime'=> $this->departureTime,
            'arrivalTime'=> $this->arrivalTime,
            'seat_no' => $this->seat_no,
            'busname'=> $this->busname,
            'source'=> $this->source,
            'destination'=> $this->destination,
            'routedetails'=> $this->routedetails,            
            'busNumber'=> $this->busNumber,
            'bustype' => $this->bustype,
            'busTypeName' => $this->busTypeName,
            'sittingType' => $this->sittingType, 
            'conductor_number'=> $this->conductor_number,
            'customer_number'=> $this->customer_number,
            'agent_number'=> $this->agent_number,
            'passengerDetails' => $this->passengerDetails ,
            'totalfare'=> $this->totalfare,
            'discount' =>  $this->discount,
            'payable_amount' => $this->payable_amount ,
            'odbus_gst'=> $this->odbus_gst,
            'odbus_charges'=> $this->odbus_charges,
            'owner_fare'=> $this->owner_fare,
            'transactionFee'=> $this->transactionFee,             
            'customer_gst_status'=> $this->customer_gst_status, 
            'customer_gst_number'=> $this->customer_gst_number, 
            'customer_gst_business_name'=> $this->customer_gst_business_name, 
            'customer_gst_business_email'=> $this->customer_gst_business_email, 
            'customer_gst_business_address'=> $this->customer_gst_business_address, 
            'customer_gst_percent'=> $this->customer_gst_percent, 
            'customer_gst_amount'=> $this->customer_gst_amount, 
            'coupon_discount'=> $this->coupon_discount,
            'total_seats'=>  $this->total_seats ,
            'seat_names'=>  $this->seat_names ,
            'customer_comission'=> $this->customer_comission,
            'cancelation_policy' => $this->cancelation_policy,
            'p_names' => $this->p_names,            
        ];

      //  Log::info("admin");
       // Log::info($data);

        $bk_dtl=Booking::with(["bus" => function($bs){
            $bs->with('BusType.busClass');
            $bs->with('BusSitting');  
          } ] )->where('pnr', $this->email_pnr)->first();
             
        $this->subject = config('services.email.subjectTicket');
        $this->subject = str_replace("<PNR>",$this->email_pnr,$this->subject);


        Mail::send('AdminemailTicket', $data, function ($messageNew) {
            $messageNew->to('booking@odbus.in')
            ->subject($this->subject);
        });
        
       
        // if($this->customer_gst_status==0){
        //     Mail::send('AdminemailTicket', $data, function ($messageNew) {
        //         $messageNew->to('booking@odbus.in')
        //         ->subject($this->subject);
        //     });

        // }

        // else{

        //     $gst='https://consumer.odbus.co.in/public/gst/'.$bk_dtl->gst_invoice_no;

        //     Mail::send('AdminemailTicket', $data, function ($messageNew) use($gst)  {
        //         $messageNew->attach($gst)->to('booking@odbus.in')
        //         ->subject($this->subject);
        //     });
        // }
       
        
        // // check for failures
        // if (Mail::failures()) {
        //     return new Error(Mail::failures()); 
        //     //return "Email failed";
        // }

    }
}
