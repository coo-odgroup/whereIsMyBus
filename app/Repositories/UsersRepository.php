<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Users;
use Illuminate\Support\Facades\Log;
use App\Repositories\ChannelRepository;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Bus;
use App\Models\Location;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\CustomerPayment;
use App\Models\BusType;
use App\Models\BusClass;
use App\Models\BusSeats;
use App\Models\BusContacts;
use App\Models\Seats;
use App\Models\TicketPrice;
use App\Models\Review;
use Illuminate\Support\Facades\File; 
use App\Repositories\CommonRepository;


class UsersRepository
{
    /**
     * @var Users
     */
    protected $channelRepository;
    protected $bus;
    protected $ticketPrice;
    protected $location;
    protected $users;
    protected $booking;
    protected $busSeats;
    protected $seats;
    protected $bookingDetail;
    protected $busType;
    protected $busClass;
    protected $customerPayment;
    protected $review;
    protected $commonRepository;

    public function __construct(Bus $bus,TicketPrice $ticketPrice,Location $location,Users $users,
    BusSeats $busSeats,Booking $booking,BookingDetail $bookingDetail, Seats $seats,BusClass $busClass
    ,BusType $busType,CustomerPayment $customerPayment,ChannelRepository $channelRepository,Review $review,CommonRepository $commonRepository)
    {
        $this->users = $users;
        $this->channelRepository = $channelRepository;  
        $this->bus = $bus;
        $this->ticketPrice = $ticketPrice;
        $this->location = $location;
        $this->busSeats = $busSeats;
        $this->seats = $seats;
        $this->booking = $booking;
        $this->bookingDetail = $bookingDetail;
        $this->busType = $busType;
        $this->busClass = $busClass;
        $this->customerPayment = $customerPayment;
        $this->review = $review;
        $this->commonRepository = $commonRepository;

    }

   public function GetUserData($phone,$email){
    return $this->users->where([
          ['phone', $phone],
          ['phone', '<>', null]
          ])
          ->orWhere([
          ['email', $email],
          ['email', '<>', null]
          ]);
   }

   public function saveUser($request){
        $user = new $this->users;
        $user->name= $request['name'];
        $user->password= bcrypt('odbus123');
        $user->created_by= $request['created_by'];

        //log::info($request['phone']);

        if($request['phone']!='9999999999'){
          $otp = $this->sendOtp($request);
          $user->otp = $otp;
        }        
        $user->phone = $request['phone'];
        $user->email = $request['email'];
        if(isset($request['fcmId'])){
          //return $request['fcmId'];
          $user->fcm_id = $request['fcmId'];
        }
        
        $user->save();
        return  $user;
   }

   public function updateUser($query,$name,$otp,$request){
        $query->update([
        'name' => $name,
        'otp' => $otp,
        'password' => bcrypt('odbus123')
    ]);
    if(isset($request['fcmId'])){
      $query->update(array('fcm_id' => $request['fcmId']));
    }

    return $query->latest()->first();

   }

    public function sendOtp($request){
        $otp = rand(10000, 99999);
        if($request['phone']){
            $this->users->phone = $request['phone'];
            $sendsms = $this->channelRepository->sendSms($request,$otp);  
        } 
        elseif($request['email']){
            $this->users->email = $request['email']; 
            $sendEmail = $this->channelRepository->sendEmail($request,$otp);
        }
        return  $otp;
    }
    public function getOtp($userId){

      return $this->users->where('id', $userId)->get('otp');
       
    }

    public function userInfo($userId){
      return $this->users->where('id', $userId)->first()->only('name','email');
    }

    public function GetUserDataAfterUpdate($userId){
      return $this->users->where('id', $userId)->first();
    }

    public function updateOTP($userId){
      $token = $this->users->where('id', $userId)->first()->token;
      if($token != Null){
          $this->users->where('id', $userId)->update(array(
            'is_verified' => '1',
            'otp' => Null
        ));
      }else{
          $this->users->where('id', $userId)->update(array(
            'is_verified' => '1',
            'token' => Str::random('10'),
            'otp' => Null
        ));
      }
    //   $this->users->where('id', $userId)->update(array(
    //     'is_verified' => '1',
    //     'token' => Str::random('10'),
    //     'otp' => Null
    //  ));
    }

