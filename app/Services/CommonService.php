<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Repositories\CommonRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CommonService
{
    
    protected $commonRepository;    
    protected $pathurls;    
    public function __construct(CommonRepository $commonRepository)
    {
        $this->commonRepository = $commonRepository;
       
    }
    public function getAll($request)
    {
        $path= $this->commonRepository->getPathurls();
        $path= $path[0];
        
        try {
            $today=date("Y-m-d");
            $banner=$this->commonRepository->getBanners($request['user_id'],$today);
            $socialMedia=$this->commonRepository->getSocialMedia($request['user_id']);
            $common=$this->commonRepository->getCommonSettings($request['user_id']);

            $banner_image='';
            $common_data=[];
            $social_media=[];

            if($banner && isset($banner[0]) && $banner[0]->banner_image){

                $banner= $banner[0];
                 $banner_image =  $path->banner_url.$banner->banner_image;
            }

            if($common && isset($common[0])){

                $common_data= $common[0];
                
                 if($common_data->logo_image){
                    $common_data->logo_image = $path->logo_url.$common_data->logo_image;
                 }

                 if($common_data->favicon_image){
                    $common_data->favicon_image = $path->favicon_url.$common_data->favicon_image;
                  }

                  if($common_data->footer_logo){
                    $common_data->footer_logo = $path->logo_url.$common_data->footer_logo;
                  }

                  if($common_data->og_image){
                    $common_data->og_image = $path->og_image_url.$common_data->og_image;
                  }

                  if($common_data->popup_image!=''){
                    $common_data->popup_image = $path->og_image_url.$common_data->popup_image;
                  }

            }

            if($socialMedia && isset($socialMedia[0])){
                $social_media=$socialMedia[0];
            }

            $data['banner_image']=$banner_image;
            $data['common']=$common_data;
            $data['socialMedia']=$social_media;                   
            return $data;

        } catch (Exception $e) {
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }
    }
}