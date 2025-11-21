<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusOperator;




class Seo extends Model
{
	use HasFactory;
	protected $table = 'seo_setting';
	protected $fillable = [
		'seo_type',
		'source_id',
		'destination_id',
		'user_id',
		'url_description',
		'page_url',
		'meta_title',
		'meta_keyword',
		'meta_description',
		'extra_meta',
		'canonical_url',
		'status',
		'created_at',
		'updated_at',
		'created_by'
	];

	public function BusOperator()
	{        
		return $this->belongsTo(BusOperator::class);        
	} 
}


