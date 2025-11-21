<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use PDF;



class SendFeedbackEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 
    protected $name;
    protected $email;
    protected $pnr;
    protected $journey_date;

    public function __construct($name,$pnr,$journey_date,$email)
    {
        $this->name = $name;
        $this->email = $email;
        $this->journey_date = date('d-m-Y',strtotime($journey_date));
        $this->subject ='';
    }

    public function handle()
    {
        $data = [
            'name' => $this->name      
        ];

        $this->subject = "ODBUS: How was Your Bus Journey?";
       
        Mail::send('emailFeedback', $data, function ($messageNew) {
            $messageNew->to($this->email)
            ->subject($this->subject);
        });
      
        // if (Mail::failures()) {
        //     return new Error(Mail::failures()); 
        // }

    }
}
