<?php

namespace App\Http\Middleware\Drivers;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;

class UserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $status)
    {
        $user = $request->user();    
        if($user->status !== $status){
            $response = array(
                'error' => true, 
                'msg' => "El estado del usuario es: ".$user->status,
            );
            return response()->json( $response , Controller::HTTP_BAD_REQUEST );	
        }
        return $next($request);
    }
}
