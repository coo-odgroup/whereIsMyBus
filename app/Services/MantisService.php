<?php

namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use GuzzleHttp\Client;

class MantisService
{
    protected $url;
    protected $http;
    protected $headers;

    public function __construct(Client $client)
    {
        $this->url = 'https://api.iamgds.com/ota/Auth';
        $this->cityurl = 'https://api.iamgds.com/ota/CityList';
        $this->searchurl = 'https://api.iamgds.com/ota/Search';
        $this->charturl = 'https://api.iamgds.com/ota/Chart';
        $this->holdSeatsurl = 'https://tranapi.iamgds.com/ota/HoldSeats';
        $this->bookSeatsurl = 'https://tranapi.iamgds.com/ota/BookSeats';
        $this->searchBusurl = 'https://api.iamgds.com/ota/SearchBus';
        $this->isCancellableurl = 'https://tranapi.iamgds.com/ota/IsCancellable';
        $this->cancelSeatsurl = 'https://tranapi.iamgds.com/ota/CancelSeats';
        $this->http = $client;
        $this->headers = [
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded',
        ];
    }
    
    public function getToken(string $uri = null)
    {
        // $token = "6FA7D6F16D51FBC41F016F386B7E097C|50-S|202212061014||FFFF";
        // $response = [];
        // $response = Http::withHeaders([
        //     'Access-Token' => $token,
        //                     ])->post($this->cancelSeatsurl, [
        //                         "PNR"=> "270866049-248758",
        //                         "TicketNo"=> "502223142",
        //                         "SeatNos"=> "22,21",
        //                         ]); 
        // Log::info($response->json());
        // //dd($response);                                               
        // return $response->json();
        //////////////////////////////////////
        $request = Http::post($this->url, [
           // 'ClientId' => 50,
            //'ClientSecret'=> 'd66de12fa3473a93415b02494253f088',
             'ClientId' => 10283,
            'ClientSecret'=> '19ba1582c07fb005ee45ee873c4357ff',
            'timeout'         => 30,
            'connect_timeout' => true,
            'http_errors'     => true,
            //'verify' => false
        ]);
        $recvToken = $request ? $request->getBody()->getContents() : null;
        $status = $request ? $request->getStatusCode() : 500;
        if ($recvToken && $status === 200 && $recvToken !== 'null') {
            Cache::add('token', $recvToken , $seconds = 86400);   
        }   
    }

    public function getCityList()
    {
        $res = [];
        try{
          //$token = "847AA0A10F6764104C7C762B42FD3BD0|50-S|202212051005||FFFF";
          if (Cache::has('token')) {
            $token = cache('token');
          }else{
            $this->getToken();
            $token = cache('token');
          }
          $token = Str::replace('"', '', $token);
          $response = Http::withToken($token)->get($this->cityurl);
          $cityLists[] = response()->json(json_decode($response)->data);
          if($cityLists){
              foreach(($cityLists)[0]->original as $city){
                    $res[] = $city;
              }
          }
          return $res;   
        }
        catch (Exception $e){
            return $e;
        }
    }
   
    public function search($s,$d,$dt) ////used for listing API
    {
        //$token = "731A751C2570C5A5BA9824AF9B9BBA05|50-S|202212021127||FFFF";
        if (Cache::has('token')) {
            $token = cache('token');
        }else{
            $this->getToken();
            $token = cache('token');
        }
        $token = Str::replace('"', '', $token);
        $response = [];
        $response = Http::withToken($token)->get($this->searchurl,[
                                                        "fromCityId"=> $s ,
                                                        "toCityId"=> $d,
                                                        "journeyDate" =>$dt,
                                                        //'headers' => $headers,
                                                        //'verify'  => false,
                                                        ]);                                             
          
        return (object) json_decode($response);   
    }
    public function chart($s,$d,$dt,$busId) 
    {
        //$token = "731A751C2570C5A5BA9824AF9B9BBA05|50-S|202212021127||FFFF";
        if (Cache::has('token')) {
            $token = cache('token');
        }else{
            $this->getToken();
            $token = cache('token');
        }
        $token = Str::replace('"', '', $token);
        $result=[];
        $response = Http::withToken($token)->get($this->charturl,[
                                                        "fromCityId"=> $s ,
                                                        "toCityId"=> $d,
                                                        "journeyDate" => $dt,
                                                        "busId" => $busId,
                                                        //'headers' => $headers,
                                                        //'verify'  => false,
                                                        ]);                                             
        return (object) json_decode($response);
    }

