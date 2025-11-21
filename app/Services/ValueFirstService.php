<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ValueFirstService
{
    protected $username;
    protected $password;
    protected $sender;
    protected $url;

    public function __construct()
    {
    }

    public function sendSms($to, $message)
    {
        $numbers = array_filter(explode(',', $to));

        $addrs=[];
        
        foreach( $numbers as $k => $n){
            $addrs[]=[
                    "from" => "ODBUUS",
                    "to" => $n,
                    "seq" => $k+1,
                    "tag" => ""
                ];
        }

        $curl = curl_init();

        $payload = [
        "apiver" => "1.0",
        "sms" => [
        "ver" => "2.0",
        "dlr" => [
            "url" => ""  // optional
        ],
        "messages" => [
            [
                "udh" => "0",
                "text" => $message,
                "property" => 0,
                "id" => "1",
                "addresses" => $addrs
            ]
        ]
    ]
];


        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.goinfinito.com/unified/v2/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($payload),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJJbmZpbml0byIsImlhdCI6MTc1MzM0OTIxOSwic3ViIjoiT0RCVVNpNG1xb2plZDZreXJtNnY5dWNiIn0._-DrotVj12RNZ6Y8swdprw4rGC115IhwQ4kREvFpRt0',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

         $sms_log['phone']=$to;
        $sms_log['sms_body']=$message;
        $sms_log['req_body']=json_encode($payload);
        $sms_log['response']=$response;
        $sms_log['via']="Website";
        DB::table("sms_log")->insert($sms_log);


        return $response;
    }
}
