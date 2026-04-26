<?php

namespace App\Http\Controllers\V2\WebApp;

use App\Classes\K_HelpersV1;
use App\Http\Controllers\Controller;
use App\Mail\StatusService;
use App\Models\ArticleService;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\FcmToken;
use App\Models\Service;
use App\Models\TypeTransport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;
class ServicesController extends Controller
{
    private function getServiceArticles($serviceId, $tipo_servicio){
        if($tipo_servicio == "SIMPLE"){
          
            $articles = ArticleService::where("service_id", $serviceId)->leftJoin("articles", "article_service.article_id", "=", "articles.id")
                ->get($this->getArticleResponseField());
            return $articles;
        }
        return null;
    }
    private function getArticleResponseField(){
        return [
            "article_service.article_id as idarticulo",
            "article_service.cantidad as cantidad",
            "articles.name as nombre",
            "articles.m3 as m3",
        ];
    }
    private function getActivedServicesFieldResponse(){
        return [ 
            "driver_services.*",
            "S.id as key",
            "driver_services.id as idconductor_servicios", 
            "S.id as servicios_id", 
            "driver_services.startTime as startTime", 
            "driver_services.endTime as endTime", 
            "S.estado as estado", 
            "driver_services.confirmed as confirmado", 
            "S.fecha_reserva", 
            "S.customer_id as usuarios_id", 
            "R.*",
            "R.primer_punto as inicio_punto",
            "R.segundo_punto as punto_final",
            DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_segundo_punto, ",", R.longitud_segundo_punto, "\"}","]") as coordenas'),
            "S.tipo_servicio as tipo_translado",
            "S.created_at as creado", 
            "S.kilometraje as kms",
            "S.tipo_pago", 
            "S.tiempo", 
            "S.tipo_transporte as id_tipo_camion",
            //" as puntos_coord", 
        // " as piso", 
            "driver_services.ispaid as ispagado", 
            //" as celular", 
            //" as service_image", 
            "S.descripcion as description", 
            "S.assistant", 
            "S.customer_id as user_id", 
        ];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //getServices
    {
        //
        $user = request()->user();
        if(!$user->can('historial')){
            return response(null, self::HTTP_UNAUTHORIZED);
        };
        $driverServices = DB::table("driver_services")
            //->whereNotIn("driver_services.status", ["Anulado", "Repetir"])     
            ->leftJoin("services as S", "S.id", "=", "driver_services.service_id")
            ->leftJoin("users as U", "U.id", "=", "S.user_id")
            ->leftJoin("routes as R", "R.service_id", "=", "S.id")           
            ->get($this->getActivedServicesFieldResponse());
        $data = [];
        foreach ($driverServices as $key => $driverService) {
            if(!empty($driverService)){
                $customer = Customer::find($driverService->user_id);
                $driverService->celular = null;
                if(!empty($customer)){
                    $driverService->celular = $customer->telefono;
                }
                $driverService->id_tipo_camion = TypeTransport::where("nombre", $driverService->id_tipo_camion)->first()->id;
                $driverService->articles = $this->getServiceArticles($driverService->servicios_id, $driverService->tipo_translado);
                $data[] = $driverService;
            }
        }
        if(count($data) <= 0){
            $response = array('error' => true, 'msg' => 'Error cargando servicios activos' );
            return response()->json($response);
        }
        $response = array('error' => false, 'msg' => 'Cargando servicios activos', 'services'=> $data);
        return response()->json($response);
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
    public function update(Request $request, $id)
    {
        //
        switch ($id) {
            case 'set_driver':
                $driver = User::find($request->driver_id);
                if(empty($driver)){
                    return response(null, self::HTTP_NOT_FOUND);
                }
                $response = $this->updateServiceDriver($request->service_id, $driver->userable->id);
                return response()->json($response);
                break;
            case 'transport_type':
                $response = $this->updateTransportType($request->service_id, $request->tt);
                $response = array('error' => false, 'msg' => 'Servicio modificado exitosamente', 'data'=> $response);
                return response()->json($response);
                # code...
                break;
            default:
                # code...
                break;
        }
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
    public function updateServiceDriver($serviceId, $driverId){
        $servicioId = $serviceId;
        $service = Service::find($serviceId);
        $driver = Driver::find($driverId);
        //Actualizar conductores agendados o en curso
        if(DriverService::where([
            ["service_id", "=", $serviceId],
            ["driver_id", "!=", $driverId],
            ])->whereIn("status", ["Agendado", 'En Curso'])->count() > 0){
            DriverService::where("service_id", $serviceId)->update([
                "status" => "Rechazado",
            ]);
        }
        $driverService = DriverService::firstOrNew([
            "service_id" => $servicioId,
            "driver_id" => $driverId,
        ]);
        $service->driver_id = $driverId;
        $service->save();
        //K_HelpersV1::getInstance()->acceptService($request->all(), $user->id, $driverTimeV);
        $driverService->startTime = date("Y-m-d H:i:s");
        
        $driverService->endTime = date("Y-m-d H:i:s");
        $driverService->driver_id = $driverId;
        $driverService->status = $service->estado == "AGENDADO" ? "Agendado" : 'En Curso';
        $driverService->confirmed = 'SI';
        $driverService->reservation_date = date("Y-m-d H:i:s");
        $driverService->observation = "";
        $driverService->suggested_price = $service->precio_real;
        $driverService->ispaid = "Pendiente";
        $driverService->commission = "";
        $driverService->save();
        //dd($driverService);

        $tokens = FcmToken::where("user_id", $driver->id)->get();
        foreach ($tokens as $key => $fcm) {
            $token = $fcm->token;
            notifyToDriver($token,  "Estado del servicio", "Cliente ha aceptado tu oferta", [
                //"url" => "ServActScreen",
                "key" => $service->estado == "AGENDADO" ? "SCHEDULE_ACCEPTED_PRICE" : "ACCEPTED_PRICE",
            ]);                    
        }
        K_HelpersV1::getInstance()->setCustomerNotification(User::where([
            ["userable_id", "=", $driverId],
            ["userable_type", "=", Driver::class],
        ])->first()->id, $service->id, "Estado del servicio", "Cliente ha aceptado tu oferta");
        DB::table("service_statuses")->insertGetId([
            "service_id" => $service->id,
            "status" => "Conductor activo",
            "it_was_read" => 0,
            "description" => "Cliente ha aceptado tu oferta",
            "servicestatetable_id" => $driverId,
            "servicestatetable_type" => Driver::class,
            "created_at" => date("Y-m-d H:i:s"),
        ]);
        Mail::to($driver->email)->send(new StatusService('Estado de servicio', "Cliente ha aceptado tu oferta", $service->estado));
        $driversServiceToken = $this->getServiceDriversTokenExcept($driverId, $service->id);//Obtiene los fcm token de los conductores rechazados
        if(count($driversServiceToken) > 0){
            notifyToDriver($driversServiceToken,  "Estado del servicio", "El cliente rechazo tu oferta", [
                //"url" => "ServActScreen",
                "key" => $service->estado == "AGENDADO" ? "SCHEDULE_REJECT_PRICE" : "REJECTED_PRICE",
            ]);    
        }
        return array('error' => false, 'msg' => 'Asignación de conductor enviada', 'data'=> []);
    }
    /*
        params: 
        $serviceId: Servicio del usuario.
        $driverId: Conductor seleccionado para el servicio.
    */
    public function getServiceDriversTokenExcept($driverId, $serviceId){
        //Listar precios de conductores del servicio diferentes al conductor seleccionado
        $driversService = DriverService::where([
            ["service_id", "=", $serviceId], 
            ["driver_id", "!=", $driverId]
        ])->select("driver_id")->groupBy('driver_id');
        $tokens = [];
        foreach ($driversService->cursor() as $ds) {
            $driver = User::where([
                ["userable_id", "=", $ds->driver_id],
                ["userable_type", "=", Driver::class],
            ])->first();
            $driverTokens = FcmToken::where("user_id", $driver->id)->get();
            if(count($driverTokens) > 0){
                $tokens = array_merge($tokens, $driverTokens->pluck("token")->toArray());
            }
        }
        return $tokens;
    }
    public function updateTransportType($serviceId, $tt){
        $service = Service::find($serviceId);
        $service->tipo_transporte = TypeTransport::find($tt)->nombre;
        return $service->save();


    }
    //Devuelve el conteo de los servicios activos    
    public function getCountServices(){
        //$status = empty(request()->status) ? Service::ACTIVO_STATUS : request()->status;
        $user = request()->user();

        if (!$user->can('listar todos los servicios activos')) {
            return response(null, self::HTTP_UNAUTHORIZED);
        };
        $userInfo = DB::table("services as U")
            ->whereIn("U.estado", [Service::ACTIVO_STATUS, Service::AGENDADO_STATUS])
            ->orderBy("U.id", "DESC")
            //->join("model_has_roles as MHR", "MHR.model_type", "=", "U.userable_id")
            //->leftJoin("roles as R", "MHR.role_id", "=", "R.id")
        ;
        $totalUsers = $userInfo->count();
        return response()->json([
            "error"=>false,
            "data" => [
                "count" => $totalUsers,
            ], 
        ]);
    }
}