    public function createOtp($query,$otp,$request){

      if($request['phone']!='9999999999'){
        $query->update(array('otp' => $otp));
      }      
      $query->update(array('password' => bcrypt('odbus123')));
      if(isset($request['fcmId'])){
        $query->update(array('fcm_id' => $request['fcmId']));
      }
      return  $query->latest()->first(); 
           
    }
    public function GetuserByToken($userId,$token)
    {
       return $this->users->where('id', $userId)->where('token', $token)->get();
        
    }
  
   

    public function updateProfile($request){

      $userId = $request['userId'];
      $token = $request['token'];

      $userDetails = $this->GetuserByToken($userId,$token);
  
      $post = $this->users->where('id', $userId)->where('token', $token)->find($userId);
      $post->name = $request['name'];
      $post->email  = $request['email'];
      $post->pincode = $request['pincode'];
      $post->street = $request['street'];
      $post->district = $request['district'];
      $post->address = $request['address'];
  
      if ($request->hasFile('profile_image'))
      {
            $file      = $request->file('profile_image');
            $filename  = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $picture   = date('His').'-'.$filename;
            $post->profile_image = $picture;
            $file->move(public_path('uploads/profile'), $picture);

            if($userDetails[0]->profile_image!=''){
              $image_path = public_path('uploads/profile/').$userDetails[0]->profile_image;
              
              if (File::exists($image_path)) {
                unlink($image_path);
              }              
            }     
      } 
      $post->update();     
      
      return $post;
    }

     //$image contain base64 encoded string
    public function saveBase64ToImage($image) 
    {
        $path = public_path('uploads/profile/');

        //$base = $_REQUEST['image'];
        $base = $image;
        $binary = base64_decode($base);
        //$binary = base64_decode(urldecode($base));
        header('Content-Type: bitmap; charset=utf-8');

        $f = finfo_open();
        $mime_type = finfo_buffer($f, $binary, FILEINFO_MIME_TYPE);        
        $mime_type = str_ireplace('image/', '', $mime_type);

        $filename = md5(\Carbon\Carbon::now()) . '.' . $mime_type;

        $file = fopen($path . $filename, 'wb');

        if (fwrite($file, $binary)) {
            return $filename;
        } else {
            return FALSE;
        }
        fclose($file);
    }


    public function updateProfileImage($request)
    {     
        $userId = $request['id'];
        $token = $request['token'];

        $userDetails = $this->GetuserByToken($userId,$token);  
        $post = $this->users->where('id', $userId)->where('token', $token)->find($userId);
        
        //log::info($request);exit;

        if ($request['image'] != '')
        {
            $imagefile = $this->saveBase64ToImage($request['image']);          
            $post->profile_image = $imagefile;                
        } 
        
        // if(isset($_FILES['image']['name']))
        // {
        //     //getting file info from the request 
        //     $fileinfo = pathinfo($_FILES['image']['name']);
            
        //     //getting the file extension 
        //     $extension = $fileinfo['extension'];
        //     $path_url = public_path('uploads/profile/');
        //     $picture   = date('His').'-'.$fileinfo['filename'].'.'. $extension;
        //     $file_path = $path_url . date('His').'-'.$fileinfo['filename'].'.'. $extension; 
        //     move_uploaded_file($_FILES['image']['tmp_name'],$file_path);   

        //     // if($userDetails[0]->profile_image!='')
        //     // {
        //     //     $image_path = public_path('uploads/profile/').$userDetails[0]->profile_image;
                
        //     //     if (File::exists($image_path)) {
        //     //       unlink($image_path);
        //     //     }              
        //     // }   

        //     $post->profile_image = $picture;  
        //     //log::info($d);exit;
        // }     
     
  
      // if ($request->hasFile('image'))
      // {
      //       $file      = $request->file('image');
      //       $filename  = $file->getClientOriginalName();
      //       $extension = $file->getClientOriginalExtension();
      //       $picture   = date('His').'-'.$filename;
      //       $post->profile_image = $picture;
      //       $file->move(public_path('uploads/profile'), $picture);

      //       if($userDetails[0]->profile_image!=''){
      //         $image_path = public_path('uploads/profile/').$userDetails[0]->profile_image;
              
      //         if (File::exists($image_path)) {
      //           unlink($image_path);
      //         }              
      //       }     
      // } 
       $post->update(); 
       return $post;
    }

