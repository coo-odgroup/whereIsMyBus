<?php
use Illuminate\Support\Facades\DB;

// Added By Banashri Mohanty :: 25-jun-2024 (for security audit weak encryption fixes)
function encryptResponse($data){

    $jsonData = json_encode($data);
    
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($jsonData, 'aes-256-cbc', '252e80b4e5d9cfc8b369ad98dcc87b5f', 0, $iv);
    return base64_encode($encrypted . '::' . base64_encode($iv));
}

// Added By Banashri Mohanty :: 25-jun-2024 (for security audit weak encryption fixes)
function decryptRequest($encryptedData){   
    $decodedData = base64_decode($encryptedData);

    $iv = substr($decodedData, 0, 16);
    $ciphertext = substr($decodedData, 16);

    return openssl_decrypt($ciphertext, 'AES-256-CBC', '252e80b4e5d9cfc8b369ad98dcc87b5f', OPENSSL_RAW_DATA, $iv);

}

function getTicketFareslab($busId,$jrdate){
    $bus_dt=DB::table('bus')->where('id',$busId)->first();
    $opId=$bus_dt->bus_operator_id;
    
    $ticketFareRecord = DB::table('ticket_fare_slab')->where('bus_operator_id', $opId)->where('from_date','<=',$jrdate)->where('to_date','>=',$jrdate)->where('from_date','!=',null)->where('to_date','!=',null)->get();

    if(count($ticketFareRecord)==0){

        $defUserId = Config::get('constants.USER_ID'); 
        $ticketFareRecord= DB::table('ticket_fare_slab')->where('user_id', $defUserId)->get();
       
    }

    return $ticketFareRecord;
}

function PaytmdriverCallBackAPI($pnr){
    $bd=DB::select("select b.*,bs.name as bus_name,bs.bus_operator_id,bs.bus_number,bc.phone from 
    booking b 
    left join bus bs on b.bus_id=bs.id 
    left join bus_contacts bc on b.bus_id=bc.bus_id 
    where b.pnr='$pnr' and bc.status=1 and bc.type=2");
    $bd=$bd[0];
    $body='{
        "providerId":68 , 
        "operatorId": "'.$bd->bus_operator_id.'",
        "journeyDate": "'.$bd->journey_dt.'",
        "tripId": "'.$bd->bus_id.'",
        "isGpsAvailable": false,
        "gpsUrl": "",
        "info": {
            "driverInfo": [
                {
                    "driverName": "'.$bd->bus_name.'",
                    "phoneNumbers": [
                        "'.$bd->phone.'"
                    ]
                }
            ],
            "vehicleNumber": "'.$bd->bus_number.'"
        }
    }';

    //echo $body;exit;

    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => env('PAYTM_DRIVER_API_URL'),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>$body,
      CURLOPT_HTTPHEADER => array(
        'bus-ek: odbus',
        'bus-es: 6632596ff74049b8ad8c4a923e4a76c9',
        'Content-Type: application/json'
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
   // Log::Info("driver call back API");
   // Log::Info($response);
}

function getFinancialYear($date = null) {
    $date = $date ? strtotime($date) : time();
    $year = date('y', $date); // 2-digit year
    $month = date('m', $date);

    if ($month < 4) {
        $startYear = $year - 1;
        $endYear = $year;
    } else {
        $startYear = $year;
        $endYear = $year + 1;
    }

    // Format both as 2-digit strings with leading zeros if needed
    return str_pad($startYear, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endYear, 2, '0', STR_PAD_LEFT);
}

function generateGSTId($number = 1, $date = null) {
    $date = $date ? strtotime($date) : time();

    // Financial Year
    $year = date('y', $date);
    $month = date('m', $date);
    if ($month < 4) {
        $startYear = $year - 1;
        $endYear = $year;
    } else {
        $startYear = $year;
        $endYear = $year + 1;
    }
    $financialYear = str_pad($startYear, 2, '0', STR_PAD_LEFT).str_pad($endYear, 2, '0', STR_PAD_LEFT);

    // Random or sequential number (default is 1 here)
    $numberFormatted = str_pad($number, 4, '0', STR_PAD_LEFT);

    // Final Format
    return "OB{$financialYear}{$month}-{$numberFormatted}.pdf";
}

function nbf($value){
    return round($value, 2);
}


?>