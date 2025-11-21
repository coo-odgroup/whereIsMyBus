<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Models\Contacts;
use App\Repositories\ContactRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ContactService
{
    
    protected $contactRepository;    
    public function __construct(ContactRepository $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }
    public function save(Request $request)
    {
        return $this->contactRepository->save($request);
    }

    
   
}