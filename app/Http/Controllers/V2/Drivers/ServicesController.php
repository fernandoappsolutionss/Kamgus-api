<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Http\Controllers\Controller;
use App\Mail\StatusService;
use App\Models\Addressee;
use App\Models\ArticleService;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\Image;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TypeTransport;
use App\Models\User;
use App\Notifications\K_SendPushNotification;
use Carbon\Carbon;
use DateInterval as DInterval;
use DateTime;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

use Stripe\Transfer;

class ServicesController extends Controller
{
    private $convertSToTT = [
        'PANEL' => "Panel",
        'PICK UP' => "Pick up",
        'CAMIÓN PEQUEÑO' => "Camión Pequeño",
        'CAMIÓN GRANDE' => "Camión Grande",
        'MOTO' => "Moto",
        'SEDAN' => "Sedan",
    ];
    private $convertTTToS = [
        "Panel" => 'PANEL',
        "Pick up" => 'PICK UP',
        "Camión Pequeño" => 'CAMIÓN PEQUEÑO',
        "Camión Grande" => 'CAMIÓN GRANDE',
        "Moto" => 'MOTO',
        "Sedan" => 'SEDAN',
    ];
     /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        //$this->middleware("has_balance")->only('store');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //getAllServicesAgendado, getAllServices
    {
        //
        $user = request()->user();
        //Service::whereIn("serviceS.estado", ["RESERVA"])->;
        $serviceStatus = ["Pendiente", "Activo"]; //Search Activo because the invited services are send with that state
        if (!empty(request()->agendado)) {
            $serviceStatus = ["Reserva"];
        }
        $driverVehicles = DB::table("driver_vehicles")
            ->where("driver_id", $user->userable_id)
            ->join("types_transports", "types_transports.id", "=", "driver_vehicles.types_transport_id")
            ->groupBy("types_transports.id")
            //->limit(2)
            ->select("types_transports.*");
        //return response()->json();    
        $tdv = $driverVehicles->get()->pluck("nombre");
      
        $enum = [];
        foreach ($tdv as $key => $value) {
            $enum[] = $this->convertTTToS[$value];
        }
        //return response()->json($enum);
        //driver_services
        
        $services = DB::table("services as S")
            ->whereIn("S.estado", $serviceStatus)
            ->whereIn("S.tipo_transporte", $enum)
            ->leftJoin("routes as R", "R.service_id", "=", "S.id")
            //->groupBy("S.id")
            ->whereNotIn(
                "S.id",
                DB::table("driver_services")
                    ->select("driver_services.service_id")
                    ->where("driver_services.driver_id", $user->userable_id)
                    ->get()
                    ->pluck("service_id")
            )
            ->whereNotIn( //Filtrar servicios con un conductor agendado
                "S.id",
                DB::table("driver_services")
                    ->select("driver_services.service_id")
                    ->where("driver_services.status", "Agendado")
                    ->get()
                    ->pluck("service_id")
            )
            ->orderBy("S.id", "DESC");
        $servicesData = $services->get($this->getServicesResponseFields());
        //$driverTimeS = (count($timeAndDistance) > 0 && empty($timeAndDistance["error"])) ? $timeAndDistance["resultado"]["duration"]["value"] : -1;
        $userLat = request()->user_lat;
        $userLng = request()->user_lng;
        
        foreach ($servicesData as $key => $service) {
            $auxService = Service::find($service->idservicios);
            $servicesData[$key]->id_tipo_camion = TypeTransport::where("nombre", $servicesData[$key]->id_tipo_camion)->first()->id;
            $images = Image::where([
                ["imageable_id", "=", $service->idservicios],
                ["imageable_type", "=", Service::class],
                ["is", "=", "service_detail"],
            ]);
            if($images->count()>0){
                $servicesData[$key]->service_image = $images->get()[0]->url;
                $servicesData[$key]->service_images = $images->get()->pluck("url");

            }
            if(!empty($userLat) && !empty($userLng)){
                //Consultar el tiempo y distancia entre el conductor y el primer punto del servicio
                $timeAndDistance = $this->calculateDistanceToUser($service->idservicios, $userLat, $userLng);
                //exit(json_encode(($timeAndDistance)));
                if(!empty($timeAndDistance["error"])){
                    //$this->response($timeAndDistance, REST_Controller::HTTP_NOT_FOUND);
                    //return json_encode($timeAndDistance);
                }
                $driverTimeV = (count($timeAndDistance) > 0 && empty($timeAndDistance["error"])) ? $timeAndDistance["resultado"]["distance"]["value"] / 1000 : -1;
                $driverTimeS = (count($timeAndDistance) > 0 && empty($timeAndDistance["error"])) ? $timeAndDistance["resultado"]["duration"]["value"] : -1;
                $servicesData[$key]->check_in = Carbon::now()
                    ->addSeconds($driverTimeS)
                    ->format("Y-m-d H:i:s");
                //$servicesData[$key]->id_tipo_camion = TypeTransport::where("nombre", $servicesData[$key]->id_tipo_camion)->first()->id;
            }
            if (!empty($auxService->user_id)) {
                # code...
                $userInfo = User::find($auxService->user_id);
                //$customer = Customer::find($userInfo->userable_id);
                $userable = $userInfo->userable;
                $servicesData[$key]->celular = $userable->telefono;
                $servicesData[$key]->passenger = $userable->nombres;
                $servicesData[$key]->customer_name = $userable->nombres . " " . $userable->apellidos;
                //$servicesData[$key]->customer_image = $customer->url_foto_perfil;
                $servicesData[$key]->articles = $this->getServiceArticles($service->idservicios, $service->tipo_translado);
            }
        }
        $response = array(
            'error' => false,
            'msg' => 'Cargando servicios',
            'articulos' => $servicesData,
            'servicios' => $servicesData
        );
        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id = null)
    {
        $user = request()->user();
        if (!empty($id)) {
            switch ($id) {
                case 'receive':
                    
                    return $this->receiveService($request, $user);
                    break;
                case 'list':
                    
                    return $this->index();
                    break;
                default:
                    # code...
                    break;
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) //getMyServices, getServiceAcceptUser, getTotalServicios, getCountServicesAgendado, getCountServicesPunto
    {
        //
        $user = request()->user();
        switch ($id) {
            case 'progress':
                $validator = Validator::make(request()->all(), [
                    'service_id' => 'required|exists:services,id'
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                }
                $serviceId = request()->service_id;
                $data = [
                    "points" => Addressee::where("service_id", $serviceId)->get("point")->pluck("point"),
                ];
                $response = array('error' => false, 'msg' => 'Progreso del servicio', 'data' => $data);
                return response()->json($response);
                break;
            case 'history': //getMyServices
                $driver = Driver::find($user->userable_id);
                $services = DriverService::where("driver_services.driver_id", $user->userable_id)
                    ->leftJoin("services as S", "driver_services.service_id", "=", "S.id")
                    ->join("users as U", "S.user_id", "=", "U.id")
                    ->join("customers as C", "U.userable_id", "=", "C.id")
                    ->leftJoin("routes as R", "R.service_id", "=", "S.id")
                    //->whereIn("S.estado", ["ACTIVO","AGENDADO","INACTIVO","PENDIENTE","RESERVA","PROGRAMAR","REPETIR"])
                    ->whereIn("driver_services.status", ["EN CURSO", "PENDIENTE", "AGENDADO", "TERMINADO"])
                    ->orderBy("driver_services.id", "DESC")
                    ->get($this->getServicesHistoryResponseFields());
                $response = array('error' => false, 'msg' => 'Historial de pagos', 'history' => $services);
                return response()->json($response);
                break;
            case 'reserved':    //getServiceAcceptUser
                $query = DB::table("driver_services as DS")
                    ->join("drivers", "DS.driver_id", "=", "drivers.id")
                    ->join("services as S", "DS.service_id", "=", "S.id")
                    ->join("users as U", "S.user_id", "=", "U.id")
                    ->join("customers as C", "U.userable_id", "=", "C.id")
                    ->join("driver_vehicles as DV", "DV.driver_id", "=", "drivers.id")
                    ->leftJoin("models as Mo", "Mo.id", "=", "DV.model_id")
                    ->leftJoin("routes as R", "R.service_id", "=", "S.id")
                    ->whereIn("S.estado", ["RESERVA", "AGENDADO"])
                    ->whereIn("DS.status", ["EN CURSO", "AGENDADO"])
                    ->where("DS.driver_id",  $user->userable_id)
                    ->orderBy("S.id", "DESC")
                    ->groupBy(["DS.id", "inicio_punto", "punto_final", "coordenas"])
                    ->select([
                        "DS.id as idconductor_servicios",
                        "S.tipo_servicio as tipo_translado",
                        "S.tipo_pago",
                        "S.precio_real as valor",
                        "R.primer_punto AS inicio_punto",
                        "R.segundo_punto AS punto_final",
                        "S.fecha_reserva AS servicio_fecha_reserva",
                        "DS.startTime AS conductor_servicios_fecha_reserva",
                        "DS.status AS conductor_servicios_estado",
                        "S.estado AS servicios_estado",
				        "S.id as servicio_id",
                        DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_segundo_punto, ",", R.longitud_segundo_punto, "\"}","]") as coordenas'),
                        DB::raw("concat(C.nombres, ' ', C.apellidos) as passenger"),
                    ])
                    ;
                    $dataQuery = $query->get();
                    if( count($dataQuery) > 0 ){
                        
                        $response = array('error' => false, 'msg' => 'Historial de pagos', 'history'=> $dataQuery );
                        return response()->json( $response , self::HTTP_OK );
                    }else{
                        $response = array('error' => true, 'msg' => 'Error en carga de datos' );
                        return response()->json( $response , self::HTTP_OK );
                    }
                break;
            case 'accepted':    //getServiceAcceptUser
                $userId = $user->userable;
                /*
                $data['usuario_id'] = $userId;//Para pasarselo a userEnabledMiddleware()
                $query = $this->db->query('SELECT * FROM conductor_servicios cs 
                INNER JOIN servicios s ON cs.servicios_id = s.idservicios 
                INNER JOIN usuarios u ON s.usuarios_id = u.idusuarios 
                WHERE cs.conductores_id = "'.$userId.'" AND cs.estado="En Curso"');
                */
                //dd(DriverService::where("driver_id", $user->userable_id) ->where([
                    //["driver_services.status", "=", "En curso"]
                //])->get());
                    
                $driverService = DB::table("driver_services")->where([
                        ["driver_services.status", "=", "En curso"],
                        ["driver_services.driver_id", "=", $user->userable_id],
                    ])           
                    ->leftJoin("services as S", "S.id", "=", "driver_services.service_id")
                    ->leftJoin("users as U", "U.id", "=", "S.user_id")
                    ->leftJoin("routes as R", "R.service_id", "=", "S.id")
                    ->first($this->getActivedServicesFieldResponse());
                $data = [];
                if(!empty($driverService)){
                    //return Customer::find($driverService->user_id);
                    $driverService->celular = User::find($driverService->user_id)->userable->telefono;
                    $driverService->id_tipo_camion = TypeTransport::where("nombre", $driverService->id_tipo_camion)->first()->id;
                    $driverService->articles = $this->getServiceArticles($driverService->servicios_id, $driverService->tipo_translado);
                    $driverService->points = Addressee::where("service_id", $driverService->service_id)->get("point")->pluck("point");
                    $data = [$driverService];
                }

                $response = array('error' => false, 'msg' => 'Servicios activos', 'data' => $data);
                return response()->json($response);
                break;
            case 'finished':    //getTotalServicios
                $driverId = $user->userable->id;
                /*
                $sql = "SELECT COUNT(s.estado) as total, s.conductores_id, SUM(servicios.valor) as valor 
                FROM conductor_servicios s 
                inner join servicios on servicios.idservicios = s.servicios_id 
                WHERE s.estado=\"Terminado\" 
                    and s.conductores_id = ".$this->db->escape_str($driverId)." GROUP BY s.conductores_id";*/
                $newData[0] = [
                    "total" => 0,
                    "valor" => 0,
                ];
                $servicesCount =  DriverService::where([
                    ["status", "=", "Terminado"],
                    ["driver_services.driver_id", "=", $driverId],
                ])
                ->leftJoin("services as S", "S.id", "=", "driver_services.service_id")
                ->groupBy("driver_services.driver_id")
                ->select([
                    DB::raw("COUNT(S.estado) as total"),
                    DB::raw("SUM(S.precio_sugerido) as valor"),
                ])->first();
                if(!empty($servicesCount)){
                    $newData[0] = $servicesCount;
                }
                $response = array('error' => false, 'msg' => 'Total de servicios terminados', 'servicios'=> $newData);
				return response()->json( $response , self::HTTP_OK );
                break;
            case 'count_reserved':  //getCountServicesAgendado
                $driverId = $user->userable->id;
                /*
                $query = $this->db->query('SELECT COUNT(*) as cantidad FROM servicios s 
                    INNER JOIN usuarios u ON s.usuarios_id = u.idusuarios 
                    WHERE s.estado in("Reserva") 
                        AND s.idservicios NOT IN (SELECT p.servicio_id FROM precio_sugerido p WHERE p.usuario_id = "'.$userId.'") 
                        AND s.id_tipo_camion IN (SELECT v.tipo_camion FROM vehiculos v WHERE v.conductores_id = "'.$userId.'")');
                */
                $newData[0] = [
                    "total" => 0,
                    "valor" => 0,
                ];
                
                $transports = DriverVehicle::where("driver_id", $driverId)
                    ->join("types_transports as TT", "TT.id", "=", "driver_vehicles.types_transport_id")
                    ->get("TT.nombre")
                    ->pluck("nombre");
                $arr = [];
                foreach ($transports as $key => $value) {
                    $arr[] = $this->convertTTToS[$value];
                }
                $servicesCount = DB::table("services as S")
                    ->whereIn("S.estado", ["Reserva"])
                    ->whereNotIn("S.id", DriverService::where("driver_id", $driverId)->get()->pluck("service_id"))
                    ->whereIn("S.tipo_transporte", $arr)
                    ->whereNotIn( //Filtrar servicios con un conductor agendado
                        "S.id",
                        DB::table("driver_services")
                            ->select("driver_services.service_id")
                            ->where("driver_services.status", "Agendado")
                            ->get()
                            ->pluck("service_id")
                    )
                    ->get()
                    ;
               
                $response = array('error' => false, 'msg' => 'Cantidad de servicios', 'cantidad'=> [
                    ["cantidad" => count($servicesCount)],
                ]);
				return response()->json( $response , self::HTTP_OK );
                break;
            case 'count':   //getCountServicesPunto
                $driverId = $user->userable->id;
                $newData[0] = [
                    "total" => 0,
                    "valor" => 0,
                ];
                
                $transports = DriverVehicle::where("driver_id", $driverId)
                    ->join("types_transports as TT", "TT.id", "=", "driver_vehicles.types_transport_id")
                    //->where("driver_vehicles.status", "A")
                    ->get("TT.nombre")
                    ->pluck("nombre");
                $arr = [];
                foreach ($transports as $key => $value) {
                    $arr[] = $this->convertTTToS[$value];
                }
                $servicesCount = DB::table("services as S")
                    ->where(function ($query) {
                        $query->where('S.created_at', '>=', DB::raw("current_date()"))
                            ->orWhereRaw('(TIMESTAMPDIFF(SECOND, now(), S.created_at) >= 0 and TIMESTAMPDIFF(SECOND, now(), S.created_at) < 900)');
                    })
                    ->whereIn("S.estado", ["Pendiente"])
                    ->whereNotIn("S.id", DriverService::where("driver_id", $driverId )->get()->pluck("service_id"))
                    ->whereIn("S.tipo_transporte", $arr)
                    ->get()
                    ;
               
                $response = array('error' => false, 'msg' => 'Cantidad de servicios', 'cantidad'=> [
                    ["cantidad" => count($servicesCount)],
                ]);
				return response()->json( $response , self::HTTP_OK );
                break;

            default:
                # code...
                break;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //setServRechazado, setServRecibido, setServiceAccept, setServiceCancel, startReservedService
    {
        //
        $user = request()->user();
        if ($request->isMethod("PUT")) {
            switch ($id) {
                case 'reject':
                    $idconductor_servicios = $request->idconductor_servicios;
                    $idservice = $request->idservice;
                    $observacion = $request->observacion;
                    $last = $request->last;
                    DriverService::where([
                        ["driver_id", "=", $user->userable_id],
                        ["service_id", "=", $idservice],
                        //["id", "=", $idconductor_servicios],
                    ])->update([
                        "observation" => $observacion,
                        "status" => "Rechazado",
                    ]);
                    $service = Service::find($idservice);
                    $estado = "Cancelado";
                    if ($service->estado == "Agendado" || strtoupper($service->estado) == "AGENDADO") {
                        $estado = "Reserva";
                    }
                    $service->estado = $estado;
                    $service->save();
                    $refunded = Transaction::where("service_id", $idservice)->first();
                    if (!empty($refunded)) {
                        //Realiza el reembolso (Refund) del servicio cuando el metodo de pago fue por tarjeta
                        $this->refundStripePayment($idservice, 'El servicio fue rechazado', $last ? "El conductor no pudo entregar el pedido" : "El conductor no pudo recoger el pedido");
                        //StripeCustomClass::getInstance()
                        //    ->refundPaymentIntent($refunded->transaction_id, $service->precio_real, 'El servicio fue rechazado', $last ? "El conductor no pudo entregar el pedido" : "El conductor no pudo recoger el pedido");
                    }
                    K_HelpersV1::getInstance()->setServRechazado($request->all());
                    $customerUser = User::find(Service::find($idservice)->user_id);
                    $fcmTokens = $customerUser->fcmtokens()->orderBy("updated_at", "DESC")->get();
                    DB::table("service_statuses")->insertGetId([
                        "service_id" => $idservice,
                        "status" => "Servicio rechazado",
                        "it_was_read" => 0,
                        "description" => $last ? "El conductor no pudo entregar el pedido" : "El conductor no pudo recoger el pedido",
                        "servicestatetable_id" => $customerUser->userable_id,
                        "servicestatetable_type" => Customer::class,
                        "created_at" => date("Y-m-d H:i:s"),
                    ]);
                    foreach ($fcmTokens as $key => $fcmT) {
                        # code...
                        $fcmToken = $fcmT->token;
                        $customerUser->notify(new K_SendPushNotification('El servicio fue rechazado', $last ? "El conductor no pudo entregar el pedido" : "El conductor no pudo recoger el pedido", $fcmToken, [
                            "key" => "SERVICE_REJECT",
                            "n_date" => date("Y-m-d H:i:s")
                            //"url" => "/driver-confirmation",
                        ]));
                    }
                    //Mail::to($customerUser)->send(new StatusService('El servicio fue rechazado', $last ? "El conductor no pudo entregar el pedido" : "El conductor no pudo recoger el pedido", $estado));
                    Mail::to($customerUser)->send(new StatusService('El servicio fue rechazado', $last ? "El conductor no pudo entregar el pedido" : "El conductor no pudo recoger el pedido", "Cancelado"));
                    $response = array('error' => false, 'msg' => 'Servicio Rechazado');
                    return response()->json($response);
                    break;
                case 'receive':
                    return $this->receiveService($request, $user);
                    break;
                case 'accept':
                    try {
                        return $this->registerDriverSuggestedPrice($user, $request);
                        DB::commit();
                    } catch (\Throwable $th) {
                        //dd($th->getMessage());
                        $response = array('error' => true, 'msg' => $th->getMessage(), "line" => $th->getTraceAsString() );
                        return response()->json( $response , self::HTTP_BAD_REQUEST );	
                        DB::rollBack();
                    }
                    $response = array('error' => true, 'msg' => 'Servicio ya no esta disponible' );
                    return response()->json( $response , self::HTTP_BAD_REQUEST );	
                    break;
                case 'cancel':
                
                    $userId = $user->id;
                    $servicioId = $request->servicio_id;
                    $estado = "Rechazado";
                    
                        DB::beginTransaction();
                        
                        $driverService = DriverService::firstOrNew([
                            "service_id" => $servicioId,
                            "driver_id" => $user->userable_id,
                        ]);
                        //K_HelpersV1::getInstance()->acceptService($request->all(), $user->id, $driverTimeV);
                        K_HelpersV1::getInstance()->cancelCustomerServiceByDriver($request->all(), $user->id);
                        $driverService->startTime = date("Y-m-d H:i:s");
                        
                        $driverService->endTime = Carbon::now()->format("Y-m-d H:i:s");
                        $driverService->driver_id = $user->userable_id;
                        $driverService->status = $estado;
                        $driverService->confirmed = "No";
                        $driverService->reservation_date = date("Y-m-d H:i:s");
                        $driverService->observation = "";
                        $driverService->suggested_price = 0;
                        $driverService->ispaid = "Pendiente";
                        $driverService->commission = "";
                        //$driverService->save();
                    if( $driverService->save() ){
                        DB::commit();
                        return response()->json( [] , self::HTTP_NO_CONTENT );
                        
                    }else{
                        DB::rollBack();
                        $response = array('error' => true, 'msg' => 'ERROR al cancelar el servicio ' );
                        return response()->json( $response , self::HTTP_OK );	
                    }  
                    
                    break;
                case 'start_reserved':
                    $driverServiceId = $request->service_id;
                   
                    $driverService = DriverService::where("driver_services.driver_id", $user->userable_id)
                        ->leftJoin("services as S", "S.id", "=", "driver_services.service_id")
                        ->where("driver_services.id", $driverServiceId)->first();
                    if(!empty($driverService)){
                        $service_id = $driverService->service_id;
                        $estado = "Activo";
                        $estadoC = "En curso";
                        $updated = Service::where("id", $service_id)->update([
                            "estado" => $estado,
                        ]);
                        DriverService::where([
                            ["driver_id", "=", $user->userable_id],
                            ["service_id", "=", $service_id],
                        ])->update([
                            "status" => $estadoC,
                            "confirmed" => "SI",
                        ]);
                        $data = DB::table("services as S")
                        ->leftJoin("routes as R", "R.service_id", "=", "S.id")
                        ->where([
                            ["S.id", "=", $service_id],
                        ])
                        ->select($this->getActiveReservedServicesResponseFields())
                        ->first();
                        K_HelpersV1::getInstance()->startReservedService($request->all());
                        $data->service_image = Service::find($data->key)->image;
                        $response = array('error' => false, 'msg' => 'Cargando servicio reservado', 'data'=> $data);
                        return response()->json( $response , self::HTTP_OK );
                         
                    }

                    $response = array('error' => true, 'msg' => 'No existen servicio reservado' );
                    return response()->json($response, self::HTTP_ACCEPTED);
                    break;

                default:
                    # code...
                    break;
            }
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
        /**
         * 
         *  

         */
    }
    private function registerDriverSuggestedPrice($user, $request){
        $driverTimeV = 0;
        $driverTimeT = "";
        $validator = Validator::make($request->all(), [
            'servicio_id'    => 'required|exists:services,id',
            'precio' => 'required',
            'user_lat' => Rule::requiredIf(Service::find($request->servicio_id)->estado != "RESERVA"),
            'user_lng' => Rule::requiredIf(Service::find($request->servicio_id)->estado != "RESERVA"),
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], self::HTTP_LOCKED);
        }
        $servicioId = $request->servicio_id;
        $userLat = $request->user_lat;
        $userLng = $request->user_lng;
        $precio = $request->precio;
        $estado = "Pendiente";
        if(!empty($userLat) && !empty($userLng)){
            $timeAndDistance = $this->calculateDistanceToUser($servicioId, $userLat, $userLng);
            //exit(json_encode(($timeAndDistance)));
            if(!empty($timeAndDistance["error"])){
                //$this->response($timeAndDistance, REST_Controller::HTTP_NOT_FOUND);
            }
            $driverTimeV = (count($timeAndDistance) > 0 && empty($timeAndDistance["error"])) ? $timeAndDistance["resultado"]["duration"]["value"] : 0;
            $driverTimeT = (count($timeAndDistance) > 0 && empty($timeAndDistance["error"])) ? $timeAndDistance["resultado"]["duration"]["text"] : "";
        }
        $dInterval = Carbon::now()->addSeconds($driverTimeV);
        DB::beginTransaction();
        
        $driverService = DriverService::firstOrNew([
            "service_id" => $servicioId,
            "driver_id" => $user->userable_id,
        ]);
        K_HelpersV1::getInstance()->acceptService($request->all(), $user->id, $driverTimeV);
        $driverService->startTime = date("Y-m-d H:i:s");
        
        $driverService->endTime = $dInterval->format("Y-m-d H:i:s");
        $driverService->driver_id = $user->userable_id;
        $driverService->status = $estado;
        $driverService->confirmed = "No";
        $driverService->reservation_date = date("Y-m-d H:i:s");
        $driverService->observation = "";
        $driverService->suggested_price = $precio;
        $driverService->ispaid = "Pendiente";
        $driverService->commission = "";
        $driverService->save();

        //Verificar que el servicio siga disponible para aceptar ofertas
        $serviceAvailable = Service::where("id", $servicioId)->whereIn("estado", ["Pendiente", "Reserva"])->count();
        if ($serviceAvailable > 0) {
            $response = array('error' => false, 'msg' => 'Su precio fue registrado para el servicio' );

            //Registrar estado del servicio
            $fcmData =$this->getDataToNewService(
                $servicioId,
                $user->userable_id,
                $precio,
                (time() + $driverTimeV) * 1000
            );
            $customerUser = User::find(Service::find($servicioId)->user_id);
            $fcmTokens = $customerUser->fcmtokens()->orderBy("updated_at", "DESC")->get();
            DB::table("service_statuses")->insertGetId([
                "service_id" => $servicioId,
                "status" => 'Estado de servicio',
                "it_was_read" => 0,
                "description" => "Un conductor tiene una oferta para el servicio.",
                "servicestatetable_id" => $customerUser->userable_id,
                "servicestatetable_type" => Customer::class,
                "created_at" => date("Y-m-d H:i:s"),
            ]);
            DB::commit();
            //Enviar notificaciones
            foreach ($fcmTokens as $key => $fcmT) {
                # code...
                $fcmToken = $fcmT->token;
                //dd($fcmToken);
                ($customerUser->notify(new K_SendPushNotification('Estado de servicio', "Un conductor tiene una oferta para el servicio.", [$fcmToken], $fcmData)));

            }
            Mail::to($customerUser)->send(new StatusService('Estado de servicio', "Un conductor tiene una oferta para el servicio.", $estado));
            return response()->json($response);
        }
    }
    private function getDriversByServiceTransportType($ttype){
        
        
        $drivers = TypeTransport::where([
            ["nombre", "=", $this->convertSToTT[$ttype]],
        ])
        ->join("driver_vehicles as DV", "types_transports.id", "=", "DV.types_transport_id")
        ->select(["DV.driver_id"])
        ;
        return $drivers;
    }
    private function getServiceArticles($serviceId, $tipo_servicio){
        if($tipo_servicio == "SIMPLE"){
            /**
             *  ArticleService::insertGetId([
                        "article_id" => $article->idarticulo,
                        "cantidad" => $article->cantidad,
                        "service_id" => $service->id,
                        "created_at" => date("Y-m-d H:i:s"),
                    ]);
                    [{
                        "idarticulo":" \r\n 2 ",
                        "cantidad":"1",
                        "nombre":"Colch\u00f3n Double \/ full o tama\u00f1o doble \/ matrimonio",
                        "m3":"3.5",
                        "m2":"1",
                        "peso":"80",
                        "tiempo":"10"
                    }]
             */
            $articles = ArticleService::where("service_id", $serviceId)->leftJoin("articles", "article_service.article_id", "=", "articles.id")
                ->get($this->getArticleResponseField());
            return $articles;
        }
        return null;
    }
    private function getServicesResponseFields()
    {
        return [
            "S.id as key",
            "S.id as idservicios",
            "S.user_id as usuarios_id",
            "S.precio_real as valor",
            //"punto_referencia",
            //"coordenas",
            "S.fecha_reserva",
            "S.tipo_servicio as tipo_translado",
            "S.estado",
            "S.created_at as creado",
            "S.kilometraje as kms",
            "S.tipo_pago",
            "S.tiempo",
            "S.tipo_transporte as id_tipo_camion",
            "S.tipo_transporte as nombre_camion",
            "S.descripcion as description",
            "S.assistant as assistant",
            "R.*",
            "R.primer_punto as inicio_punto",
            "R.segundo_punto as punto_final",
            DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_segundo_punto, ",", R.longitud_segundo_punto, "\"}","]") as coordenas'),
        ];
        /**
         * [{"coord_punto_inicio":"8.9940604,-79.5054676"},{"coord_punto_final":"8.9959791,-79.506109" }]
         */
    }
    private function getServicesHistoryResponseFields()
    {

        return [
            "driver_services.id as idconductor_servicios",
            "S.tipo_servicio as tipo_translado",
            "S.tipo_pago as tipo_pago",
            "S.precio_real as valor",
            "R.*",
            "R.primer_punto as inicio_punto",
            "R.segundo_punto as punto_final",
            "S.fecha_reserva as servicio_fecha_reserva",
            "driver_services.created_at as conductor_servicios_fecha_reserva",
            "driver_services.status as conductor_servicios_estado",
            "S.estado as servicios_estado",
            "S.id as servicio_id",
            DB::raw("concat(C.nombres, ' ', C.apellidos) as passenger"),
        ];
        /**
         * [{"coord_punto_inicio":"8.9940604,-79.5054676"},{"coord_punto_final":"8.9959791,-79.506109" }]
         */
    }
    private function getActiveReservedServicesResponseFields()
    {
        return [
            "S.id as key",
            "S.id as idservicios",
            "S.user_id as usuarios_id",
            "S.precio_real as valor",
            //"punto_referencia",
            //"coordenas",
            "S.fecha_reserva",
            "S.tipo_servicio as tipo_translado",
            "S.estado",
            "S.created_at as creado",
            "S.kilometraje as kms",
            "S.tipo_pago",
            "S.tiempo",
            "S.tipo_transporte as id_tipo_camion",
            "S.tipo_transporte as nombre_camion",
            "R.*",
            "R.primer_punto as inicio_punto",
            "R.segundo_punto as punto_final",
            DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_segundo_punto, ",", R.longitud_segundo_punto, "\"}","]") as coordenas'),
        ];
        /**
         * [{"coord_punto_inicio":"8.9940604,-79.5054676"},{"coord_punto_final":"8.9959791,-79.506109" }]
         */
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
            "S.user_id as user_id", 
        ];
    }
    private function getArticleResponseField(){
        return [
            "article_service.article_id as idarticulo",
            "article_service.cantidad as cantidad",
            "articles.name as nombre",
            "articles.m3 as m3",
        ];
    }
    private function calculateDistanceToUser($servicioId, $userLat, $userLng){
		$service = DB::table('services as S')
            ->leftJoin("routes as R", "R.service_id", "=", "S.id")
            ->where(array('S.id' => $servicioId))
            ->first("R.*");
		
		
		if(empty($service->latitud_primer_punto)){
            return ["error" => true, "msg" => "Tiempo estimado no disponible"] ;	
        }
        $dMatrix = getDistanceBetween($userLat, $userLng, $service->latitud_primer_punto, $service->longitud_primer_punto);
            
        if($dMatrix === false || $dMatrix["resultado"] == "ZERO_RESULTS" || $dMatrix["resultado"]["status"] !== "OK"){
            return ["error" => true, "msg" => "Tiempo estimado no disponible"] ;	
            
        }
        return $dMatrix;
	}

    public static function getDataOfNewService($service_id, $driver_id, $amount, $tiempo){
        return (new self())->getDataToNewService($service_id, $driver_id, $amount, $tiempo);
    }
    private function getDataToNewService($service_id, $driver_id, $amount, $tiempo){
		//consultar info de conductor
        $driver = Driver::leftJoin("images", "imageable_id", "=", Driver::raw($driver_id))
            ->where([
                ["drivers.id", "=", $driver_id],
                //["images.imageable_type", "=", Driver::class],
                //["images.is", "=", "profile"]
            ])
            ->first(['nombres as FirstName', 'apellidos as LastName', 'telefono']);
		//dd(Driver::find($driver_id));
		
		if( empty($driver) ){
            return [];
        }
        $driver = $driver;

		//consultar vehicle
        $vehicle = DriverVehicle::where("driver_id", $driver_id);
	
		if( $vehicle->count() > 0 ){
			//$response = array('error' => false, 'msg' => 'Cargando Conductor Servicio', 'conductor'=> $query->result_array());
			$vehicle = $vehicle->first();
    	}else{
    		return false;
		}
		//retornar data de notificaion al app de usuario
		$data = [
			"url" => "driver-confirmation",
			"timeOfArrival" => $tiempo,
			//"rating" => $rating,
			"avatar" => empty($driver->url_foto) ? null: $driver->url_foto,
			"name" => $driver->FirstName.' '.$driver->LastName,
			//"vehicle" => $vehicle->nombre_marca,
			"vehicle" => $vehicle->model_id,
			"plate" => $vehicle->placa,
			"country" => $vehicle->pais,
			"price" => $amount,
			"driver_id" => hash("sha256", $driver_id),
			"idservicios" => $service_id,
		];
		return $data;
	}
    /**
	 * 	Inicia un reembolso a un servicio 
	 */
	/** {"id":"re_3MBoOVEumLY5hcKQ1Kaxjwfg","object":"refund","amount":1,"balance_transaction":"txn_3MBoOVEumLY5hcKQ1K2MzH07","charge":"ch_3MBoOVEumLY5hcKQ1ea4Tr8z","created":1670284824,"currency":"usd","metadata":{"description":"El conductor no pudo recoger el pedido","subject":"El servicio fue rechazado"},"payment_intent":"pi_3MBoOVEumLY5hcKQ1CR3akd9","reason":"requested_by_customer","receipt_number":null,"source_transfer_reversal":null,"status":"succeeded","transfer_reversal":null} */
	//{"id":"re_3MBoOVEumLY5hcKQ1tYUoGwu","object":"refund","amount":1,"balance_transaction":"txn_3MBoOVEumLY5hcKQ1c2auXDl","charge":"ch_3MBoOVEumLY5hcKQ1ea4Tr8z","created":1670291898,"currency":"usd","metadata":{"description":"El conductor no pudo recoger el pedido","subject":"El servicio fue rechazado"},"payment_intent":"pi_3MBoOVEumLY5hcKQ1CR3akd9","reason":"requested_by_customer","receipt_number":null,"source_transfer_reversal":null,"status":"succeeded","transfer_reversal":null}
	private function refundStripePayment($serviceId, $title = '', $description = ''){

        $service = Service::find($serviceId);
        $serviceTransaction = Transaction::where([
            ["service_id", "=", $serviceId],
            ["gateway", "=", "stripe"],
        ])->first();
        ;
		if(!empty($serviceTransaction->transaction_id) 
            && (Str::contains($serviceTransaction->transaction_id, ["pi_", "cs_"]) !== false) 
            && (array_search($serviceTransaction->status, Transaction::SUCCESS_STATES) !== false) 
            ){
			//reembolsa valor del servicio
			//$response = json_decode('{"id":"re_3MBoOVEumLY5hcKQ1Kaxjwfg","object":"refund","amount":1,"balance_transaction":"txn_3MBoOVEumLY5hcKQ1K2MzH07","charge":"ch_3MBoOVEumLY5hcKQ1ea4Tr8z","created":1670284824,"currency":"usd","metadata":{"description":"El conductor no pudo recoger el pedido","subject":"El servicio fue rechazado"},"payment_intent":"pi_3MBoOVEumLY5hcKQ1CR3akd9","reason":"requested_by_customer","receipt_number":null,"source_transfer_reversal":null,"status":"succeeded","transfer_reversal":null}');
            $gatewayTransactionId = $serviceTransaction->transaction_id;
            $gatewayTransactionId = (strpos($serviceTransaction->transaction_id, "cs_") !== false) ? 
                StripeCustomClass::getInstance()->getPaymentIntentFromCheckoutSession($gatewayTransactionId) : $gatewayTransactionId;
            $response = StripeCustomClass::getInstance()->refundPaymentIntent($gatewayTransactionId, $service->precio_real * 100, $title, $description);	
            $userId = $service->user_id;
			$data = array(
				'usuarios_id' => $userId,
				'servicios_id' => $serviceId,
				'amount' => $service->precio_real,
				//'conductores_id' => $driverId,
				//'interfaz' => 'DEVOLVER',
				//'IsSuccess' => $response->status === 'succeeded' ? 1 : 0,
				'status' => $response["status"] == "succeeded" ? "canceled" : $response["status"],
				//'ResponseSummary' => $response->reason,
				'transaction_id' => $response["id"],
                'created_at' => $response["created"],
			);
			
			Transaction::where("id", $serviceTransaction->id)->update($data);
            
			//die(json_encode($response));
			return;
		}
		//die("Error reenvolsando servicio");
		return ;
	}
    private function receiveService(Request $request, $user){
        $idconductor_servicios = $request->idconductor_servicios;
        $point = $request->punto;
        $dService = DriverService::find($idconductor_servicios);
        $estado = $dService->status;

        /**nombre_recep
        identidad_recep */
        //register receip info
        //registrar informacion de receptor en las nueva tabla Receivers(name, doc_id, service_id, point, created_at, updated_at)
        //$addressee = new Addressee();
        $addressee = Addressee::firstOrNew([
            "service_id" => $dService->service_id,     
            "point" => $point,
        ]);
        $addressee->name = $request->nombre_recep;
        $addressee->doc_identifier = $request->identidad_recep;
        $addressee->service_id = $dService->service_id;
        $addressee->point = $point;
        $addressee->save();
        
        if($request->hasFile("selected_images")){
            if(is_array($request->file("selected_images")))
            {
                foreach ($request->file("selected_images") as $key => $value) {
                    
                    $receivedCountImages = Image::where([
                        ["imageable_id", "=", $dService->service_id],
                        ["imageable_type", "=", Service::class],
                    ])->whereIn("is", ["service_point_A", "service_point_B"])->count();
                    
                    if ($receivedCountImages < 6 && $urlImage = $this->uploadAFile($value, $dService->service_id."_".$key."_s_point_".$point)) {
                        $image = new Image();
                        $image->url = $urlImage;
                        $image->is = "service_point_".$point; 
                        $image->imageable_id = $dService->service_id;
                        $image->imageable_type = Service::class;
                        $image->save(); 
                    }
                    
                }
            }else{
                
                $receivedCountImages = Image::where([
                    ["imageable_id", "=", $dService->service_id],
                    ["imageable_type", "=", Service::class],
                ])->whereIn("is", ["service_point_A", "service_point_B"])->count();
                if ($receivedCountImages < 6 && $urlImage = $this->uploadAFile($request->file("selected_images"), $dService->service_id."_s_point_".$point)) {
                    $image = new Image();
                    $image->url = $urlImage;
                    $image->is = "service_point_".$point; 
                    $image->imageable_id = $dService->service_id;
                    $image->imageable_type = Service::class;
                    $image->save(); 
                }
                
            }
        }
        

        if ($point === "B") {
            $estado = "Terminado";
            //$query = $this->db->query('UPDATE conductor_servicios SET nombre_recep = "' . $data['nombre_recep'] . '", identidad_recep = "' . $data['identidad_recep'] . '", estado = "Terminado" WHERE idconductor_servicios = "' . $userId . '"');
            $dService->status = "Terminado";
            Service::where("id", $dService->service_id)->update([
                "estado" => $estado,
            ]);
            $dService->save();
            $response = array(
                'error' => false,
                'msg' => 'Servicio Completado',
                // "cc_c" => !empty($_FILES["selected_images"]) ? "Si": "No" 
            );
            
            K_HelpersV1::getInstance()->setServRecibido(array_merge($request->all(), ["service_id" => $dService->service_id]), $user->id);
            $customerUser = User::find(Service::find($dService->service_id)->user_id);
            $fcmTokens = $customerUser->fcmtokens()->orderBy("updated_at", "DESC")->get();
            DB::table("service_statuses")->insertGetId([
                "service_id" => $dService->service_id,
                "status" => 'Estado de servicio',
                "it_was_read" => 0,
                "description" => "El conductor ha completado el servicio.",
                "servicestatetable_id" => $customerUser->userable_id,
                "servicestatetable_type" => Customer::class,
                "created_at" => date("Y-m-d H:i:s"),
            ]);
            foreach ($fcmTokens as $key => $fcmT) {
                # code...
                $fcmToken = $fcmT->token;
                $customerUser->notify(new K_SendPushNotification('El servicio ha terminado', "El conductor ha completado el servicio.", $fcmToken, [
                    "key" => "FINISHED_SERVICE",
                    "url" => "/tipo-servicio",
                    "n_date" => date("Y-m-d H:i:s"),
                ]));
            }
            Mail::to($customerUser)->send(new StatusService('El servicio ha terminado', "El conductor ha completado el servicio.", $estado));
            return response()->json($response);
        }

        $customerUser = User::find(Service::find($dService->service_id)->user_id);
        $fcmTokens = $customerUser->fcmtokens()->orderBy("updated_at", "DESC")->get();
            DB::table("service_statuses")->insertGetId([
                "service_id" => $dService->service_id,
                "status" => 'Estado de servicio',
                "it_was_read" => 0,
                "description" => "El conductor ha llegado al siguiente punto.",
                "servicestatetable_id" => $customerUser->userable_id,
                "servicestatetable_type" => Customer::class,
                "created_at" => date("Y-m-d H:i:s"),
            ]);
            foreach ($fcmTokens as $key => $fcmT) {
                # code...
                $fcmToken = $fcmT->token;
                $customerUser->notify(new K_SendPushNotification('Estado de servicio', "El conductor ha llegado al siguiente punto.", $fcmToken, [
                    //"key" => "FINISHED_SERVICE",
                    //"url" => "/tipo-servicio",
                    "n_date" => date("Y-m-d H:i:s"),
                ]));
            }
            Mail::to($customerUser)->send(new StatusService('Estado de servicio', "El conductor ha llegado al siguiente punto.", $estado));
        $response = array('error' => false, 'msg' => 'El conductor ha llegado al nuevo punto de partida');
        return response()->json($response);
    }
    function uploadAFile($file, $identif, $default = null){
        $uploadfile = date('YmdHms').'_APP_'.$identif.'_photo.png';
        $location = 'public/profiles/servicios';    //Concatena ruta con nombre nuevo
        $url_imagen_foto = secure_asset("storage/profiles/servicios/$uploadfile"); //prepara ruta para obtención del archivo imagen
        $service = Service::find($identif);
       
        if ($path = Storage::putFileAs($location, $file, $uploadfile, 'public')) {
            # code...
            //return $url_imagen_foto;
            //chmod($path, 0644); it's not necessary 
            return $url_imagen_foto;           
        }
        return false;
        

    }
}
