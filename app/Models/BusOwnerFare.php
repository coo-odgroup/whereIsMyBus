<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;

class BusOwnerFare extends Model
{
    use HasFactory;
    protected $table = 'bus_owner_fare';
}
