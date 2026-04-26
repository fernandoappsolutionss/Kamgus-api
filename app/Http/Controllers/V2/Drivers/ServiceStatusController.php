<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Classes\K_HelpersV1;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\ServiceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //driver_notifications
    {
        //
        $user = request()->user();
        $driver = Driver::find($user->userable_id);
        $lastId = request()->last;
        $whereArray = [
            ["driver_services.driver_id", "=", $driver->id],
            //["SS.it_was_read", "=", 0],
            ["SS.servicestatetable_id", "=", $driver->id],
            ["SS.servicestatetable_type", "=", Driver::class],
        ];
        if(!empty($lastId)){
            $whereArray[] = ["SS.id", ">", $lastId];			
		}
        $states = DriverService::where($whereArray)
            ->join("service_statuses as SS", "SS.service_id", "=", "driver_services.service_id")
            ->orderBy("SS.id", "DESC");
        if(!empty(request()->c_read) && request()->c_read){			
			$response = array('error' => false, 'count' =>  $states->count());
			return response()->json( $response , self::HTTP_OK );
		}
        $response = array('error' => false, 'data' =>  $states->select([
            "SS.id as idse", 
            "SS.service_id as servicios_id", 
            "SS.status as estado", 
            "SS.created_at as fecha", 
            "driver_services.driver_id as conductor_id", 
            "SS.description as motivo", 
            "SS.it_was_read", 
            "SS.created_at as creado", 
            "SS.created_at as date", 
            DB::raw("MONTH(SS.created_at) as month"), 
            DB::raw("YEAR(SS.created_at) as year"), 
            DB::raw("DAY(SS.created_at) as day"), 
            DB::raw("TIME(SS.created_at) as time"), 
        ])->get());
        return response()->json( $response , self::HTTP_OK );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //driver_notifications
    {
        //
        DB::table("service_statuses")->where([
            ["id", "<=", $id]
        ])->update(
            [
                "it_was_read" => 1,
                "updated_at" => date("Y-m-d H:i:s"),
            ],
        );
        K_HelpersV1::getInstance()->updateUserNotification($id);
        return response()->json(null, self::HTTP_NO_CONTENT);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
