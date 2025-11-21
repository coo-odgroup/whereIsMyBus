<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Models\Contacts;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class ContactRepository
{
    protected $contacts;

    public function __construct(Contacts $contacts)
    {
        $this->contacts = $contacts;
    }   
    
    public function save($data)
    { 
        $post = new $this->contacts;
        $post->user_id = $data['user_id'];
        $post->name  = $data['name'];
        $post->email = $data['email'];
        $post->phone = $data['phone'];
        $post->service = $data['service'];
        $post->message = $data['message'];      
        $post->save();

        return $post;
    }


}