<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LocationE;
use Illuminate\Support\Facades\DB;


class DTController extends Controller
{

    protected $locatione;

    public function __construct(LocationE $locatione)
    {     
        $this->locatione = $locatione;
    }

    public function coreTable(Request $request)
    {
        $filter = $request->query('filter');
        $paginate = $request->query('paginate');
        if (empty($paginate)) 
        {
            $paginate = 5;
        }

        if (!empty($filter)) {
            $products = LocationE::sortable()
                ->where('location.name', 'like', '%'.$filter.'%');                
        } else {
            $products = LocationE::sortable();              
        }
        $products =  $products->paginate($paginate);
        $response = array(
            "count" => $products->count(), 
            "total" => $products->total(),
            "data" => $products
           );   
           return $response;
    }
    public function HelloWorld(Request $data)
    {
        return "Hello";

    }
}