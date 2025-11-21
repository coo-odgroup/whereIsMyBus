<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Passport\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\UserBankDetails;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserNotification;
use App\Models\OdbusCharges;

class User extends Authenticatable implements JWTSubject 
{
    use HasFactory, Notifiable;
    //use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'user';
    protected $fillable = [
        'user_pin', 'first_name', 'middle_name','last_name','thumbnail','email','location','org_name','address','phone','alternate_phone','alternate_email','password', 
        'user_role','rand_key','created_by'
    ];
    public function userBankDetails()
    {
        return $this->hasMany(UserBankDetails::class);
        
    } 

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
         'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
     protected $casts = [
        'email_verified_at' => 'datetime',
     ];

     /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }    
     public function buses()
    {
        return $this->hasMany(Bus::class);    
    } 
    public function booking()
      {
            return $this->hasMany(Booking::class);   
      } 
    public function userNotification()
    {
    	 return $this->hasMany(UserNotification::class);        
    } 
    
    public function OdbusCharges()
    {
    	 return $this->hasOne(OdbusCharges::class);        
    } 
}
