<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;



class SendAdminEmailTicketCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $to;
    protected $email;
    protected $pnr;
    protected $journeydate;
    protected $contactNo;
    protected $route;
    protected $deductionPercentage;
    protected $refundAmount;
    protected $seat_no;
    protected $cancellationDateTime;
    protected $totalfare;
    protected $subject;
    protected $cancelation_policy;
    protected $origin;
    protected $bus_name;
    protected $transaction_fee;


    public function __construct($request)

    {
        $this->to = $request['email'];
        $this->pnr = $request['pnr'];
        $this->journeydate = $request['journeydate'];
        $this->contactNo = $request['contactNo'];
        $this->route = $request['route'];
        $this->deductionPercentage = $request['deductionPercentage'];
        $this->refundAmount = number_format($request['refundAmount'],2);
        $this->seat_no = $request['seat_no'];
        $this->totalfare = $request['totalfare'];
        $this->cancellationDateTime = $request['cancellationDateTime'];
        $this->subject ='';
        $this->cancelation_policy = $request['cancelation_policy'];
        $this->origin = $request['origin'];
        $this->bus_name = $request['bus_name'];
        $this->transaction_fee = $request['transaction_fee'];

        

    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $data = [
            'email' => $this->to,
            'pnr' => $this->pnr,
            'contactNo'=> $this->contactNo,
            'journeydate' => $this->journeydate ,
            'route'=> $this->route,
            'seat_no' => $this->seat_no,
            'totalfare'=> $this->totalfare,
            'deductionPercentage'=> $this->deductionPercentage,
            'refundAmount'=> $this->refundAmount,
            'cancellationDateTime'=> $this->cancellationDateTime,
            'origin'=> $this->origin,
            'bus_name'=> $this->bus_name,
            'transaction_fee'=> $this->transaction_fee,
            'cancelation_policy'=>  $this->cancelation_policy  
        ];
        //dd($data);
        $this->subject = config('services.email.subjectTicketCancel');
        $this->subject = str_replace("<PNR>",$this->pnr,$this->subject);

        Mail::send('emailTicketCancel', $data, function ($messageNew) {
            $messageNew->to('support@odbus.in')
            ->subject($this->subject);
        });

    }
}
