<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RecentSearch;


class RecentSearch extends Model
{
    use HasFactory; 
    protected $table = 'recent_search';
    protected $fillable = ['users_id', 'source' ,'destination','journey_date'];

    public function users()
    {
    	return $this->belongsTo(Users::class);
    }
}
