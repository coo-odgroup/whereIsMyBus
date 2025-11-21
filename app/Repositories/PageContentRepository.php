<?php
namespace App\Repositories;
use App\Models\PageContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
class PageContentRepository
{
   protected $pagecontent;
   public function __construct(PageContent $pagecontent )
   {
      $this->pagecontent = $pagecontent ;
   }    
   public function getAll($user_id,$page_url)
   {      
      return $this->pagecontent->where('user_id',$user_id)
                               ->where('page_url',$page_url)
                               ->where('status', 1)->get();
   }
}