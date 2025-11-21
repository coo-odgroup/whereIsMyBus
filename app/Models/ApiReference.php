<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiReference extends Model
{
    use HasFactory; 
    protected $table = 'api_reference';
    protected $fillable = ['name','email','phone'];
    
}
