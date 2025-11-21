<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Mail\SendPdfEmail;

class TestingEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $to;
    protected $name;
    protected $subject;
    protected $file;
    protected $gst;
    public function __construct($to, $name)
    {
        $this->to = $to;
        $this->name = $name;
        $this->subject = 'testing';
        $this->file=public_path('ticketpdf/ODW6340039.pdf');
        $this->gst=public_path('gst/OB-000082.pdf');
        
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
        ];

        
      Mail::send('test', $data, function ($messageNew) {
           $messageNew->attach($this->file)->attach($this->gst)->to($this->to)
            ->subject($this->subject);
        });
        
        // check for failures
        if (Mail::failures()) {
            return new Error(Mail::failures()); 
            //return "Email failed";
        }else{
            return 'success';
        }

    }
}