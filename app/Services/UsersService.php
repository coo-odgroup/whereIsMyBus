<?php

namespace App\Services;

use App\Models\Users;
use App\Repositories\UsersRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use App\Repositories\CommonRepository;
use Illuminate\Support\Arr;

class UsersService
{
   
    protected $usersRepository;
    protected $commonRepository;

    
    public function __construct(UsersRepository $usersRepository,CommonRepository $commonRepository)
    {
        $this->usersRepository = $usersRepository;
        $this->commonRepository = $commonRepository;
    }

    public function resendOTP($request)
    {   
        $source = $request['source'];
        $isMobile = $request['isMobile'];
        $isLogin = $request['isLogin'];

        if($isLogin == 'true'){
            if(is_numeric($source)){
                $query = $userDetail = Users::where('phone', $source);
                $userDetail = $query->first();
                if(isset($userDetail)){
                    $verifiedStatus =  $userDetail->is_verified; 
                    if($verifiedStatus == 1){
                        $request->request->add(['phone' => $source]);       
                        $request->request->add(['name' => $userDetail->name]);
                        $otp = $this->usersRepository->sendOtp($request);
                        return $this->usersRepository->createOtp($query,$otp,$request);
                    } else{
                        return "un_registered";
                    } 
                }else{
                    return "record_not_found";
                }   
            }else{
                $query = Users::where('email', $source);
                $userDetail = $query->first();
                if(isset($userDetail)){
                    $verifiedStatus =  $userDetail->is_verified; 
                    if($verifiedStatus == 1){
                        $request->request->add(['email' => $source]);       
                        $request->request->add(['name' => $userDetail->name]);
                        $otp = $this->usersRepository->sendOtp($request);
                        return $this->usersRepository->createOtp($query,$otp,$request);
                    } else{
                        return "un_registered";
                    } 
                } else{
                    return "record_not_found";
                }  
            }
        }else{
            if(is_numeric($source)){
                $query = $userDetail = Users::where('phone', $source);
                $userDetail = $query->first();
                if(isset($userDetail)){
                    $request->request->add(['phone' => $source]);       
                    $request->request->add(['name' => $userDetail->name]);
                    $otp = $this->usersRepository->sendOtp($request);
                    return $this->usersRepository->createOtp($query,$otp,$request);
                }
                else{
                    return "record_not_found";
                } 
            }else{
                $query = Users::where('email', $source);
                $userDetail = $query->first();
                if(isset($userDetail)){
                    $request->request->add(['email' => $source]);       
                    $request->request->add(['name' => $userDetail->name]);
                    $otp = $this->usersRepository->sendOtp($request);
                    return $this->usersRepository->createOtp($query,$otp,$request);
                }else{
                    return "record_not_found";
                } 
            }   
        }
    }

