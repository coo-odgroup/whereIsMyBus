<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class UserBankDetails extends Model
{
    use HasFactory;
    protected $table = 'user_bank_details';
    // public $timestamps = false;
    protected $fillable = ['user_id', 'banking_name','bank_name	','ifsc_code','account_number','created_by'];
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

}
