<?php
namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HasActiveReservedService
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
        if($user->userable_type != Driver::class){
            return response("You are not a driver", 401);
        }
        $hasReservedServices = DriverService::whereIn("status", ["Agendado"])
            ->join("services", "driver_services.service_id", "=", "services.id")
            ->whereIn("services.estado", ["Agendado"])
            ->where([
                ["services.fecha_reserva", '<=', Carbon::now()->addHours(2)->format("Y-m-d H:i:s")],
                ["driver_services.driver_id", "=", $user->userable_id],
            ]);
        if($hasReservedServices->count() > 0){
            return response()->json([
                "error" => true,
                "msg" => 'Tiene un servicio agendado pendiente por iniciar: '.$hasReservedServices->get()[0]->id,
            //], Controller::HTTP_UNAUTHORIZED);
            ], Controller::HTTP_NON_AUTHORITATIVE_INFORMATION);
        }
        $response = $next($request);
        return $response;
    }
}

