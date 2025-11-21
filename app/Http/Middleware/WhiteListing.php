<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class WhiteListing
{
    use ApiResponser;  
    public $allowedIps = ['49.37.112.51','139.59.63.18','127.0.0.1','testing.odbus.co.in'];
    public function handle(Request $request, Closure $next)
    {
        $ip= $_SERVER["REMOTE_ADDR"];

       Log::Info($ip);

        if (!in_array($ip, $this->allowedIps)) {           
           return $this->errorResponse(Config::get('constants.CLIENT_UNAUTHORIZED'),Response::HTTP_UNAUTHORIZED);

        }
    
        return $next($request);
    }
}
