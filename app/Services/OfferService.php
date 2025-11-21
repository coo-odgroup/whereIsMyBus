<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Repositories\OfferRepository;
use App\Repositories\CommonRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OfferService
{
    
    protected $offerRepository;    
    protected $commonRepository; 

    public function __construct(OfferRepository $offerRepository, CommonRepository $commonRepository)
    {
        $this->offerRepository = $offerRepository;
        $this->commonRepository = $commonRepository;
    }
    public function offers($request)
    {

        $path= $this->commonRepository->getPathurls();
        $path= $path[0];

        try {
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
            return $offer;

        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }
       
    }   
    public function coupons($request)
    {
        return $this->offerRepository->coupons($request);
    }
    public function getPathUrls($request)
    {
        return $this->offerRepository->getPathUrls($request);
    }
   
}