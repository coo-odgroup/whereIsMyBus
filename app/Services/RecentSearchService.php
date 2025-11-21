<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Repositories\RecentSearchRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Support\Facades\Config;

class RecentSearchService
{
    protected $recentSearchRepository;

    public function __construct(RecentSearchRepository $recentSearchRepository)
    {
        $this->recentSearchRepository = $recentSearchRepository;
    }
  
    public function createSearch($data)
    {
        try { 
            $search = $this->recentSearchRepository->createSearch($data);
        } catch (Exception $e) { 
            Log::info($e);
            throw new InvalidArgumentException(Config::get('constants.INVALID_ARGUMENT_PASSED'));
        }
        return $search;    
    }

    public function getSearchDetails($userId)
    {
        return $this->recentSearchRepository->getSearchDetails($userId);
    }

}