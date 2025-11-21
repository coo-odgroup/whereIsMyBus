<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pathurls extends Model
{
    use HasFactory; 
    protected $table = 'file_path_urls';
    protected $fillable = ['profile_url','safety_url','amenity_url','busphoto_url','sliderphoto_url',
    'logo_url','banner_url','favicon_url'];
    
}
