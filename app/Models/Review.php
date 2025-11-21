<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bus;
use App\Models\Users;


class Review extends Model
{
    use HasFactory;
    protected $table = 'review';
    protected $fillable = ['pnr','bus_id','customer_id','customer_name','title','reference_key','rating_overall','rating_comfort','rating_clean','rating_behavior',
    'rating_timing','comments','created_by'];
    public function bus()
    {
    	return $this->belongsTo(Bus::class);
    }
    public function users()
    {
    	return $this->belongsTo(Users::class);
    }
}
