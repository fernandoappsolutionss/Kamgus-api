<?php

namespace App\Http\Middleware\Drivers;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalancedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $minimunBalance = DB::table("configurations")->where([
            ["id", "=", 4],
        ])->first()->comision;
        $userId = $request->user();    
        if(empty($userId)){
            return response(null, Controller::HTTP_UNAUTHORIZED);
        }
		if(round(calculateDriverBalance($userId->id, DB::table("transactions")), 2) < $minimunBalance){
            //$response = array('error' => true, 'msg' => 'Cuenta no esta activa, revise su correo para activarla' );
            $response = array(
                'error' => true, 
                'msg' => 'Cuenta no esta activa, su balance es insuficiente: $'.round(calculateDriverBalance($userId->id, DB::table("transactions")), 2) 
            );
            return response()->json( $response , Controller::HTTP_BAD_REQUEST );	
        }
        return $next($request);
    }
}
