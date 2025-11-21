<?php

namespace App\Services;


use App\Repositories\SeoRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Support\Facades\Config;
class SeoService
{
    protected $seoRepository;
    public function __construct(SeoRepository $seoRepository)
    {
        $this->seoRepository = $seoRepository;
    }
    public function getAll($request)
    {      
        $seolist = $this->seoRepository->getAll($request['user_id']);

        $records = array();
        
        if($seolist){
            foreach($seolist as $item){
               $sourcedata = $this->seoRepository->getLocation($item->source_id);
               $destdata = $this->seoRepository->getLocation($item->destination_id);

                $records[] = array(
                    "source" => $sourcedata, 
                    "destination" => $destdata,
                    "seo_type" => $item->seo_type,
                    "user_id" =>  $item->user_id,
                    "url_description" =>  $item->url_description,
                    "page_url" =>  $item->page_url,
                    "meta_title" =>  $item->meta_title,
                    "meta_keyword" =>  $item->meta_keyword,
                    "meta_description" =>  $item->meta_description,
                    "extra_meta" =>  $item->extra_meta,
                    "canonical_url" =>  $item->canonical_url
                );        

            }

        }
        return $records;
    }
}