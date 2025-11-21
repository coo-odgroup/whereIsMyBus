<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerQuery extends Model
{
    use HasFactory;
    protected $table = 'customer_query';
    protected $fillable = [
        'email','phone', 'query_typ','data'
    ];
}
