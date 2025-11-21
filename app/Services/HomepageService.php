<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Repositories\CommonRepository;
use App\Repositories\PopularRepository;
use App\Repositories\OfferRepository;
use App\Repositories\ListingRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class HomepageService
{
    
    protected $homepageRepository;    
   

    public function __construct(CommonRepository $commonRepository,OfferRepository $offerRepository,PopularRepository $popularRepository,ListingRepository $listingRepository)
    {

        $this->commonRepository = $commonRepository;
        $this->offerRepository = $offerRepository;
        $this->popularRepository = $popularRepository;
        $this->listingRepository = $listingRepository;
    }
    public function homePage($request)
    {
        $path= $this->commonRepository->getPathurls();
        $path= $path[0];
        
        try {
            $today=date("Y-m-d");
            $banner = $this->commonRepository->getBanners($request['user_id'],$today);
            $socialMedia = $this->commonRepository->getSocialMedia($request['user_id']);
            $common = $this->commonRepository->getCommonSettings($request['user_id']);

            $banner_image ='';
            $common_data =[];
            $social_media =[];
            
             // log::info($banner);
             // log::info($socialMedia);
             // log::info($common);

            $current_time=date("Y-m-d H:i:s");

           // log::info($current_time);

            if($banner){  // && isset($banner[0]) && $banner[0]->banner_image

                foreach($banner as $b){

                    //log::info($b->start_time."----".$b->end_time);

                    $current_time=strtotime($current_time);
                    $start_time=strtotime($b->start_time);
                    $end_time=strtotime($b->end_time);

                   // log::info($start_time."<=".$current_time.">=".$end_time);

                    if($start_time <= $current_time && $current_time <= $end_time){
                        $banner_image =  $path->banner_url.$b->banner_image;
                    }
                   
                }               
            }

            if($banner_image==''){

                if(count($banner)!=0){
                     $banner_image = $path->banner_url.$banner[count($banner)-1]->banner_image;
                }
               
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
            }

            if($socialMedia && isset($socialMedia[0])){
                $social_media=$socialMedia[0];
            }

            $offer = $this->offerRepository->offers($request);


           
            if($offer){
                foreach($offer as $o){
                    $o->slider_photo= $path->sliderphoto_url.$o->slider_photo;
                    if($o->android_image){
                        $o->android_image= $path->sliderphoto_url.$o->android_image;
                    }else{
                        $o->android_image='';
                    }
                    
                }
            }

            $popularRoutes = array();

            $routenames = $this->popularRepository->getRoutes();



            foreach($routenames as $route){
            $srcId = $route->source_id;
            $destId = $route->destination_id;
            $count = $route->count;
            $src = $this->popularRepository->getRoute($srcId);
            $dest = $this->popularRepository->getRoute($destId);
                $popularRoutes[] = array(
                    "source" => $src,
                    "destination" => $dest,
                    "count" => $count
                );
            } 

            $busIds = $this->popularRepository->getBusIds();

            if($busIds->isEmpty()){
               $topOperators=[];
            }
            else{
                foreach($busIds as $busId){
                   $bus_id = $busId->bus_id;
                   $count = $busId->count;
                    $opDetail = $this->popularRepository->getOperator($bus_id);

                    if($opDetail && isset($opDetail[0])){

                    $opDetail=$opDetail[0];
                   $topOperators[] = array(
                       "id" => $opDetail->busOperator->id, 
                       "operatorName" => $opDetail->busOperator->operator_name, 
                       "organisation_name" => $opDetail->busOperator->organisation_name, 
                       "operator_url" => $opDetail->busOperator->operator_url, 
                       "count" => $count
                       );
                    }
                } 
            }
            $topOperators = collect($topOperators)->unique('operatorName')->values()->skip(0)->take(20);
           
            $getLocationName = $this->listingRepository->getLocation($request['locationName']);  

            //log::Info($getLocationName);

            $data['locationName'] = $getLocationName;
            $data['banner_image'] = $banner_image;
            $data['common'] = $common_data;
            $data['socialMedia'] = $social_media; 
            $data['offers'] = $offer; 
            $data['popularRoutes'] = $popularRoutes;   
            $data['topOperators'] = $topOperators;   

            

            return $data;

        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }   
    }   
}