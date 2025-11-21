<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\SocialMedia;
use App\Models\OdbusCharges;
use App\Models\Pathurls;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use DateTime;

class CommonRepository
{
    protected $banner;
    protected $socialMedia;
    protected $odbusCharges;
    protected $pathurls;

    public function __construct(Banner $banner, SocialMedia $socialMedia,
     OdbusCharges $odbusCharges,Pathurls $pathurls)
    {
        $this->banner = $banner;
        $this->socialMedia = $socialMedia;
        $this->odbusCharges = $odbusCharges;
        $this->pathurls = $pathurls;
    }
    
    public function getPathurls(){
        return $this->pathurls->get();
    }
    
    public function getBanners($user_id,$today)
    { 
        return $this->banner->where('user_id','=',$user_id)
                            ->where("status",1)
                            ->where("start_date","<=",$today)
                            ->where("end_date",">=",$today)
                            ->select('id','user_id','start_time','end_time','heading','occassion','category','url',
                             'banner_img','banner_image','alt_tag')
                            ->orderBy('id','DESC')
                            ->get();
        
    }

    public function getSocialMedia($user_id)
    { 
        return $this->socialMedia->where('user_id','=',$user_id)->get();
        
    }

    public function getCommonSettings($user_id)
    { 
        return $this->odbusCharges->where('user_id','=',$user_id)->get();
        
    }

   

}