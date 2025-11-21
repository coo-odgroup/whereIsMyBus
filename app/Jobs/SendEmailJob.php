<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $to;
    protected $name;
    protected $email_otp;
    //protected $email_body;
    public function __construct($to, $name, $email_otp)
    {
        $this->to = $to;
        $this->name = $name;
        //$this->subject = $subject;
        //$this->email_body= $email_body;
        $this->email_otp= $email_otp;
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
            'otp' => $this->email_otp,
        ];
       
        Mail::send('email', $data, function ($messageNew) {
            //$messageNew->from(config('mail.contact.address'))
            $messageNew->to($this->to)
            ->subject(config('services.email.subject'));
            
        });

        // Mail::send('email', ['email_body' => $this->email_body], function ($messageNew) {
        //     $messageNew->from(config('mail.contact.address'))
        //     ->to($this->to)
        //     ->subject($this->subject);
        // });

    }
}