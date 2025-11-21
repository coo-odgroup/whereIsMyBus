<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Repositories\ApiReferenceRepository;
use App\Models\ApiReference;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Arr;

class ApiReferenceService
{
    
    protected $apiReferenceRepository;


    public function __construct(ApiReferenceRepository $apiReferenceRepository)
    {
        $this->apiReferenceRepository = $apiReferenceRepository;

    }
    public function apiReference($request)
    {       
    
        $res = $this->apiReferenceRepository->apiReference($request);    
        return $res;
    }   

}