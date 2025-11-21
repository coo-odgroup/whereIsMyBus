<?php

namespace App\Services;


use App\Repositories\SeoRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Support\Facades\Config;
use App\Models\Users;
class NotificationService
{
    
    public function __construct()
    {
    
    }
    public function sendNotification($request)
    {   
       $SERVER_API_KEY = env('FCM_SERVER_KEY');   
       $allFirebaseTokens = Users::whereNotNull('fcm_id')->pluck('fcm_id')->all();
       $userIds = $request['userIds'];
       if(isset($userIds)){
       $reqFirebaseTokens = Users::whereIn('id', $userIds)->pluck('fcm_id')->all();
       }
       if(isset($reqFirebaseTokens) && count($reqFirebaseTokens)>0){ 
        $data = [
            "registration_ids" => $reqFirebaseTokens,
            "notification" => [
                "title" => $request['notification.title'],
                "body" => $request['notification.body'],  
            ]
        ];
       }else{
        $data = [
            "registration_ids" => $allFirebaseTokens,
            "notification" => [
               "title" => $request['notification.title'],
               "body" => $request['notification.body'],  
            ]
        ];
       }
      
       $dataString = json_encode($data);
       
       $headers = [
           'Authorization: key=' . $SERVER_API_KEY,
           'Content-Type: application/json',
       ];
     
       $ch = curl_init();
       
       //curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
       curl_setopt($ch, CURLOPT_URL, env('FCM_GOOGLE_URL'));
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set timeout to 30 seconds

       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                
       $response = json_decode(curl_exec($ch));
       //return $response;
       if ($response === false) {
            // throw new Exception('Curl error: ' . curl_error($crl));
            //print_r('Curl error: ' . curl_error($crl));
            //$result_noti = 0;
            return 'failed';
        } else {
 
            return $response;
        }   
 
        //curl_close($crl);
        //print_r($result_noti);die;
        //return $result_noti;
       //return $data['notification'];
       //return back()->with('success', 'Notification send successfully.');
   }
  
}