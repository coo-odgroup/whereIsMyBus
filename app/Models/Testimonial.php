<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusOperator;




class Testimonial extends Model
{
	use HasFactory;
	protected $table = 'testimonial';
	protected $fillable = ['posted_by','testinmonial_content','location','designation'];

	public function BusOperator()
	{        
		return $this->belongsTo(BusOperator::class);        
	} 
}


