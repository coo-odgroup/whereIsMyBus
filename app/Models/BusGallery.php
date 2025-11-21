<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;


class BusGallery extends Model
{
    use HasFactory; 
    protected $table = 'bus_gallery';
    protected $fillable = ['bus_id', 'image','alt_tag','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
}