    public function CancelledBookings($user_id){

      return Booking::where('users_id',$user_id)
      ->where('status','=',2)
      ->with('users')
      ->with(["bus" => function($bs){
          $bs->with('BusType.busClass');
          $bs->with('BusSitting');                
          $bs->with('busContacts');
        } ] )
      ->with(["bookingDetail" => function($b){
              $b->with(["busSeats" => function($s){
                  $s->with("seats");
                } ]);
          } ])->orderBy('journey_dt','desc');
      

    }

    public function getReviews($pnr){
        return Review::where("pnr",$pnr)->orderBy('id','desc')->first();
    }

    public function CompletedBookings($user_id,$today){
      return Booking::where('users_id',$user_id)
            ->where('status','!=',2)
              ->where('status','!=',0)
              ->where('status','!=',4)
            ->where('journey_dt','<',$today)
            ->with('users')
            ->with(["bus" => function($bs){
                $bs->with('BusType.busClass');
                $bs->with('BusSitting');                
                $bs->with('busContacts');
              } ] )
            ->with(["bookingDetail" => function($b){
                    $b->with(["busSeats" => function($s){
                        $s->with("seats");
                      } ]);
                } ])->orderBy('journey_dt','desc');
    }

    public function UpcomingBookings($user_id,$today){
      return  Booking::where('users_id',$user_id)
              ->where('status','!=',2)
              ->where('status','!=',0)
              ->where('status','!=',4)
            ->where('journey_dt','>',$today)
            ->with('users')
            ->with(["bus" => function($bs){
                $bs->with('BusType.busClass');
                $bs->with('BusSitting');                
                $bs->with('busContacts');
              } ] )
            ->with(["bookingDetail" => function($b){
                    $b->with(["busSeats" => function($s){
                        $s->with("seats");
                      } ]);
                } ])->orderBy('journey_dt','asc');
    }

    public function DolphinBookingInfo($id){

      $ar= $this->booking->with('users')->with('bookingDetail')
            ->where('id',$id)->first();


    $bus["bus_number"]=$ar->bus_number;      
    $bus["name"]=$ar->bus_name; 

    $bus["bus_type"]["name"]='';
    $bus["bus_type"]["bus_class"]=[
        "class_name" => ""
    ];

    $bus["bus_sitting"]["name"]=""; 
    $bus["bus_contacts"]["phone"]=""; 

    $ar['bus']= $bus;

    $bookingDetail=$ar->bookingDetail;

    foreach($bookingDetail as $k => $bd){
        
        $st["seatText"]=$bd->seat_name;  
        $stx["seats"]= $st;            
        $ar['bookingDetail'][$k]["bus_seats"]=$stx;
        
    }

    return $ar;

    }

    public function AllBookings($user_id){
      return  Booking::where('users_id',$user_id)
      ->where('status','!=',0)
      ->where('status','!=',4)
      ->with('users')
     ->with(["bus" => function($bs){
         $bs->with('BusType.busClass');
         $bs->with('BusSitting');                
         $bs->with('busContacts');
       } ] )
     ->with(["bookingDetail" => function($b){
             $b->with(["busSeats" => function($s){
                 $s->with("seats");
               } ]);
         } ])->orderBy('journey_dt','desc');
    }

    public function getLocation($location_id){
      return $this->location->where('id',$location_id)->get();
    }

    public function getLocationName($location_id){
      return Location::where('id',$location_id)->first()->name;
    }

    public function UserCanReviewStatus($users_id,$pnr){
      return $this->review->where('users_id',$users_id)->where('pnr',$pnr)->get();
    }

    public function getPnrInfo($pnr){

      return $this->booking->where("pnr",$pnr)->with('bus')->first();
      
  }

    public function userReviews($user_id){
            
      return Review::where('users_id', $user_id)
               ->where('status','!=',2)
          ->with('users')
          ->orderBy('id','desc')
          ->get();

   }   
  

}   
 