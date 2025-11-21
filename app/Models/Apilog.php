<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Apilog extends Model
{
    use HasFactory;
    protected $table = 'api_log';
    protected $fillable = ['url','method','request_body','response','user_name','user_id','created_at','updated_at'];

    public function User(){
       return $this->belongsTo(User::class);
    }
    
}
