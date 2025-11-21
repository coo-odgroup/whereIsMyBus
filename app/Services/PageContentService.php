<?php
namespace App\Services;
use App\Repositories\PageContentRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Support\Facades\Config;

class PageContentService
{
    protected $pagecontentRepository;
    public function __construct(PageContentRepository $pagecontentRepository)
    {
        $this->pagecontentRepository = $pagecontentRepository;
    }
    
    public function getAll($request)
    {      
        return $this->pagecontentRepository->getAll($request['user_id'],$request['page_url']);
    }
}