    public function Register($request)
    {   
        
          $query = $this->usersRepository->GetUserData($request['phone'],$request['email']);

            $guestUser = $query->latest()->exists();
            
            if(!$guestUser){
                return $this->usersRepository->saveUser($request);                
            }else{
                $verifiedUser = $query->latest()->first()->is_verified;

                if($verifiedUser==0){
                    $otp = $this->usersRepository->sendOtp($request);
                    return $this->usersRepository->updateUser($query,$request['name'],$otp,$request);
                }
                else{
                        return "Existing User";
                }
            }

    }
    public function verifyOtp($request)
    { 
        $rcvOtp = trim($request['otp']);
        $userId = $request['userId'];
        $existingOtp = $this->usersRepository->getOtp($userId);

        $path= $this->commonRepository->getPathurls();
        $path= $path[0];

        if(isset($existingOtp[0])){
        $existingOtp = $existingOtp[0]['otp'];

        $user = $this->usersRepository->userInfo($userId);

            if(($rcvOtp=="")){
                return "";
                }
            elseif($existingOtp == $rcvOtp){

                if($rcvOtp!='00000'){
                    $this->usersRepository->updateOTP($userId);
                } 

                $uinfo = $this->usersRepository->GetUserDataAfterUpdate($userId);
                // if($uinfo[0]->profile_image!=null && $uinfo[0]->profile_image!=''){ 
                //     $uinfo[0]->profile_image = $path->profile_url.$uinfo[0]->profile_image;      
                // }
                if($uinfo->profile_image!=null && $uinfo->profile_image!=''){ 
                    $uinfo->profile_image = $path->profile_url.$uinfo->profile_image;      
                }
                return $uinfo;
            }
            else{
                return 'Inval OTP';
            }
        }else{
        return 'Invalid User ID';
        }   

    }
    public function login($request)
    {   

        $query = $this->usersRepository->GetUserData($request['phone'],$request['email']);
      
        $verifiedStatus = $query->latest()->first()->is_verified; 
       
        if($verifiedStatus == 1){
            $name = $query->latest()->first()->name;        
            $request->request->add(['name' => $name]);
            $otp = $this->usersRepository->sendOtp($request);
            return $this->usersRepository->createOtp($query,$otp,$request);

        } else{
            return "un_registered";
        }      

    }
    public function userProfile($request){

       $userId = $request['userId'];
       $token = $request['token']; 
       $path= $this->commonRepository->getPathurls();
       $path= $path[0];

       $userDetails = $this->usersRepository->GetuserByToken($userId,$token);
       if(isset($userDetails[0])){

        if($userDetails[0]->profile_image!=null && $userDetails[0]->profile_image!=''){ 
            $userDetails[0]->profile_image = $path->profile_url.$userDetails[0]->profile_image;      
        }
        return $userDetails;
       }else{
         return 'Invalid';
       }

    }

    
    public function AppBookingHistory($request){
       
        $user= $this->userProfile($request);
      
        if($user!='Invalid'){
          
          $user = $user[0];
                    
         $status = $request['status'];
         $filter = $request['filter'];  
 
         $today=date("Y-m-d");
 
         if($status=='Cancelled'){
             $list = $this->usersRepository->CancelledBookings($user->id);
         }
         
         else if($status=='Completed'){  
             $list = $this->usersRepository->CompletedBookings($user->id,$today); 
         }
 
         else if($status=='Upcoming'){ 
             $list = $this->usersRepository->UpcomingBookings($user->id,$today); 
         }
 
       else{
             $list = $this->usersRepository->AllBookings($user->id);  
         } 
       
       
 
         $list =  $list->get();
 
         if($list){
             foreach($list as $k => $l){
                 $l['source']=$this->usersRepository->getLocation($l->source_id);
                 $l['destination']=$this->usersRepository->getLocation($l->destination_id);

                 $l['created_date'] = date('Y-m-d',strtotime($l['created_at']));  
               
                 $l['review']= false;
                 $l['cancel']= false;
 
                 if($l->status==2){
                     $l['booking_status']= "Cancelled";
                     
                 }
                 else if($l->status!=2 && $today > $l->journey_dt){
                   
                    $review= $this->usersRepository->UserCanReviewStatus($l->users_id,$l->pnr);                   
               
                     if(isset($review[0])){
                     }else{
                       $l['review']= true;
                     }
                   
                     $l['booking_status']= "Completed";
                 }elseif($l->status!=2 && $today < $l->journey_dt){
                     $l['booking_status']= "Upcoming";
                     $l['cancel']= true;
                 }elseif($l->status!=2 && $today == $l->journey_dt){
                     $l['booking_status']= "Ongoing";
                     $l['cancel']= true;
                 }

                 if($l->user_id != 0 ){
                    $l['cancel']= false;
                 }

                 $l['review_list']=$this->usersRepository->getReviews($l->pnr);


             }
         }
           
        return $list;
          
        }else{
          return $user;
        }

    }

