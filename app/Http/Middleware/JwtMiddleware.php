<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            //invalid tokens
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                 return response()->json(['message'=>'Token is Invalid. Please Login', 'statusCode' => 402,'data'=> [],'success' => 'error','statusText'=>'Unauthorized'], 401);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['message'=>'Session Timed out. Please Login', 'statusCode' => 401,'data'=> [],'success' => 'error','statusText'=>'Unauthorized'], 401);
            }else{
                return response()->json(['message'=>'Authorization Token not found', 'statusCode' => 402,'data'=> [],'success' => 'error','statusText'=>'Unauthorized'], 401);
            }
        }
        
        return $next($request);
        
       
        
    }
}
