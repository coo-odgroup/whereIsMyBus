<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomPages extends Model
{
    use HasFactory;
    protected $table = 'custom_pages';
    protected $fillable = ['origin','type','source_id','destination_id','name','url',
    'content','meta_title','meta_keyword','meta_descriptiom','created_by'];
}
