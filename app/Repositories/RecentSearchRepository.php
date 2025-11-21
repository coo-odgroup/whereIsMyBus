<?php

namespace App\Repositories;
use App\Models\RecentSearch;
use App\Models\Users;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;


class RecentSearchRepository
{
   
    protected $recentSearch;
    protected $users;

    
    public function __construct(RecentSearch $recentSearch, Users $users)
    {
        $this->recentSearch = $recentSearch;
        $this->users = $users;
    }

    public function createSearch($data)
    {
  
        $userId = $this->users->where('id',$data['users_id'])->first('id');
        $post = new $this->recentSearch;
        $post->source = $data['source'];
        $post->destination  = $data['destination'];
        $post->journey_date = date('Y-m-d', strtotime($data['journey_date']));
        $userId->recentSearch()->save($post);
        return $post;
    }
    public function getSearchDetails($userId)
    {
        $searchDetails = $this->recentSearch->where('users_id', $userId)->latest()->first();
        return $searchDetails;          
    }
}