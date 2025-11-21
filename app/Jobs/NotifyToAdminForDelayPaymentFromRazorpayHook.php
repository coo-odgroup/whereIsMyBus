<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyToAdminForDelayPaymentFromRazorpayHook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $detail;
    protected $order_id;
    protected $payament_id;
    protected $status;
    protected $subject;
    

    public function __construct($booking_detail,$order_id,$payament_id,$status)

    {
        $this->detail = $booking_detail;
        $this->order_id =  $order_id;
        $this->payament_id = $payament_id;
        $this->status = $status;
        $this->subject='';
      
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $data = [
            'pnr' =>  $this->detail->pnr,    
            'Payment_Status' =>  $this->status,    
            'Razor_Payment_ID' =>  $this->payament_id,    
            'Razor_Order_ID' =>  $this->order_id,    
        ];

        //Log::info($data);
             
        $this->subject = "!Important : Delay Payment From Razorpay - PNR : ".$this->detail->pnr;
       
        Mail::send('AdminDelayNotify', $data, function ($messageNew) {
            $messageNew->to('booking@odbus.in')
            ->subject($this->subject);
        });
        
        // // check for failures
        // if (Mail::failures()) {
        //     return new Error(Mail::failures()); 
        //     //return "Email failed";
        // }

    }
}
