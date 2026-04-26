<?php

namespace App\Http\Controllers\V2\Customers;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Events\CustomerServiceCancelled;
use App\Http\Controllers\Controller;
use App\Mail\StatusService;
use App\Models\ArticleService;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\FcmToken;
use App\Models\Image;
use App\Models\Route;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TypeTransport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Notifications\SendExpoPushNotification;
use App\Notifications\SendPushNotification;
use DateTime;
use Illuminate\Support\Facades\Storage;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    private $transportType = [
        "1" => 'PANEL',
        "2" => 'PICK UP',
        "3" => 'CAMIÓN PEQUEÑO',
        "4" => 'CAMIÓN GRANDE',
        "7" => 'MOTO',
        "8" => 'SEDAN',       
        "6" => 'SEDAN',       
    ];
    private $convertSToTT = [
        'PANEL' => "Panel",
        'PICK UP' => "Pick up",
        'CAMIÓN PEQUEÑO' => "Camión Pequeño",
        'CAMIÓN GRANDE' => "Camión Grande",
        'MOTO' => "Moto",
        'SEDAN' => "Sedan",
    ];
    private $paymentType = [
        "Efectivo" => "Efectivo",
        "Card" => "Card",
        "Tarjeta Crédito" => "Card",
        "Yappy" => "Yappy",
        
        "Transferencia" => "Transferencia",
        
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //crearServicio
    {
        DB::beginTransaction();
        $user = request()->user();
        $customer = Customer::where("id", $user->userable_id)->first();
        $rules = [
            
            'valor' => 'required|numeric|max:255',
            'inicio_punto' => 'required|max:255',
            'punto_final' => 'required|max:255',
            'coordenas' => 'required|max:255',
            'fecha_reserva' => 'nullable|max:255',
            'tipo_translado' => 'required|max:255',
            'estado' => 'required|max:255',
            'creado' => 'nullable|max:255',
            'kms' => 'nullable|max:255',
            'tipo_pago' => 'required|max:255',
            'tiempo' => 'nullable|max:255',
            'id_tipo_camion' => 'required|max:255',
            'tokenTarjeta' => 'nullable|max:255',
            'Response' => 'nullable|max:255',
            'customerId' => 'required|max:255',
            'articulos' => 'nullable|max:255',
            'ExpirationDate' => 'nullable|max:255',
            'precio' => 'required|max:255',
            'description' => 'required_if:tipo_translado,Mudanza|max:255',
            'assistant' => 'nullable|max:255',
            'image_description.*' => 'nullable|image|max:50000',
            
            'articulos' => 'required_if:tipo_translado,Simple',

        ];

        $this->validate($request, $rules);
        $assistant = empty($request->assistant) ? 0 : 1;
        if($request->tipo_translado == 'Simple'){
			$assistant = 1;
		}
        //usuarios_id:2267
        //articulos:[{"idarticulo":"96","cantidad":1,"nombre":"Lámpara de Techo","m3":"","m2":0,"peso":"0.1","tiempo":0,"tc":"5"}]
        //guardar el servicio
        $serviceO = K_HelpersV1::getInstance()->createService((array)$request->all(), $user->id);
        $ttype = is_numeric($request->id_tipo_camion) ? $this->transportType[$request->id_tipo_camion] : $request->id_tipo_camion;
        $service = new Service();
        if(!empty($serviceO)){
            $service->id = $serviceO;
        }
        //$service->tiempo = $request->tiempo / 60;
        $service->tiempo = $request->tiempo;
        //$service->kilometraje = $request->kms / 1000;
        $service->kilometraje = $request->kms;
        $service->fecha_reserva =  $request->fecha_reserva == 'now' ? Carbon::now() : Carbon::parse($request->fecha_reserva)->setTimezone(date_default_timezone_get());        
        $service->tipo_transporte = $ttype;
        $service->estado = $request->estado;
        $service->tipo_servicio = $request->tipo_translado;
        $service->precio_real = $request->valor;
        $service->precio_sugerido = $request->precio;
        $service->tipo_pago = $this->paymentType[$request->tipo_pago];
        $service->descripcion = $request->descripcion;
        $service->assistant = $assistant;
        $service->customer_id = $user->userable->id;
        $service->user_id     = $user->id;
        $service->created_at     = Carbon::now();
        $service->save();
        
        //guardar la ruta del servicio
        $latitudPrimerPunto = null;
        $longitudPrimerPunto = null;
        $latitudSegundoPunto = null;
        $longitudSegundoPunto = null;
        $coordenas = json_decode(stripcslashes(trim($request->coordenas,'"')));
        if(is_array($coordenas)){
            $pointsA = explode(",", $coordenas[0]->coord_punto_inicio);
            $pointsB = explode(",", $coordenas[1]->coord_punto_final);
            $latitudPrimerPunto = $pointsA[0];
            $longitudPrimerPunto = $pointsA[1];
            $latitudSegundoPunto = $pointsB[0];
            $longitudSegundoPunto = $pointsB[1];
        }else{
            $pointsA = explode(",", $coordenas->coord_punto_inicio);
            $pointsB = explode(",", $coordenas->coord_punto_final);
            $latitudPrimerPunto = $pointsA[0];
            $longitudPrimerPunto = $pointsA[1];
            $latitudSegundoPunto = $pointsB[0];
            $longitudSegundoPunto = $pointsB[1];            
        }

        $route = new Route();
        $route->primer_punto = $request->inicio_punto;
        $route->latitud_primer_punto = $latitudPrimerPunto;
        $route->longitud_primer_punto = $longitudPrimerPunto;
        $route->segundo_punto = $request->punto_final;
        $route->latitud_segundo_punto = $latitudSegundoPunto;
        $route->longitud_segundo_punto = $longitudSegundoPunto;
        $route->service_id = $service->id;
        $route->save();

        //Registrar articulos del servicio
        if(!empty($request->articulos) && $request->tipo_translado == "Simple"){
            //if($articles = json_decode('[{"idarticulo":" \r\n 2 ","cantidad":"1","nombre":"Colch\u00f3n Double \/ full o tama\u00f1o doble \/ matrimonio","m3":"3.5","m2":"1","peso":"80","tiempo":"10"}]')){
            if($articles = json_decode($request->articulos)){
                foreach ($articles as $key => $article) {
                    ArticleService::insertGetId([
                        "article_id" => $article->idarticulo,
                        "cantidad" => $article->cantidad,
                        "service_id" => $service->id,
                        "created_at" => date("Y-m-d H:i:s"),
                    ]);
                }
            };
        }

        //Registrar imagen del servicio
        if($request->file("image_description")){
            //$this->uploadAFile($request->file("image_description"), $service->id);
            //foreach ($request->file("selected_images") as $key => $value) {
            $imageUrls = [];
            foreach ($request->file("image_description") as $key => $value) {
                $url_imagen_foto = $this->uploadAFile($value, $service->id."_initial_".$key);
                if ($url_imagen_foto){
                    $imageUrls[] = $url_imagen_foto;
                }
            }
            foreach ($imageUrls as $key => $url) {
                $image = new Image();
                $image->url = $url;
                $image->is = "service_detail"; 
                $image->imageable_id = $service->id;
                $image->imageable_type = Service::class;
                $con[] = $image->save();    
                K_HelpersV1::getInstance()->insertImageService($serviceO, $url_imagen_foto, $user->id, 'A');
            }
        }
        $drivers = $this->getDriversByServiceTransportType($ttype)->get()->pluck("driver_id");
        $usersTokens = $this->getTokensFromDrivers($drivers);
        //foreach ($drivers as $key => $driver) {
        //    $driver->notify(new SendExpoPushNotification("Nuevo servicio", "Hay un nuevo servicio disponible"));
        //}
        $userEmails = Driver::whereIn("drivers.id", $drivers)
            ->join("users as U", "drivers.id", "=", "U.userable_id")
            ->where([
                ["U.status", "=", "Activo"],
                ["U.userable_type", "=", Driver::class],
            ])
            ->get(["U.email"]);
        if(count((array)$userEmails)){
            Mail::to($userEmails->pluck("email"))->send(new StatusService('Estado de servicio', "Hay un nuevo servicio disponible", "Pendiente"));
        }
        foreach ($drivers as $driverId) {
            $auxUser = User::where([
                ["userable_id", "=", $driverId],
                ["userable_type", "=", Driver::class],
            ])->first();
          
            K_HelpersV1::getInstance()->setCustomerNotification($auxUser->id, $service->id, "Nuevo servicio V2", "Hay un nuevo servicio disponible");
            DB::table("service_statuses")->insertGetId([
                "service_id" => $service->id,
                "status" => "Nuevo servicio",
                "it_was_read" => 0,
                "description" => "Hay un nuevo servicio disponible",
                "servicestatetable_id" => $driverId,
                "servicestatetable_type" => Driver::class,
                "created_at" => date("Y-m-d H:i:s"),
            ]);
        }
        foreach ($usersTokens as $tdriver) {
            notifyToDriver($tdriver->token, "Nuevo servicio", "Hay un nuevo servicio disponible");
            
        }
        if($route->save()){
            $response = array(
                'error' => false, 
                'msg' => 'Creando servicio', 
                'id'=> $service->id, 
                'precio_sugerido' => $service->precio_sugerido, 
                'data' => $service
                            ->where("services.id", $service->id)
                            ->leftJoin("routes as R", "R.service_id", "=", "services.id")
                            ->get($this->getServicesResponseFields()),
            );
            DB::commit();

            return response()->json($response);
        }
        DB::rollBack();
        
        $response = array(
            'error' => true, 
            'msg' => 'Error guardando servicio', 
        );
        return response()->json($response, self::HTTP_BAD_REQUEST);
    
    }
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) //suggestedServicePrices, getActiveService, isprimerservicio, getConductorActivo, getHistorialServicios
    {
        //
        $user = request()->user();
        $serviceId = $id;
		//$query = $this->db->get_where('mytable', array('id' => $id), $limit, $offset);

        if($id === "active"){
            return $this->getActiveService($user);
        }else if($id === "is_first"){
            return $this->isFirstService($user); 
        }else if($id === "driver"){
            $validator = Validator::make(request()->all(), [
                "servicio_id" => 'required|exists:services,id|exists:driver_services,service_id'
            ]);
            
            if ($validator->fails()) {
                return response()->json($validator->errors(), self::HTTP_LOCKED);
            }
            return $this->getDriverByServiceId($user, request()->servicio_id); 
        }else if($id === "history"){
            $data = $this->getServicesCompleted($user);
            $res = array('error' => false, 'msg' => 'Cargando servicios terminados', 'articulos'=> $data);
            return response()->json($res, self::HTTP_OK);
        }

		$subQuery = [];
	
        $query = DriverService::where([
            ['service_id', "=", $serviceId],
			//['status', "=", "Pendiente"],
        ])->whereIn("status", ["Pendiente", "Reserva"]);
		if( $query->count() > 0 ){
			foreach ($query->get() as $key => $value) {
				$subQuery[] = $value["driver_id"];
			}
		}
	
        //dd($subQuery);
		//if(!empty(request()->driver_id)){
		//	$params["usuario_id"] = request()->driver_id;
		//}
		$query = DriverService::where([
            ['driver_services.service_id', "=", $serviceId],
            ['u.userable_type', "=", Driver::class],
        ])
        /*
        ->select([
			"usuarios.idusuarios as driver_id",
			"concat(usuarios.nombre, ' ', usuarios.apellidos) as name",
			"usuarios.celular",
			"usuarios.email",
			"usuarios.pais_idpais",
			"p.nombre as country",
			"usuarios.url_foto as avatar",
			"usuarios.url_licencia",
			"usuarios.url_cedula",
			"usuarios.codigounico",
			"usuarios.registro",
			//"if(second(tiempo) > 30, minute(tiempo) + 1, minute(tiempo)) as timeOfArrival",
			"UNIX_TIMESTAMP(ADDTIME(now(), precio_sugerido.tiempo)) * 1000 as timeOfArrival",
			"if(avg(calificacion) is null, 0, avg(calificacion)) as rating",
			"precio_sugerido.precio as price",
			"m.nombre_marca as vehicle",
			"v.placa as plate",
			"precio_sugerido.servicio_id as idservicios",
			"precio_sugerido.created_at as created_at"
		])
        */
        ->join('drivers', 'drivers.id', "=", 'driver_services.driver_id')
        ->join('users as u', 'u.userable_id', "=", 'drivers.id')
        ->leftJoin('countries as p', 'u.country_id', "=", 'p.id')
        ->join('driver_vehicles as DV', 'DV.driver_id', "=", 'drivers.id')
        ->leftJoin("models as Mo", "Mo.id", "=", "DV.model_id")
        ->whereIn("driver_services.status", ["Pendiente"])
        ->whereIn("DV.driver_id", $subQuery)
        ->groupBy("service_id")
        ;
        //dd($query->get([DB::raw("driver_services.*")]));
        $services = DB::table("services")->where([
//            ["user_id", "=", $user->id],
            ['u.userable_type', "=", Driver::class],
            ['service_id', "=", $serviceId],
        ])
        ->whereIn("services.estado", ["Pendiente", "Reserva"])
        ->whereIn("DS.status", ["Pendiente"])
        ->whereIn("DS.driver_id", $subQuery)
        ->join("driver_services as DS", "DS.service_id", "=", "services.id")
        ->leftJoin('drivers', 'drivers.id', "=", 'DS.driver_id')
        ->leftJoin('users as u', 'u.userable_id', "=", 'drivers.id')
        ->leftJoin('countries as p', 'u.country_id', "=", 'p.id')    
        
        ->get($this->suggestedPriceField());
        foreach ($services as $key => $value) {
            /**
             * "usuarios.url_foto as avatar",
			"usuarios.url_licencia",
			"usuarios.url_cedula",
             */
            $services[$key]->fotoConductor = Driver::find($value->driver_id)->url_foto_perfil;
            $services[$key]->avatar = $services[$key]->fotoConductor;
            $driverVehicleInfo = DriverVehicle::where([
                ["driver_id", "=", $value->driver_id],
            ])->leftJoin("models as Mo", "Mo.id", "=", "driver_vehicles.model_id")->first();
            $services[$key]->vehicle = $driverVehicleInfo->name;
            $services[$key]->rating = 0;
            $services[$key]->plate = $driverVehicleInfo->plate;
        }
        //dd($services);

		if( $query->count() > 0 ){
			$response = array('error' => false, 'msg' => 'Cargando servicios activos', 'conductores'=> $services);
				return response()->json( $response , self::HTTP_OK );
		}else{
			$response = array('error' => true, 'msg' => 'Error cargando conductores activos' );
			return response()->json( $response , self::HTTP_NO_CONTENT );	
		}
    }

    /**
     * Show 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $user = request()->user();

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //postPrecioSugeridos, sendPayment, cancelService, notifyReserveService
    {
        $user = request()->user();
        $customer = Customer::where("id", $user->userable_id)->first();
        switch ($id) {
            case 'confirm_driver':
                $servicioId = $request->servicio_id;
                //$usuarioId = $request->usuario_id;
                $driverId = $request->driver_id;
                $driver = Driver::whereRaw("SHA2(id, 256) = ?", [$request->driver_id])->first();
                $service = Service::find($servicioId);
                if((!empty($request->role) && $request->role === "Administrador") 
                    || ($driverId == Service::SERVICE_ADMIN_OFFER)){// && !User::find($request->role)->hasRole("Conductor")){
                    $service->precio_real = $service->precio_sugerido;
                    if($service->estado == "PENDIENTE"){
                        $service->estado = "ACTIVO";
                    } else if($service->estado == "RESERVA"){
                        $service->estado = "AGENDADO";
                    }
                    $service->save();
                    $response = array('error' => false, 'msg' => 'La respuesta a precio sugerido fue enviada', 'data'=> []);
                    return response()->json($response, self::HTTP_OK);
                }
                $driverId = $driver->id;
                $driver = User::where([
                    ["userable_id", "=", $driverId],
                    ["userable_type", "=", Driver::class],
                ])->first();
                K_HelpersV1::getInstance()->confirmDriver((array) $request->all(), $driver->id);
                

                $driverService = DriverService::where([
                    ["service_id", "=", $servicioId],
                    ["driver_id", "=", $driverId],
                ])->first();
        
                if(empty($driverService) || empty($driverService->suggested_price)){
                    return response()->json(null, self::HTTP_BAD_REQUEST);
                }
                $service->precio_real = $driverService->suggested_price;
                if($service->estado == "PENDIENTE"){
                    $service->estado = "Activo";
                    $service->driver_id = $driverId;
                    $service->save();
                } else if($service->estado == "RESERVA"){
                    $service->estado = "AGENDADO";
                    $service->driver_id = $driverId;
                    $service->save();
                }
                $driverVehicle = $this->getDriverVehicleByServiceTransportType($service->tipo_transporte, $driverId);
                $driverService->service_id = $servicioId;
                $driverService->driver_id = $driverId;
                $driverService->startTime = date("Y-m-d H:i:s");
                $driverService->endTime = Carbon::now()->addSeconds(Carbon::parse($driverService->endTime)->diffInSeconds(Carbon::parse($driverService->startTime)))->format("Y-m-d H:i:s");
                $driverService->status = $service->estado == "AGENDADO" ? "Agendado" : 'En Curso';
                $driverService->confirmed = 'SI';
                $driverService->ispaid = 'Pendiente';
                $driverService->commission = '';
                $driverService->save();
                    //'vehiculos_id' => $driverVehicle->first()->id,
                    //'role' => 'CONDUCTOR',
                    //'fecha_reserva' => $serviceInfo->fecha_reserva,
                    //'creado' => date("Y-m-d H:i:s"),
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
                $response = array('error' => false, 'msg' => 'Enviada respuesta a precio sugerido', 'data'=> []);
                return response()->json($response, self::HTTP_OK);
                break;
            case 'init_card_payment': //sendPayment
                $userId  = $user->id;
                //$driverId  = $request->driver_id;
                $driverId = Driver::whereRaw("SHA2(id, 256) = ?", [$request->driver_id])->first()->id;
                $serviceId  = $request->service_id;
                $paymentMethodId  = $request->payment_method;
                $activeServices = Service::whereNotIn("estado", ["Anulado", "Terminado", "Cancelado", "Repetir"])->where("id", $serviceId)->orderBy("id", "DESC");
                if($activeServices->count() <= 0){
                    $response = array('error' => true, 'msg' => 'Servicio no encontrado' );
                    return response()->json($response, self::HTTP_NOT_FOUND);
                }
                $driverPrice = DriverService::where([
                    ["service_id", "=", $serviceId],
                    ["driver_id", "=", $driverId],
                ])->first()->suggested_price;
                $userInfo = User::where([
                    ["userable_id", "=", $driverId],
                    ["userable_type", "=", Driver::class],
                ])->first();
                if(!empty($userInfo)){
                    try {
                        $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
                        $porcentTaxes = ($taxes / 100.0);
                        $vTaxes = ($driverPrice * $porcentTaxes);
                        $result = StripeCustomClass::getInstance()->confirmPayment($userInfo->stripe_id, $paymentMethodId, $driverPrice * 100);
                        if($result["error"]){
                            return response()->json($result, self::HTTP_BAD_REQUEST);
                        }
                        Transaction::firstOrNew([
                            "service_id" => $serviceId,
                            //"type" => "Payment",
                        ], [
                            "amount" => $driverPrice,
                            "transaction_id" => $result["payment_intent"]->id,
                            "status" => $result["payment_intent"]->status,
                        ]);
                        K_HelpersV1::getInstance()->paymentIntent($request, $user->id, $result);
                    }  catch (\Stripe\Exception\InvalidRequestException $e) {
                        $result = [
                            "error" => true,
                            "amount" => $driverPrice * 100,
                            "msg" => $e->getError()->message
                        ];
                        return response()->json($result, self::HTTP_BAD_REQUEST);
        
                        //throw $th;
                    }
                }
                break;
            case 'cancel':
                $rules = [
                    'servicio_id' => 'exists:services,id'
                ];
                $this->validate($request, $rules);
                $service = Service::find($request->servicio_id);
                if($service->estado == "CANCELADO"){
                    $response = array('error' => true, 'msg' => 'El servicio ya fue cancelado previamente' );
                    //return response()->json( $response , self::HTTP_UNPROCESSABLE_ENTITY);
                    return response()->json( $response , self::HTTP_ACCEPTED);
                }
                K_HelpersV1::getInstance()->cancelService($service->id);
                
                return $this->cancelService($request, $user->id);
                break;
            case 'start_reserved':
                $service_id = $request->service_id;

                $service = Service::find($service_id);
                if($service->estado != "AGENDADO" && $service->estado != "ACTIVO"){
                    return response(["status" => $service->estado], self::HTTP_NOT_FOUND);
                }else
                if($service->estado == "ACTIVO"){
                    return response(["status" => $service->estado], self::HTTP_OK); //El servicio ya fue activado
                }
                DB::beginTransaction();
                try {
                    //code...
                    $service->estado = 'ACTIVO';
                    $service->save();
                    $driverService = DriverService::where([
                        ["service_id", "=", $service_id],
                        ["status", "=", "Agendado"],
                    ])->first();
                    $driverService->status = 'En Curso';
                    $driverService->save();
                    $usersTokens = Driver::whereIn("drivers.id", [$driverService->driver_id])
                        ->join("users as U", "drivers.id", "=", "U.userable_id")
                        ->join("fcm_tokens as F", "U.id", "=", "F.user_id")
                        ->where([
                            ["U.status", "=", "Activo"],
                            ["U.userable_type", "=", Driver::class],

                        ])
                        ->select(["F.token", "drivers.id", "U.email"])
                        ->get();
                    foreach ($usersTokens as $key => $user) {
                        if(!empty($user->token)){
                            notifyToDriver($user->token,  "Estado del servicio", "Cliente ha aceptado tu oferta", [
                            //"url" => "ServActScreen",
                                //"url" => "ReservasScreen",
                                "key" => "ACTIVATED_RESERVED_SERVICE",
                            ]);
                        }
                    }
                    DB::commit();
                    return response(null, self::HTTP_OK);
                } catch (\Throwable $th) {
                    DB::rollback();
                    return response($th->getMessage(), self::HTTP_BAD_REQUEST);
                }

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
    public function getActiveService($user){
        $services = Service::whereNotIn("estado", ["Anulado", "Terminado", "Cancelado", "Repetir", "Pendiente"])->where('user_id', $user->id)
            ->leftJoin("routes as R", "R.service_id", "=", "services.id")
            ->leftJoin("driver_services as DS", "services.id", "=", "DS.service_id")
            ->leftJoin("drivers as D", "DS.driver_id", "=", "D.id")
            ->orderBy("services.id", "DESC")->get($this->getHistoryServicesField());
        $response = array('error' => true, 'msg' => 'Error cargando servicios activos' );
        if(count($services) <= 0){
            return response()->json($response, self::HTTP_OK);
        }
        foreach ($services as $key => $value) {
            $driver = Driver::find($value->driver_id);
            $services[$key]->driver_name = null;
            $services[$key]->driver_phone = null;
            if(!empty($driver)){
                $services[$key]->driver_name = $driver->nombres." ".$driver->apellidos;
                $services[$key]->driver_phone = $driver->celular;
            }
        }
        $response = array('error' => false, 'msg' => 'Cargando servicios activos', 'articulos'=> $services);
        return response()->json($response, self::HTTP_OK);
    }
    public function isFirstService($user){
        $res = array('primerServicio'=>false);
        $services = Service::where([
            ["user_id", "=",$user->id]
        ])
            ->whereIn("estado", ['Terminado','Reserva'])
            ->count();

        if($services == 0) {
            $descuento = DB::select('SELECT configurations.comision from configurations where id=3')[0]->comision; //Obtener el Descuento
            $res = array('primerServicio'=>true, 'descuento'=>$descuento);
        } 
        return response()->json($res);
    }
    public function getDriverByServiceId($user, $serviceId){
        //- u.nombre, 
        //- tc.nombre_camion, 
        //- u.url_foto AS fotoConductor, 
        //- tc.foto, 
        //- u.apellidos, 
        //- u.idusuarios AS conductor_id, 
        //- v.placa, 
        //- u.celular

        
        $driverService = DB::table("driver_services as DS")
            ->where("DS.service_id", $serviceId)
            ->whereIn("DS.status", ["En curso"])
            //->whereNotIn("DS.status", ['Rechazado'])
            ->join("drivers as D", "DS.driver_id", "=", "D.id")
            ->join("services as S", "DS.service_id", "=", "S.id")
            ->first([
                "D.id as conductor_id",
                "D.nombres as nombre",
                "D.apellidos as apellidos",
                "D.telefono as celular",
                "S.tipo_transporte",
            ]);
        if(empty($driverService)){
            return response(null, self::HTTP_NO_CONTENT);
        }
            //dd($driverService);
        $driverUser = User::where([
            ["userable_id", "=", $driverService->conductor_id],
            ["userable_type", "=", Driver::class],
        ])->first();
        $driverImage = Image::where([
            ["imageable_id", "=", $driverUser->id],
            ["imageable_type", "=", User::class],
        ])->whereIn("is", ["profile"])
        ->first();
        if(!empty($driverImage)){
            $driverService->fotoConductor = $driverImage->url;
        }
        $tt = TypeTransport::where("nombre", $this->convertSToTT[$driverService->tipo_transporte])->first();
        $driverVehicle = DriverVehicle::where([
            ["driver_id", "=", $driverService->conductor_id],
            ["types_transport_id", "=", $tt->id],
        ])->first();
        $driverService->foto = $tt->foto;
        $driverService->nombre_camion = $tt->nombre;

        if(!empty($driverVehicle)){
            $driverService->placa = $driverVehicle->plate;
        }
        $response = array('error' => false, 'msg' => 'Cargando servicios activos', 'conductor'=> [$driverService]);
        return response()->json($response);
    }
    public function testValidation(Request $request){
         
        $validator = Validator::make($request->all(), [
            'images_description' => [
                'required',
                'array',
                //'image',
            ],
            //'images_description.*' => "image|mimes:jpg,bmp,png",
            'images_description.*' => "image",
        ]);

        if ($validator->fails()) {
            // ...
            return response()->json($validator->errors());
            dd($validator->errors());
            return 2;
        }
        dd($request->file("images_description"));
        return 1;

    }
    public function testNotifyDriver($driverId, $serviceId){
        $driver = User::where([
            ["userable_id", "=", $driverId],
            ["userable_type", "=", Driver::class],
        ])->first();
        $service = Service::find($serviceId);
        $tokens = FcmToken::where("user_id", $driver->id)->get();
        $responseC = [];
                foreach ($tokens as $key => $fcm) {
                    $token = $fcm->token;
                    $response = notifyToDriver($token,  "Estado del servicio", "Cliente ha aceptado tu oferta", [
                        //"url" => "ServActScreen",
                        "key" => $service->estado == "AGENDADO" ? "SCHEDULE_ACCEPTED_PRICE" : "ACCEPTED_PRICE",
                    ]);
                    $responseC[] = ["r" => $response, "t" => $token];
                }
                return response($responseC);
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
    private function cancelService($request, $userId){
		
        $servicioId = $request->servicio_id;
        $estado = "Cancelado";
        $updated = Service::where("id", $servicioId)->update([
            "estado" => $estado,
        ]);
        K_HelpersV1::getInstance()->cancelCustomerService($request->all(), $userId);
        if(!$updated){
            $response = array('error' => true, 'msg' => 'ERROR al cancelar el servicio ' );
            return response()->json( $response , self::HTTP_ACCEPTED);
        }
        $response = array('error' => false, 'msg' => 'El servicio fue cancelado' );
        //if($service->estado != "CANCELADO" && $service->estado != "ANULADO"){
        $ds = DriverService::where([
            ["service_id", "=", $servicioId],
        ])->first();
        $where = [
            ["userable_type", "=", Driver::class],
        ];
        if(!empty($ds)){
            $where[] = ["userable_id", "=", $ds->driver_id];
            //Registra el estado del servicio para el conductor
            DB::table("service_statuses")->insertGetId([
                "service_id" => $servicioId,
                "status" => 'Estado de servicio',
                "it_was_read" => 0,
                "description" => "Cliente ha cancelado el servicio activo",
                "servicestatetable_id" => $ds->driver_id,
                "servicestatetable_type" => Driver::class,
                "created_at" => date("Y-m-d H:i:s"),
            ]);
            if($ds->status == "En curso"){
                $ds->status = "Rechazado";
                $ds->save();
            }
        }
        $driver = User::where($where)->first();
        CustomerServiceCancelled::dispatch($driver, $servicioId, $userId, $estado); //Dispara evento para notificar al usuario y conductor via email y push notification
        //Reembolsa monto del servicio
        if(Transaction::where([["service_id", "=", $servicioId]])->whereIn("status", Transaction::SUCCESS_STATES)->count() > 0){
            //Realizar reembolso
            $serviceTransaction = Transaction::where([["service_id", "=", $servicioId]])->whereIn("status", Transaction::SUCCESS_STATES)->first();
            switch ($serviceTransaction->gateway) {
                case 'stripe':
                    $gatewayTransactionId = $serviceTransaction->transaction_id;
                    $gatewayTransactionId = (strpos($serviceTransaction->transaction_id, "cs_") !== false) ? 
                        StripeCustomClass::getInstance()->getPaymentIntentFromCheckoutSession($gatewayTransactionId) : $gatewayTransactionId;
                    $response = StripeCustomClass::getInstance()->refundPaymentIntent($gatewayTransactionId, $serviceTransaction->total * 100, "Servicio cancelado");
                    $userId = $serviceTransaction->user_id;
                    $data = array(
                        'usuarios_id' => $userId,
                        'servicios_id' => $servicioId,
                        //'amount' => $service->precio_real,
                        //'conductores_id' => $driverId,
                        //'interfaz' => 'DEVOLVER',
                        //'IsSuccess' => $response->status === 'succeeded' ? 1 : 0,
                        'status' => $response["status"] == "succeeded" ? "canceled" : $response["status"],
                        //'ResponseSummary' => $response->reason,
                        'transaction_id' => $response["id"],
                        'created_at' => $response["created"],
                    );
                    
                    Transaction::where("id", $serviceTransaction->id)->update($data);
                    break;
                case 'yappy':
                    # code...
                    break;
                
                default:
                    # code...
                    break;
            }
        }

        return response()->json( $response , self::HTTP_OK );
	}
    
    function uploadAFile($file, $identif, $default = null){
        $uploadfile = date('YmdHms').'_APP_'.$identif.'_photo.png';
        $location = 'public/profiles/servicios';    //Concatena ruta con nombre nuevo
        $url_imagen_foto = secure_asset("storage/profiles/servicios/$uploadfile"); //prepara ruta para obtención del archivo imagen
        $service = Service::find($identif);
        
        if(!empty($service->image) && $service->image->count() >= 4){
            return false;
        }else
        if ($path = Storage::putFileAs($location, $file, $uploadfile, 'public')) {
            # code...
            //return $url_imagen_foto;
            //chmod($path, 0644); it's not necessary 
            return $url_imagen_foto;           
        }
        return false;
        

    }
    private function uploadFile($data, $isFile = false){  
		$GLOBALS['ulrActual'] = 'http://www.api.kamgus.com/';
		$conductorId = empty($data['conductor_id']) ? " " : $data['conductor_id'];
		try {	       

			$this->db->select('idfotoservicio');
			$this->db->from('fotos_servicio');
			$this->db->where('servicios_id', $data['servicio_id']);
			$rows = $this->db->count_all_results(); 
			
	
			if($rows <= 4) {
				if($isFile){
					$uploadfile = date('YmdHms').'_SERVICIO_'.$data['servicio_id'].'_photo.png';
					$location = $_SERVER['DOCUMENT_ROOT'].'/profiles/servicios/'.$uploadfile;
					$url = $GLOBALS['ulrActual'].'profiles/servicios/'.$uploadfile;
					$saved = $this->saveNewImage($data["image_description"], $location);
					chmod($location, 0644);
				}else{

					$imagen = $data['image_description']; 
					list($type, $imagen) = explode(';', $imagen);
					list(, $imagen)      = explode(',', $imagen);
					
					if($type == 'data:image/jpeg'){
						$imagen = base64_decode($imagen);
						$uploadfile = date('YmdHms').'_SERVICIO_'.$data['servicio_id'].'_photo.png';
						$location = $_SERVER['DOCUMENT_ROOT'].'/profiles/servicios/'.$uploadfile;
						$url = $GLOBALS['ulrActual'].'profiles/servicios/'.$uploadfile;
						$saved = file_put_contents($location, $imagen); 
						if($saved === false){						
							//$this->response(([
								//	"error" => true,
								//	"msg" => "Imagen no guardada",
								//	"is_writable" => is_writable($location),
								//]), REST_Controller::HTTP_NOT_FOUND );   
								return false;
						}
						chmod($location, 0644);
					}        
				}
		
				$array = array(
					'servicios_id' => $data['servicio_id'],
					'usuarios_id' => $data['cliente_id'],
					'conductores_id' => $conductorId,
					'url_foto' => $url,
					'punto' => $data['punto'],
				);
				
				$this->db->set($array);
				$this->db->insert('fotos_servicio');
			}  
	
			//$this->response(($rows), REST_Controller::HTTP_OK);
			return true;
		   
		} catch(Exception $e) {
			//$er = array('text'=>'Error al registrar el vehiculo, error: A0016','error'=>$e->getMessage());
			//$this->response(($er), REST_Controller::HTTP_NOT_FOUND);
			return false;
		}
	}
    private function getServicesCompleted($user){
        $services = Service::where([
            ["estado", "=", "TERMINADO"],
            ["user_id", "=", $user->id],
        ])
        ->leftJoin("routes as R", "R.service_id", "=", "services.id")
        ->leftJoin("driver_services as DS", "services.id", "=", "DS.service_id")
        ->leftJoin("drivers as D", "DS.driver_id", "=", "D.id")
        ->get($this->getHistoryServicesField());
        return $services;
    }
    private function getTokensFromDrivers($drivers){
        return Driver::whereIn("drivers.id", $drivers)
            ->join("users as U", "drivers.id", "=", "U.userable_id")
            ->join("fcm_tokens as F", "U.id", "=", "F.user_id")
            ->where([
                ["U.status", "=", "Activo"],
                ["U.userable_type", "=", Driver::class],
            ])
            ->select(["F.token", "drivers.id", "U.email"])
            ->get()
            //->get()->pluck("token")
            ;
    }
    private function getDriversByServiceTransportType($ttype){
        $convert = [
            'PANEL' => "Panel",
            'PICK UP' => "Pick up",
            'CAMIÓN PEQUEÑO' => "Camión Pequeño",
            'CAMIÓN GRANDE' => "Camión Grande",
            'MOTO' => "Moto",
            'SEDAN' => "Sedan",
        ];
     
        $drivers = TypeTransport::where([
            ["nombre", "=", $convert[$ttype]],
        ])
        ->join("driver_vehicles as DV", "types_transports.id", "=", "DV.types_transport_id")
        ->select(["DV.driver_id"])
        ;
        return $drivers;
    }
    private function getDriverVehicleByServiceTransportType($ttype, $driverId){
        $convert = [
            'PANEL' => "Panel",
            'PICK UP' => "Pick up",
            'CAMIÓN PEQUEÑO' => "Camión Pequeño",
            'CAMIÓN GRANDE' => "Camión Grande",
            'MOTO' => "Moto",
            'SEDAN' => "Sedan",
        ];
        $ttId = TypeTransport::where([
            ["nombre", "=", $convert[$ttype]],
        ])->first()->id;
        return DriverVehicle::where([
            ["driver_id", "=", $driverId],
            ["types_transport_id", "=", $ttId],
        ]);
    }
    private function getServicesResponseFields()
    {
        return [
            "services.id as key",
            "services.id as idservicios",
            "services.user_id as usuarios_id",
            "services.precio_real as valor",
            //"punto_referencia",
            //"coordenas",
            "services.fecha_reserva",
            "services.tipo_servicio as tipo_translado",
            "services.estado",
            "services.created_at as creado",
            "services.kilometraje as kms",
            "services.tipo_pago",
            "services.tiempo",
            "services.tipo_transporte as id_tipo_camion",
            "services.tipo_transporte as nombre_camion",
            "R.*",
            "R.primer_punto as inicio_punto",
            "R.segundo_punto as punto_final",
            DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_segundo_punto, ",", R.longitud_segundo_punto, "\"}","]") as coordenas'),
        ];
        /**
         * [{"coord_punto_inicio":"8.9940604,-79.5054676"},{"coord_punto_final":"8.9959791,-79.506109" }]
         */
    }
    private function suggestedPriceField(){
        return [
			DB::raw("SHA2(DS.driver_id, 256) as driver_id"),
			DB::raw("concat(drivers.nombres, ' ', drivers.apellidos) as name"),
			"drivers.telefono as celular",
			"u.email",
			"u.country_id as pais_idpais",
			"p.name as country",
			
			"u.id as codigounico",
			"u.id as registro",
			//"if(second(tiempo) > 30, minute(tiempo) + 1, minute(tiempo)) as timeOfArrival",
			DB::raw("UNIX_TIMESTAMP(ADDTIME(now(),TIME_TO_SEC(TIMEDIFF(DS.endTime, DS.startTime)))) * 1000 as timeOfArrival"),
			"DS.suggested_price as price",
			"DS.service_id as idservicios",
			"DS.created_at as created_at"
		];
        //SELECT ADDTIME(now(),TIME_TO_SEC(TIMEDIFF(DS.endTime, DS.startTime))) as ti, UNIX_TIMESTAMP(now()) cu, TIME_TO_SEC(TIMEDIFF(DS.endTime, DS.startTime)) ss, DS.* FROM `driver_services` as DS where id = 79

    }
    private function getHistoryServicesField(){
        return array_merge($this->getServicesResponseFields(), [
            DB::raw("concat(D.nombres, ' ', D.apellidos) as driver_name"),
        ]);
    }
  
}
