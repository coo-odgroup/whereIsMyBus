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



class SendApiEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 
    protected $name;
    protected $email;
    protected $phone;

    public function __construct($name,$email,$phone)
    {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->subject ='';
    }

    public function handle()
    {
        $data = [
            'name' => $this->name ,     
            'email' => $this->email ,     
            'phone' => $this->phone     
        ];

        $this->subject = "ODBUS: New Lead For API Reference - ".$this->name;
       
        Mail::send('emailApi', $data, function ($messageNew) {
            $messageNew->to("support@odbus.in")
            ->subject($this->subject);
        });
      
        // if (Mail::failures()) {
        //     return new Error(Mail::failures()); 
        // }

    }
}
