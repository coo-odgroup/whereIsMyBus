<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\MyComment;
use App\Models\Bus;
use App\Models\BusSeats;
use App\Models\Dummy;
use App\Models\BusCancelled;
use App\Models\BusOperator;
//use App\Models\BusStoppage;
use App\Models\Location;
use App\Models\Locationcode;
use App\Models\BusSchedule;



//use App\Models\BusSpecialFare;
use App\Models\SpecialFare;
// use Illuminate\Support\Facades\Validator;
// use App\Services\AppVersionService;
// use Exception;
// use Middleware;
// use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

class ArticleController extends Controller

{

    //protected $busSpecialFare;
    // protected $busCancelled;
    // protected $bus;
    // protected $specialFare;
    // protected $busOperator;
    // protected $busStoppage;
    // protected $location;
    // protected $locationcode;
    // protected $busSchedule;
    // protected $article;
    // protected $myComment;


    
    public function __construct(Bus $bus)
    {
        //$this->busSpecialFare = $busSpecialFare;
        // $this->busCancelled = $busCancelled;
        // $this->bus = $bus;
        // $this->specialFare = $specialFare;
        // $this->busOperator = $busOperator;
        // $this->busStoppage = $busStoppage;
        // $this->location = $location;
        // $this->locationcode = $locationcode;
        // $this->busSchedule = $busSchedule;
    }
    
 
    // public function testMe(Request $data){
    //     //Log:: info($data);
    //     $bus = $this->bus->find($data['bus_id']);
    //     $bus->running_cycle = $data['running_cycle'];
    //     $bus->update();
    //     //Log:: info($entryDates);
    //     //$busScheduleModels[] = "";
    //     //$entryDates[];
    //     //$entryDate = strtotime("+".$data['running_cycle']."day", strtotime($data['entry_date']));                
    //     $entryDate = $data['entry_date'];
    //     $busScheduleDate->entry_date=$entryDate;
    //         for($date=0;$date<=30;$date++) {   
               
    //             $busSchedule= new busSchedule();
    //             $busSchedule->bus_id=$data['bus_id'];
    //             $entryDate = strtotime("+".$data['running_cycle']."day", strtotime($entryDate));                
    //             $entryDate = date("Y-m-d", $entryDate);
    //             $busSchedule->entry_date=$entryDate;
    //             $busSchedule->created_by =$data['created_by'];
    //             $busSchedule->status = 1;
    //             $busScheduleModels[] =  $busSchedule;
    //         }        
    //         $bus->busSchedule()->saveMany($busScheduleModels);
            
    //         return $busScheduleModels;
    //         //return $bus;
    // }

    // public function saveBuscancel(Request $data){
    //     $recordsDatas =  $this->busCancelled->with('Bus.busSchedule')->get();
    //     //return $recordsDatas;
       
    //     $busIds = $data['bus_id'];

    //     foreach($busIds as $busId) 
    //     {
    //         $busCancelled = new BusCancelled();
    //         $busCancelled->bus_operator_id = $data['bus_operator_id'];        
    //         $busCancelled->cancelled_date = $data['cancelled_date'];
    //         $busCancelled->cancelled_by = $data['cancelled_by'];
    //         $busCancelled->status = 0;
    //         $busCancelled->reason = $data['reason'];
    //         $busCancelled->bus_id = $busId;
            
    //        // $busCancelled->save();
    //         //Log::info("Save called");
    //     }
    //     return $busCancelled;

    // }
   
    // public function manytomany()
    // {
    //    // DB::enableQueryLog();
    //     //$recordsData = $this->specialFare->with('bus')->get();
    //     $rowperpage = 10;
    //     $searchValue ="";
    //     $draw = 2;
    //     $start=0;
    //     $columnSortOrder='desc';
    //     $columnName="id";
    //     $data_arr = array();
    //     $totalRecordswithFilter=$this->specialFare->with('bus')  
    //     ->whereHas('bus', function ($query) use ($searchValue){
    //            $query->where('name', 'like', '%' .$searchValue . '%');               
    //        })->whereNotIn('status', [2])->count();
    //     $totalRecords=$this->specialFare->withCount('bus')->whereNotIn('status', [2])->count();
    //     $totalRecordData=$this->specialFare->whereHas('bus')->whereNotIn('status', [2])->get();
    //     $totalRecordCount = count($totalRecordData);
      

