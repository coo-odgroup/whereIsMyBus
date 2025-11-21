<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class SendEmailOTP extends Mailable
{
    use Queueable, SerializesModels;
    public $to;
    public $name;
    public $email_otp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $email_otp)
    {
        $this->name = $name;
        $this->email_otp= $email_otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(config('services.email.subject'))
        ->view('email')
        ->with([
            'name' => $this->name,
            'otp' => $this->email_otp,  
        ]);
    }
}
