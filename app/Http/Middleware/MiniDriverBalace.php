<?php
namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\Transaction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MiniDriverBalace
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
        $user = $request->user();
        $balance = calculateDriverBalance($user->id, DB::table("transactions"));
        if(round($balance, 2) < Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision){
            //return redirect()->back()->withErrors(["msg" => 'El conductor tiene balance insuficiente']);
            return response()->json(['error' => [], "msg" => "El usuario no tiene el balance minimo"], Controller::HTTP_LOCKED);
        }
        $response = $next($request);
        return $response;
    }
}