    //     //return $totalRecordCount;
        
    //     $recordsDatas =  $this->specialFare->with('Bus')
    //     ->orderBy($columnName,$columnSortOrder)  
    //     ->whereHas('bus', function ($query) use ($searchValue){
    //            $query->where('name', 'like', '%' .$searchValue . '%');
    //                  //->orWhere('name', 'like', '%' .$columnName . '%')
    //                  //->orWhere('name', 'like', '%' .$columnSortOrder . '%');

    //     })
    //        ->skip($start)
    //        ->take($rowperpage)
    //        ->whereNotIn('status', [2])
    //        ->get();
        

    //     //return $recordsDatas;
        
    //    // $quries = DB::getQueryLog();
    //     //Log::info($quries);

    //     $data_arr = array();
    //    foreach($recordsDatas as $record)
    //     {                    
    //        $buses= $record->bus;           
            
    //        $busNames="";
    //       foreach($buses as $bus)
    //        {               
    //             $busNames .=  ($busNames=="")?$bus->name:",".$bus->name;              
    //        }

    //        $data_arr[] = array(
    //         "id" => $record->id,                
    //         "seater_price" => $record->seater_price, 
    //         "sleeper_price" => $record->sleeper_price,
    //         "date" => $record->date,
    //         "created_at" => date('j M Y h:i a',strtotime($record->created_at)),
    //         "status" => $record->status,
    //         "group_code" => $record->group_code,
    //         "name"=>$busNames,
    //         "iTotalRecords" => $totalRecordCount,
    //         "iTotalDisplayRecords" => $totalRecordswithFilter,  
    //        );    

         
        
    //     } 
    //     return  $data_arr;     
    // }
    // public function insertManyTomany()
    // {

    //     $spacialfare = $this->specialFare->find(3);   
    //     $busIds = [1330, 1327];
    //     $spacialfare->bus()->attach($busIds);
    // }
    // public function cancelbusDT()
    // {
    //     $rowperpage = 10;
    //     $searchValue ="";
    //     $draw = 2;
    //     $start=0;
    //     $columnSortOrder='desc';
    //     $columnName="id";
    //     //  $busRecords =  $this->busOperator->with('bus.busstoppage')
    //     // ->get();
    //      //  $busRecords =  $this->bus->with('busOperator')
    //     //  ->get();
    //     // $busRecords =  $this->busStoppage->with('bus')
    //     //   ->get();
    //     $totalRecords=$this->busCancelled->whereNotIn('status', [2])->count();
        
    //     $totalRecordswithFilter=$this->busCancelled
    //     ->with('bus.busOperator','bus.busstoppage')  
    //     ->whereHas('bus', function ($query) use ($searchValue){
    //            $query->where('name', 'like', '%' .$searchValue . '%');               
    //        })
    //        ->whereNotIn('status', [2])
    //        ->count();

    //     $busRecords =  $this->busCancelled->with('bus.busOperator','bus.busstoppage')
    //     ->orderBy($columnName,$columnSortOrder)
    //     ->whereHas('bus', function ($query) use ($searchValue){
    //     $query->where('name', 'like', '%' .$searchValue . '%');          
    // })
    //     ->skip($start)
    //     ->take($rowperpage)
    //     ->whereNotIn('status', [2])
    //     ->get();
    //    $data_arr = array();
    //    $bus_stoppage = array();
    //    $response = array();
    //     foreach($busRecords as $record){
    //         $id = $record->id;
    //         $busRecord  = $record->bus;
    //         $name = $busRecord->name;
    //         $name = $name." >> ".$busRecord->bus_number;
    //         $operatorName = $busRecord->busOperator->operator_name;
    //         $bStoppages = $busRecord->busstoppage;
    //         $cancelled_date = $record->cancelled_date;
    //         $reason = $record->reason;
    //         $cancelled_by = $record->cancelled_by;
    //         $status = $record->status;
    //         foreach($bStoppages as $bStoppage){
    //             $sourceId = $bStoppage->source_id;
    //             $destinationId = $bStoppage->destination_id;
    //             $stoppageName = $this->location->whereIn('id', array($sourceId, $destinationId))->get('name');
                
