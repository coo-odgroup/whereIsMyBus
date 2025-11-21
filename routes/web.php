<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotManController;
use App\Http\Controllers\SoapController;
use App\Http\Controllers\BookingManageController;
use App\Http\Controllers\HTMLPDFController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ClientBookingController;
use App\Http\Controllers\PopularController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::match(['get', 'post'], 'botman', [BotManController::class, 'handle']);

Route::get('qr-code-g', function () {
  
    // \QrCode::size(500)
    //         ->format('png')
    //         ->generate('ItSolutionStuff.com', public_path('images/qrcode.png'));
    
  return view('qrCode');
    
});

Route::get('gst', function () {
return view('Gst');
  
});

Route::get('/TestingEmail', [ChannelController::class, 'testingEmail']); 

Route::get('downloadTicket/{pnr}', [BookingManageController::class,'downloadTicket']);
Route::get('PaytmdriverDetailApi', [ClientBookingController::class,'PaytmdriverDetailApi']);

/// cron job for GST after journey date completed
Route::get('/GSTEmailNotification', [ChannelController::class, 'GSTEmailSend']);

Route::get('/send-sms', [PopularController::class, 'ValueFirstSms']);