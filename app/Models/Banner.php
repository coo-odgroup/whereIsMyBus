<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;


class Banner extends Model
{
    use HasFactory; 
    protected $table = 'banner';
    protected $fillable = ['bus_id', 'bus_operator_id' ,'image','alt_tag','created_by'];
}