    //             $bus_stoppage[] = array(
    //                 "sourceName" => $stoppageName,
    //                 "destinationName" => $stoppageName                   
    //             );
    //         }
         
    //     $data_arr[] = array(
    //         "id" => $id,
    //         "cancelled_date" => $cancelled_date,
    //         "reason" => $reason,
    //         "cancelled_by" => $cancelled_by,
    //         "operatorName" => $operatorName,
    //         "name" => $name,
    //         "sourceId" => $sourceId,
    //         "busStoppgae"=>$bus_stoppage,
    //         "routes" => $stoppageName,
    //         "Stoppage" =>$bStoppage->bus_id,
    //         "status" => $status         
            
    //     );
    //     $response = array(
    //         "draw" => intval($draw),
    //         "iTotalRecords" => $totalRecords,
    //         "iTotalDisplayRecords" => $totalRecordswithFilter,
    //         "aaData" => $data_arr
    //     ); 
    // }
    //   //return ($busRecords);
    //    //return ($data_arr);
    //     //return $busRecords->bus;
    //     return ($response);
    // }
     
    // public function insertOneToMany(Request $data){
    //     $this->location->name= $data['name'];
    //     $this->location->synonym= $data['synonym'];
    //     $this->location->created_by= $data['created_by'];
    //     $this->location->save();
    //     $locationModels = [];
    //      //TOD Latter,Write Enhanced Query
    //     foreach ($data['locationcode'] as $lCode) {
    //     $locationModels[] = new Locationcode($lCode);
    //     }
    //     $this->location->locationcode()->saveMany($locationModels);
    //     return $data['locationcode'];      
    // }
    // public function updateOneToMany(Request $data){
    //     //Find The record to update
    //     $this->location = $this->location->find(1802);
    //     $this->location->locationcode()->delete();
        
    //     $locationModels = [];
    //      //TOD Latter,Write Enhanced Query
    //     foreach ($data['locationcode'] as $lCode) {
    //     $locationModels[] = new Locationcode($lCode);
    //     }
    //     $this->location->locationcode()->saveMany($locationModels);
    //     //return $data['locationcode'];    
    //     //return $locationModels; 
    //     return $this->location->id;

    // }
    // public function getOperatorData()
    // {
    //     $draw = 0;
    //     $start = 0;
    //     $rowperpage = 10; // Rows display per page
    //     if(!is_numeric($rowperpage))
    //     {
    //         $rowperpage=Config::get('constants.ALL_RECORDS');
    //     }
    //    // $columnIndex_arr = $request->get('order');
    //     //$columnName_arr = $request->get('columns');
    //    // $order_arr = $request->get('order');
    //     //$search_arr = $request->get('search');

    //    // $columnIndex = $columnIndex_arr[0]['column']; // Column index
    //    // $columnName = $columnName_arr[$columnIndex]['data']; // Column name
    //     //$columnSortOrder = $order_arr[0]['dir']; // asc or desc
    //    // $searchValue = $search_arr['value']; // Search value

    //     // Total records
    //     $totalRecords=$this->busCancelled->whereNotIn('status', [2])->count();
        
    //     $totalRecordswithFilter=$this->busCancelled->with('bus.busOperator','bus.busstoppage')  
    //    // ->whereHas('bus', function ($query) use ($searchValue){
    //          //  $query->where('name', 'like', '%' .$searchValue . '%');               
    //        //})
    //        ->whereNotIn('status', [2])->count();
      
       
       
    //        // Fetch records
    
    //    $busRecords =  $this->busCancelled->with('bus.busOperator','bus.busstoppage')
    //   // ->orderBy($columnName,$columnSortOrder)
    //   // ->whereHas('bus', function ($query) use ($searchValue){
    //    // $query->where('name', 'like', '%' .$searchValue . '%');                   
    // //})
    //    ->skip($start)
    //    ->take($rowperpage)
    //    ->whereNotIn('status', [2])
    //    ->get();
       