    public function BookingHistory($request){
      
        $user= $this->userProfile($request);
      
        if($user!='Invalid'){
          
          $user = $user[0];
                    
         $status = $request['status'];
         $paginate = $request['paginate'];
         $filter = $request['filter'];  
 
         $today=date("Y-m-d");
 
         if($status=='Cancelled'){
             $list = $this->usersRepository->CancelledBookings($user->id);
         }
         
         else if($status=='Completed'){  
             $list = $this->usersRepository->CompletedBookings($user->id,$today); 
         }
 
         else if($status=='Upcoming'){ 
             $list = $this->usersRepository->UpcomingBookings($user->id,$today); 
         }
 
       else{
             $list = $this->usersRepository->AllBookings($user->id);  
         } 
       
       
 
         $list =  $list->paginate($paginate);
 
         if($list){
             foreach($list as $k => $l){

                if($l->origin == 'DOLPHIN'){

                    $dolphin_bk= $this->usersRepository->DolphinBookingInfo($l->id);
                    $list[$k]=$dolphin_bk;

                    $l=$list[$k];

                }


                 $l['source']=$this->usersRepository->getLocation($l->source_id);
                 $l['review_list']=$this->usersRepository->getReviews($l->pnr);
                 $l['destination']=$this->usersRepository->getLocation($l->destination_id);

                 $l['created_date'] = date('Y-m-d',strtotime($l['created_at']));  
               
                 $l['review']= false;
                 $l['cancel']= false;
 
                 if($l->status==2){
                     $l['booking_status']= "Cancelled";
                     
                 }
                 else if($l->status!=2 && $today > $l->journey_dt){
                   
                    $review= $this->usersRepository->UserCanReviewStatus($l->users_id,$l->pnr);                   
               
                     if(isset($review[0])){
                     }else{
                       $l['review']= true;
                     }
                   
                     $l['booking_status']= "Completed";
                 }elseif($l->status!=2 && $today < $l->journey_dt){
                     $l['booking_status']= "Upcoming";
                     $l['cancel']= true;
                 }elseif($l->status!=2 && $today == $l->journey_dt){
                     $l['booking_status']= "Ongoing";
                     $l['cancel']= true;
                 }

                 if($l->user_id != 0 ){
                    $l['cancel']= false;
                 }


             }
         }
 
       
         $response = array(
             "count" => $list->count(), 
             "total" => $list->total(),
             "data" => $list
            ); 
           
            return $response;
          
        }else{
          return $user;
        }

    }
    public function updateProfile($request){
        $userId = $request['userId'];
        $token = $request['token']; 

        $path= $this->commonRepository->getPathurls();
        $path= $path[0];

        $userDetails=$this->usersRepository->GetuserByToken($userId,$token);
        if(isset($userDetails[0])){

            $uinfo= $this->usersRepository->updateProfile($request);

            if($uinfo->profile_image!=null && $uinfo->profile_image!=''){ 
                $uinfo->profile_image = $path->profile_url.$uinfo->profile_image;      
            }
            return $uinfo;

            
         }else{
           return 'Invalid';
         }
    }   

    public function updateProfileImage($request)
    {
        $userId = $request['id'];
        $token = $request['token']; 

        $path= $this->commonRepository->getPathurls();
        $path= $path[0];

        $userDetails=$this->usersRepository->GetuserByToken($userId,$token);

        //log::info($userDetails);exit;

        if(isset($userDetails[0]))
        {
            $uinfo= $this->usersRepository->updateProfileImage($request);

            if($uinfo->profile_image!=null && $uinfo->profile_image!=''){ 
                $uinfo->profile_image = $path->profile_url.$uinfo->profile_image;      
            }
            return $uinfo;            
         }else{
           return 'Invalid';
         }
    }   
    public function userReviews($request)
    { 
       $user= $this->userProfile($request); 
       if($user!='Invalid'){ 
         $user = $user[0];
         $userReviews =  $this->usersRepository->userReviews($user->id);
         $userReviews = collect($userReviews); 

         if($userReviews){
             foreach($userReviews as $key => $value){ 

                $pnrInfo=$this->usersRepository->getPnrInfo($value->pnr);
                 if(!empty($pnrInfo)){
                    
                    if($pnrInfo->origin=='DOLPHIN'){
                        $bus["bus_number"]=$pnrInfo->bus_number;      
                        $bus["name"]=$pnrInfo->bus_name; 
                        
                    }else{
                        $bus["bus_number"]=$pnrInfo->bus->bus_number;      
                        $bus["name"]=$pnrInfo->bus->name; 
                    }

                    $userReviews[$key]['bus']=$bus;

                    $bus["booking"]=$pnrInfo;
                    $bus["booking"]['src_name']=$this->usersRepository->getLocationName($pnrInfo->source_id);
                    $bus["booking"]['dest_name']=$this->usersRepository->getLocationName($pnrInfo->destination_id); 
                    $userReviews[$key]['bus']=$bus;

               }else{
                   unset($userReviews[$key]);
               }
            }
         }
         return $userReviews;
        }
       else{
         return $user;
       }
     }

}