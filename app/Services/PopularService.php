<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Repositories\PopularRepository;
use App\Repositories\CommonRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Transformers\DolphinTransformer;
use App\Services\DolphinService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;


use InvalidArgumentException;

class PopularService
{
    protected $popularRepository; 
    protected $commonRepository;   
    protected $dolphinTransformer;
    protected $dolphinService;


    public function __construct(PopularRepository $popularRepository,CommonRepository $commonRepository,DolphinTransformer $dolphinTransformer, DolphinService $dolphinService)
    {
        $this->popularRepository = $popularRepository;
        $this->commonRepository = $commonRepository;
        $this->dolphinTransformer = $dolphinTransformer;
        $this->dolphinService=$dolphinService;


    }

    public function downloadApp(Request $request){
      return $this->popularRepository->downloadApp($request['phone']);
    }
    public function getPopularRoutes(Request $request)
    {
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
        return $popularRoutes;

    }
    public function getTopOperators(Request $request)
    {
      
        $busIds = $this->popularRepository->getBusIds();

           if($busIds->isEmpty()){
               return [];
           }
           else{
               foreach($busIds as $busId){
                   $bus_id = $busId->bus_id;
                   $count = $busId->count;
                    $opDetail = $this->popularRepository->getOperator($bus_id);
                    if(isset($opDetail[0])){

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
           //return collect($topOperators)->unique('operatorName')->values();
           return collect($topOperators)->unique('operatorName')->values()->skip(0)->take(20);

          // $temp = array_unique(array_column($topOperators, 'operatorName'));
          //return $unique_arr = array_intersect_key($topOperators, $temp);

    }


    public function CityPair()
    {

        $DolphinCity=$this->dolphinTransformer->GetCityPair();

        $allRoutes = array();
       
        $routenames = $this->popularRepository->getAllRoutes();

        $BusList=[];

        foreach($routenames as $route){

          //  if($route->bus->busSchedule != null ){
                $srcId = $route->source_id;
                $destId = $route->destination_id;
                $count = $route->count;
                $src = $this->popularRepository->getRoute($srcId);
                $dest= $this->popularRepository->getRoute($destId);
                
                if($src && isset($src[0]) && $dest && isset($dest[0])){

                    $allRoutes[] = array(
                        "source" => $src[0]->name,
                        "destination" => $dest[0]->name
                    );

                }  
           // }       
           
        } 

        if($DolphinCity){
            foreach($DolphinCity as $d){

                $allRoutes[] = $d;

            }
        }

      //  $allRoutes= collect($DolphinCity)->concat(collect($allRoutes))->unique();

        return $allRoutes;

    }

    
    public function allRoutes(Request $request)
    {
        $allRoutes = array();
       
        $routenames = $this->popularRepository->getAllRoutes();

        $DolphinCity=$this->dolphinService->GetCityPair();


        $BusList=[];

        foreach($routenames as $route){

                $srcId = $route->source_id;
                $destId = $route->destination_id;
                $count = $route->count;
                $src = $this->popularRepository->getRoute($srcId);
                $dest= $this->popularRepository->getRoute($destId);
                $BusList= $this->popularRepository->getBus($srcId,$destId);

                if($src && isset($src[0]) && $dest && isset($dest[0])){

                    $allRoutes[] = array(
                        "source" => $src,
                        "destination" => $dest,
                        "count" => $count,
                        "BusList" => $BusList,
                    );

                }
           
        } 

        foreach($DolphinCity as $d){

            $srcId = $d['FromCityID'];
            $destId = $d['ToCityID'];
            $count = 0;
            $src = $this->popularRepository->getDolphinRoute($srcId);
            $dest= $this->popularRepository->getDolphinRoute($destId);
            $BusList= [];

            if($src && isset($src[0]) && $dest && isset($dest[0])){

                $allRoutes[] = array(
                    "source" => $src,
                    "destination" => $dest,
                    "count" => $count,
                    "BusList" => $BusList,
                );

            }
       
    }

    if(isset($request['page_no']) && isset($request['per_page'])){

        $page_no = $request['page_no'] ;
       $per_page= $request['per_page'] ;

       $data= $this->customPaginate($allRoutes,$per_page,$page_no)->withPath('/api/allRoutes');

       return $data;

    }

    return $allRoutes;
       
      

      

    }

    public function customPaginate($items, $perPage, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function allOperators(Request $request)
    {

        $paginate = $request['paginate'];
         $filter = $request['filter']; 

         $list= $this->popularRepository->allOperators($filter);

         $list =  $list->paginate($paginate);

         $response = array(
            "count" => $list->count(), 
            "total" => $list->total(),
            "data" => $list
           ); 
          
           return $response;
    }
    public function operatorDetails(Request $request)
    {
       $path= $this->commonRepository->getPathurls();
       $path= $path[0];

       $allRoutes = array();
       $allAmenity=array();
       $allreviews=array();    
       $Totalrating=0;
          
      
        $operator_url = $request['operator_url'];
        $this->entry_date = date("Y-m-d", strtotime($request['entry_date']));

        $operatorDetails = $this->popularRepository->GetOperatorDetail($operator_url);
      
      if($operatorDetails && isset($operatorDetails[0])){
      
          $buses = $operatorDetails[0]->bus;
          $busIds =$buses->pluck('id');
      
        if(!sizeof($busIds)){
            $opNameDetails['id'] = $operatorDetails[0]->id;
            $opNameDetails['operator_name'] = $operatorDetails[0]->operator_name;
            $opNameDetails['organisation_name'] = $operatorDetails[0]->organisation_name;            
            $opNameDetails['operator_info'] = $operatorDetails[0]->operator_info; 
            $opNameDetails['buses'] = [];
            $opNameDetails['routes'] = [];
            $opNameDetails['total_rating'] = $Totalrating;
            $opNameDetails['amenities'] = $allAmenity;
            $opNameDetails['reviews'] = $allreviews;
            $opNameDetails['popularRoutes'] = [];

            return $opNameDetails;
        }else {

         $allAmenity = $this->popularRepository->GetAllBusAmenities($busIds);
         if($allAmenity)
            {
                foreach($allAmenity as $a){
                    if($a->amenities != null && isset($a->amenities->amenities_image) )
                    {
                        $a->amenities->amenities_image = $path->amenity_url.$a->amenities->amenities_image;   
                    }
                }
            }
        
         $allreviews=  $this->popularRepository->GetOperatorReviews($busIds);

         
         $Review_list=[];
 
         if(count($allreviews)>0){
             foreach($allreviews as $k => $rv){
                  $Review_list[$k]['users_id']=$rv->users_id;
                  $Review_list[$k]['title']=$rv->title;
                  $Review_list[$k]['rating_overall']=$rv->rating_overall;
                  $Review_list[$k]['rating_comfort']=$rv->rating_comfort;
                  $Review_list[$k]['rating_clean']=$rv->rating_clean;
                  $Review_list[$k]['rating_behavior']=$rv->rating_behavior;
                  $Review_list[$k]['rating_timing']=$rv->rating_timing;
                  $Review_list[$k]['comments']=$rv->comments;
                  $Review_list[$k]['name']=$rv->users->name;
                  $Review_list[$k]['district']=$rv->users->district;
                  $Review_list[$k]['profile_image']='';

               if($rv->users && $rv->users->profile_image!='' && $rv->users->profile_image!=null){
                $Review_list[$k]['profile_image']=$path->profile_url.$rv->users->profile_image;
              }

             }     
         }

         $Totalrating=  $this->popularRepository->Totalrating($busIds);

       

        //>>Find the popular routes of that Operator   
        $bookingRoutes = $this->popularRepository->GetRouteBookings($busIds);  

        if(sizeof($bookingRoutes)) {
            foreach($bookingRoutes as $bookingRoute){
                $src = $this->popularRepository->getRoute($bookingRoute['source_id']);
                $dest = $this->popularRepository->getRoute($bookingRoute['destination_id']);
                $depTime = $this->popularRepository->GetDepartureTime($bookingRoute['source_id'],$bookingRoute['destination_id'],$busIds);       
                
                $popularRoutes[] = [
                        "sourceID" => $src[0]->id, 
                        "sourceName" => $src[0]->name, 
                        "destinationID" => $dest[0]->id,
                        "destinationName" => $dest[0]->name,
                        "depTime" => date("H:i",strtotime($depTime)),
                        ];

            }  
        }else{
            $popularRoutes = [];
        } 

        //>>Find all the routes of that Operator
        $items = collect($operatorDetails)[0]->ticketPrice; 
        if(sizeof($items)){
            foreach($items as $item){
                $src = $this->popularRepository->getRoute($item['source_id']);
                $dest = $this->popularRepository->getRoute($item['destination_id']);
              
               $depTime = $this->popularRepository->GetDepartureTime($item['source_id'],$item['destination_id'],$busIds);       
             
                $allRoutes[] = [   
                    "sourceID" => $src[0]->id, 
                    "sourceName" => $src[0]->name, 
                    "destinationID" => $dest[0]->id,
                    "destinationName" => $dest[0]->name,
                    "depTime" => date("H:i",strtotime($depTime))
                ];       
            }
           
        }     

        $opNameDetails['id'] = $operatorDetails[0]->id;
        $opNameDetails['operator_name'] = $operatorDetails[0]->operator_name;
        $opNameDetails['organisation_name'] = $operatorDetails[0]->organisation_name;
        $opNameDetails['operator_info'] = $operatorDetails[0]->operator_info;
        $opNameDetails['buses'] = $buses;
        $opNameDetails['amenities'] = $allAmenity;
        $opNameDetails['reviews'] = $Review_list;        
        $opNameDetails['total_rating'] = number_format($Totalrating,2);
        $opNameDetails['routes'] = $allRoutes;
        $opNameDetails['popularRoutes'] = $popularRoutes;

        return $opNameDetails; 
     }  
        
    }else{
    return 'operator-not-found';
    }

  
}
}