    //     $data_arr = array();
    //     $bus_stoppage = array();
       
    //     foreach($busRecords as $record){
    //         $id = $record->id;
    //         $busRecord  = $record->bus;
    //         $name = $busRecord->name;
    //         $name = $name." >> ".$busRecord->bus_number;
    //         $operatorName = $busRecord->busOperator->operator_name;
    //         $bStoppages = $busRecord->busstoppage;
    //         $cancelled_date = $record->cancelled_date;
    //         $reason = $record->reason;
    //         $cancelled_by = $record->cancelled_by;
    //         $status = $record->status;
    //         $stoppageName="";   
    //         $routesdata="";         
    //         foreach($bStoppages as $bStoppage){            
                          
    //             $sourceId = $bStoppage->source_id;
    //             $destinationId = $bStoppage->destination_id;               
    //             $stoppageName = $this->location->whereIn('id', array($sourceId, $destinationId))->get('name');
                
    //             $bus_stoppage[] = array(
    //                 "sourceName" => $stoppageName,
    //                 "destinationName" => $stoppageName,
    //             );
    //            $routesdata =  $stoppageName[0]['name']."-".$stoppageName[1]['name'];
              
    //         }  
            
            
    //     $data_arr[] = array(
    //         "id" => $id,
    //         "cancelled_date" => $cancelled_date,
    //         "reason" => $reason,
    //         "cancelled_by" => $cancelled_by,
    //         "operatorName" => $operatorName,
    //         "name" => $name,
    //         //"name" => $name." >> ".$busRecord->bus_number,           
    //       //  "busStoppgae"=>$bus_stoppage,
    //         "routes" => $routesdata,           
    //         "status" => $status           
    //     );
    //     }
    //     $response = array(
    //         "draw" => intval($draw),
    //         "iTotalRecords" => $totalRecords,
    //         "iTotalDisplayRecords" => $totalRecordswithFilter,
    //         "aaData" => $data_arr
    //     ); 
    
    //     return ($response);

    // }

    public function getBusSeats(Request $request)
    {
        $entry_date = $request['entry_date'];
        $entry_date = date("Y-m-d", strtotime($entry_date));
        $busSeats = Bus::with(['busSeats'=> function ($bs) use ($entry_date) {
            $bs->where('operation_date', $entry_date)
               ->orwhereNull('operation_date')
               //->whereNull('operation_date');
               ->where('status',1)
               ->with(['seats' => function ($s){
                        $s->where('status',1);
                    }]);
        }])
            ->get();
            return $busSeats;


            
//////////////////////////////////////////////////////////////////////////////


        DB::connection()->enableQueryLog();
        $entry_date = $request['entry_date'];
        $entry_date = date("Y-m-d", strtotime($entry_date));
        //$busSeats = Bus::all();
        //$busSeats = BusSeats::all();
        $busSeats = Dummy::get();
        //$busSeats = Bus::with('busSeats')->get();
        return $busSeats;




        $busSeats = Bus::with(['busSeats'=> function ($bs) use ($entry_date) {
            $bs->where('operation_date', $entry_date)
               ->orwhereNull('operation_date');
               //->whereNull('operation_date');
        }])
            ->get();
         //return $busSeats;

         
         
         //$result = User:where(['status' => 1])->get();
         
         $log = DB::getQueryLog();
         
         dd($log);


        





        // $busSeats = Bus::with(['busSeats' => function ($bs) use ($entry_date) {
        //     $bs->where(['operation_date', $entry_date] )
        //         // $bs->where(function ($q) use ($entry_date){
        //         //         $q->where([['operation_date', $entry_date],['type',1]]);
        //         //     })
        //         //->orwhereNull('operation_date')
        //            ->whereNull('operation_date')
        //            ->where('status',1);
        //         //    ->with(['seats' => function ($s) {
        //         //         $s->where('status',1);
                   
        //         //     }]); 
                  
        //     }])->get();

      
    }




}