    public function HoldSeats($bookingDet) 
    {
        //$token = "731A751C2570C5A5BA9824AF9B9BBA05|50-S|202212021127||FFFF";
        if (Cache::has('token')) {
            $token = cache('token');
        }else{
            $this->getToken();
            $token = cache('token');
        }
        $token = Str::replace('"', '', $token);
        $response = Http::withHeaders([
                        'Access-Token' => $token,
                    //'Content-Type' => 'application/json'
                        ])->post($this->holdSeatsurl, $bookingDet   
                                );                      
        return $response->json(); 
    }
    public function BookSeats($holdId) 
    {
        //$token = '3FE2CD1A4D70A0346BA6C19F3EC8DE22|50-S|202212011228||FFFF';     
        if (Cache::has('token')) {
            $token = cache('token');
        }else{
            $this->getToken();
            $token = cache('token');
        }  
        $token = Str::replace('"', '', $token);                  
        $response = Http::withHeaders([
            'Access-Token' => $token,
           // 'Content-Type' => 'application/json'
            ])->post($this->bookSeatsurl, ['HoldId' => (int)$holdId
            ]); 
         return $response->json();
    }
    public function searchBus($s,$d,$dt,$busId) ///used to get details of a Bus
    {  
        //$token = "731A751C2570C5A5BA9824AF9B9BBA05|50-S|202212021127||FFFF";
        if (Cache::has('token')) {
            $token = cache('token');
        }else{
            $this->getToken();
            $token = cache('token');
        }
        $token = Str::replace('"', '', $token);
        $response = [];
        $response = Http::withToken($token)->get($this->searchBusurl,[
                                                        "fromCityId"=> $s ,
                                                        "toCityId"=> $d,
                                                        "journeyDate" =>$dt,
                                                        "busId" =>$busId,
                                                        //'headers' => $headers,
                                                        //'verify'  => false,
                                                        ]);                                             
        return $response->json();  
    }
    public function isCancellable($pnrNo,$tktNo,$seats){
        //$token = "6FA7D6F16D51FBC41F016F386B7E097C|50-S|202212061014||FFFF";
        if (Cache::has('token')) {
            $token = cache('token');
        }else{
            $this->getToken();
            $token = cache('token');
        }
        $token = Str::replace('"', '', $token);
            $response = [];
            $response = Http::withHeaders([
                'Access-Token' => $token,
                                ])->get($this->isCancellableurl,[
                                    "PNRNo"=> $pnrNo,
                                    "TicketNo"=> $tktNo,
                                    "seatNos"=> $seats,
                                    ]);                                                                    
            return $response->json();
    }
    public function cancelSeats($pnrNo,$tktNo,$seats){
       
        //$token = "6FA7D6F16D51FBC41F016F386B7E097C|50-S|202212061014||FFFF";
        if (Cache::has('token')) {
            $token = cache('token');
        }else{
            $this->getToken();
            $token = cache('token');
        }
        $token = Str::replace('"', '', $token);
            $response = [];
            $response = Http::withHeaders([
                'Access-Token' => $token,
                            ])->post($this->cancelSeatsurl, [
                                "PNR"=> $pnrNo,
                                "TicketNo"=> $tktNo,
                                "SeatNos"=> $seats,
                                ]);                                                                    
            return $response->json();
        }

    private function postResponse(string $uri = null, array $post_params = [])
    {
        $full_path = $this->url;
        $full_path .= $uri;

        $request = $this->http->post($full_path, [
            'headers'         => $this->headers,
            'timeout'         => 30,
            'connect_timeout' => true,
            'http_errors'     => true,
            'form_params'     => $post_params,
        ]);

        $response = $request ? $request->getBody()->getContents() : null;
        $status = $request ? $request->getStatusCode() : 500;

        if ($response && $status === 200 && $response !== 'null') {
            return (object) json_decode($response);
        }

        return null;
    }
}