<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;


class IncomingApiCompany extends Model
{
    use HasFactory; 
    protected $table = 'incoming_api_company';
    protected $fillable = ['id', 'name' ,'gst'];
}
