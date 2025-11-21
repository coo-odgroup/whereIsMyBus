<?php
namespace App\Repositories;
use App\Models\Seo;
use App\Models\Location;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
class SeoRepository
{
    protected $seo;
    protected $location;
    public function __construct(Seo $seo, Location $location )
    {
       $this->seo = $seo ;
       $this->location = $location ;
    }    
    public function getAll($user_id)
    {
      return $this->seo->where('user_id', $user_id)
                       ->where('status','1')->get();
    }

    public function getLocation($location_id)
    {
      return $this->location->where('id', $location_id)->get();
    }
}