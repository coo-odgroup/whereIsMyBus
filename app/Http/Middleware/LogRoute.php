<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Apilog;


class LogRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected $apilog;

    public function __construct(Apilog $apilog)
    {
        $this->apilog = $apilog;
    }  

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $token = JWTAuth::getToken();

        $user = JWTAuth::toUser($token);


        if (app()->environment('local')) {
            $log = [
                'URI' => $request->getUri(),
                'METHOD' => $request->getMethod(),
                'REQUEST_BODY' => $request->all(),
                'RESPONSE' => $response->getContent()
            ];

            $api_log = new $this->apilog();
            $api_log->url = $request->getUri();
            $api_log->method = $request->getMethod();
            $api_log->request_body = json_encode($request->all());
            $api_log->response = json_encode($response->getContent());
            $api_log->user_id = $user->id;
            $api_log->user_name = $user->name;
            $api_log->save(); 

            //Log::info(json_encode($log));
        }

        $response->headers->set('Access-Control-Allow-Origin' , '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, Application','ip');
        return $response;
    }
}