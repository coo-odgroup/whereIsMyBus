<?php

namespace App\Services;


use App\Repositories\TestimonialRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Support\Facades\Config;
class TestimonialService
{
    protected $testimonialRepository;
    public function __construct(TestimonialRepository $testimonialRepository)
    {
        $this->testimonialRepository = $testimonialRepository;
    }
    public function getAll($request)
    {    
        $list= $this->testimonialRepository->getAll($request['user_id']);
        return  $list;
    }

    public function getFAQ()
    {    
        $faq = $this->testimonialRepository->getFAQ();
        return  $faq;
    }
}