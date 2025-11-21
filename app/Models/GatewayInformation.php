<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewayInformation extends Model
{
    use HasFactory;
    protected $table = 'gateway_information'; 
    protected $fillable = [
        'sender', 'contents','channel_type','service_provider','contents','created_by'
    ];
}
