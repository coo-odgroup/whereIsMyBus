<?php

namespace App\Repositories;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChannelRepository;
use App\Models\User;
use App\Models\Role;
use App\Models\UserBankDetails;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;


class UserRepository
{
    /**
     * @var User
     */
    protected $user;
    protected $userBankDetails;
    protected $channelRepository;
    /**
     * PostRepository constructor.
     *
     * @param Post $BusType
     */
    public function __construct(User $user, UserBankDetails $userBankDetails,ChannelRepository $channelRepository)
    {
        $this->user = $user;
        $this->userBankDetails = $userBankDetails;
        $this->channelRepository = $channelRepository; 
    }

    
    ///////////////////////////////////////////////Agent Registration////////////////////////////////////////////////////////////

    public function Register($request)
    {   
        $query =$this->user->where([
            ['phone', $request['phone']]  
        ]);
          // ->where('status', '1');
           
    $registeredAgent = $query->exists();
    
    if(!$registeredAgent){
        $agent = new $this->user; 
        $otp = $this->sendOtp($request);
        $agent->phone = $request['phone'];
        $agent->otp = $otp;
        $agent->save();
        return $agent;
    }else{
        $status = $this->user->where('phone',$request['phone'])->first()->status;
            switch($status){
                case '0':
                    $otp = $this->sendOtp($request);
                    $this->user->where('phone', $request['phone'])->update(array(
                        'otp' => $otp
                         ));
                    $agent = $this->user->where('phone', $request['phone'])->get();
                    return $agent[0]; 
                case '1':
                    return "Registered Agent";
            }
           
         }    
}
public function sendOtp($request){
    $otp = rand(10000, 99999);
        $sendsms = $this->channelRepository->sendSmsAgent($request,$otp);  
    return  $otp;
}
public function verifyOtp($request){

    $rcvOtp = trim($request['otp']);
    $userId = $request['userId'];
    $existingOtp = $this->user->where('id', $userId)->get('otp');
    $existingOtp = $existingOtp[0]['otp'];
    $user = $this->user->where('id', $userId)->first()->only('name');
    if(($rcvOtp=="")){
        return "";
        }
    elseif($existingOtp == $rcvOtp){

         $users = $this->user->where('id', $userId)->update(array( 'otp' => Null, ));   
         $usersDetails = $this->user->where('id', $userId)->get();
        return $usersDetails; 
    }
    else{
        return 'Inval OTP';
    }
}
public function login($request){
    $query =$this->user->where([
        ['email', $request['email']],
        ['email', '<>', null]
      ]);
    $existingUser = $query->latest()->exists(); 
    
    if($existingUser == true){
        $password = $query->first()->password; 

        if(Hash::check($request['password'], $password )){
            
            return $query->first();
        } else{
            return "pwd_mismatch";
        }     
    }else{
        return "un_registered_agent";
    }    
  }

  public function getRoles()
  {
      return Role::whereNotIn('status', [2])->get();
  }

  public function agentRegister($request)

    {    
        $users = $this->user->where('id', $request['userId'])->update(array(
                            'name' => $request['name'],
                            'email' => $request['email'],
                            'password' => bcrypt($request['password']),
                            'user_type' => 'AGENT',
                            'location' => $request['location'],
                            'adhar_no' => $request['adhar_no'],
                            'pancard_no' => $request['pancard_no'],
                            'organization_name' => $request['organization_name'],
                            'address' => $request['address'],
                            'street' => $request['street'],
                            'landmark' => $request['landmark'],
                            'city' => $request['city'],
                            'pincode' => $request['pincode'],
                            'name_on_bank_account' => $request['name_on_bank_account'],
                            'bank_name' => $request['bank_name'],
                            'ifsc_code' => $request['ifsc_code'],
                            'bank_account_no' => $request['bank_account_no'],
                            'branch_name' => $request['branch_name'],
                            'upi_id' => $request['upi_id'],
                            'email' => $request['email'],
                            'status' => "1",
                            ));
         $usersDetails = $this->user->where('id',  $request['userId'])->get();
         return $usersDetails; 

    
    }

}
