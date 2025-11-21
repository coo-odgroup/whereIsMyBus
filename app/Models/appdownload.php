<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppDownload extends Model
{
    use HasFactory; 
    protected $table = 'appdownload';
    // public $timestamps = false;
    protected $fillable = [
        'mobileno', 'created_date', 'created_by',
    ];
}
