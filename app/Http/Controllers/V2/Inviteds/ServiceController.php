<?php

namespace App\Http\Controllers\V2\Inviteds;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Events\CustomerServiceCancelled;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\Drivers\ServicesController;
use App\Jobs\NotifyServiceStatus;
use App\Jobs\NotifyServiceStatusByEmail;
use App\Mail\StatusService;
use App\Models\ArticleService;
use App\Models\Configuration;
use App\Models\Country;
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
use App\Notifications\K_SendPushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Mail;

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

    private $paymentType = [
        "Efectivo" => "Efectivo",
        "Card" => "Card",
        "Tarjeta Crédito" => "Card",
        "Credito" => "Card",
        "Yappy" => "Card",
        
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //crearServicioInvitado
    {
        DB::beginTransaction();
        //
        $kms = $request->kms / 1000;		
		$duration = $request->tiempo / 60;
		$valor = round($request->valor);
		$description = empty($request->description) ? null : $request->description;
		$assistant = empty($request->assistant) ? 0 : $request->assistant;
        $email = $request->correo;
		$traslado = $request->tipo_translado;
		$telefono = $request->telefono;
        if(strlen(trim($telefono)) == 8){
            $telefono = "+".Country::where("nicename", "Panama")->first()->phonecode."".$telefono;//+507xxxxxxxx
        }
		if($traslado == "articulo"){
			$traslado = 'Simple';
			$assistant = 1;
		}else if ($traslado == "vehiculo"){
			$traslado = 'Mudanza';		
		}
		if($request->tipo_translado != "articulo" && empty($request->description)){
			$response = array('error' => true, 'msg' => 'La descripción es obligatoria' );
			return response()->json( $response , self::HTTP_BAD_REQUEST );
		}
        if( $request->fecha_reserva == 'now'){
        	$fechNueva = date('Y-m-d H:i:s');
        }else{
        	$date = new \DateTime( $request->fecha_reserva );
			$fechNueva =  $date->format('Y-m-d H:i:s');
        }    
        $id_user = null;
        $rules = [
            
            //'telefono' => 'unique:customers',
            'valor' => 'required|numeric|max:255',
            'punto_inicial' => 'required|max:255',
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
            //'customerId' => 'required|max:255',
            'articulos' => 'nullable|max:255',
            'ExpirationDate' => 'nullable|max:255',
            'precio_sugerido' => 'required|max:255',
            'description' => 'required_if:tipo_translado,Mudanza|max:255',
            'assistant' => 'nullable|max:255',
            'image_description.*' => 'nullable|image|max:50000',
            
            'articulos' => 'required_if:tipo_translado,Simple|max:255',

        ];
        
        
        //dd(json_encode($nArticulos));
        $this->validate($request, $rules);
        if(User::where("email", $email)->count() > 0){
            $user = User::where("email", $email)->first();
            $user->userable->telefono = $telefono;
            $user->userable->save();
           //return $user;
            $id_user = $user->id;
            if($user->userable_type !== Customer::class && $user->userable_type !== Driver::class){
                $response = array('error' => true, 'msg' => 'El usuario ya esta registrado, pero no como un cliente sino como: '.$user->userable_type );
                return response()->json( $response , self::HTTP_BAD_REQUEST );
            }
            if(K_HelpersV1::getInstance()->isNotRegisteredCustomer($id_user)){
                $oldUserId = K_HelpersV1::getInstance()->registerCustomer(array_merge($request->all(), ["password" => 'K12345678', "url_foto" => null, "url_licencia" => null, "email" => $request->correo]), $id_user);
            }
        }else{
            
            $oldUserId = K_HelpersV1::getInstance()->registerCustomer(array_merge($request->all(), ["password" => 'K12345678', "url_foto" => null, "url_licencia" => null, "email" => $request->correo]));
            
            $customer = new Customer();
            $customer->nombres = $request->nombres;
            $customer->apellidos = $request->apellidos;
            $customer->telefono = $telefono;
            $customer->save();
    
            $user = new User();
            if(K_HelpersV1::ENABLE && $oldUserId){
                $user->id = $oldUserId;
            }
            $user->email = $email;
            $user->password = Hash::make("K12345678");
            $user->userable_id = $customer->id;
            $user->userable_type = Customer::class;
            $user->country_id = Country::where("iso", "PA")->first()->id;
            $user->save();
    
            // Asignar un rol
            $user->assignRole('Cliente');
            $id_user = $user->id;
            
            //$customer-> = $request->correo;
        }
        $user = User::find($id_user);
        if(!empty($request->customer_id) && $request->customer_id !== 'null'){
            
            $user->stripe_id = $request->customer_id;
            $user->save();
        }
        
        //Registrar nuevo servicio
        //$customer = Customer::where("id", $user->userable_id)->first();
        $customer = $user->userable();
       
        
        //usuarios_id:2267
        //articulos:[{"idarticulo":"96","cantidad":1,"nombre":"Lámpara de Techo","m3":"","m2":0,"peso":"0.1","tiempo":0,"tc":"5"}]
        //guardar el servicio
        $serviceO = K_HelpersV1::getInstance()->createServiceInvited((array)$request->all(), $user->id);
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
        $service->tipo_servicio = $traslado;
        $service->precio_real = $request->valor;
        $service->estado = $request->estado;
        if($request->estado == "AGENDADO"){
            $service->estado = "RESERVA";
        }else if($request->estado == "ACTIVO"){
            $service->estado = "PENDIENTE";
        }
        $service->precio_sugerido = $request->precio_sugerido;
        $service->tipo_pago = $this->paymentType[ucfirst($request->tipo_pago)];
        $service->descripcion = $request->description;
        $service->assistant = $assistant;
        //$service->customer_id = $user->userable->id; //Eliminar de la base de datos porque no solo los customer crean servicios.
        $service->user_id     = $user->id;
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
        $route->primer_punto = $request->punto_inicial;
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
        $userEmails = Driver::whereIn("drivers.id", $drivers)
        ->join("users as U", "drivers.id", "=", "U.userable_id")
        ->where([
            ["U.status", "=", "Activo"],
            ["U.userable_type", "=", Driver::class],
        ])
        ->get(["U.email"]);
        if(count((array)$userEmails)){
            //NotifyServiceStatusByEmail::dispatch($userEmails->pluck("email"), $service->id, "Nuevo servicio", "Hay un nuevo servicio disponible");
            NotifyServiceStatusByEmail::dispatchAfterResponse($userEmails->pluck("email"), $service->id, "Nuevo servicio", "Hay un nuevo servicio disponible");
        }
        $usersTokens = $this->getTokensFromDrivers($drivers);
        //foreach ($drivers as $key => $driver) {
        //    $driver->notify(new SendExpoPushNotification("Nuevo servicio", "Hay un nuevo servicio disponible"));
        //}
        //Register service status in drivers
        foreach ($drivers as $driverId) {
            $driver = User::where([
                ["userable_id", "=", $driverId],
                ["userable_type", "=", Driver::class],
            ])->first();
            if(!empty($driver)){
                K_HelpersV1::getInstance()->setCustomerNotification($driver->id, $service->id, "Nuevo servicio V2", "Hay un nuevo servicio disponible");
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
        }

        //foreach ($usersTokens as $tdriver) {
            //notifyToDriver($tdriver->token, "Nuevo servicio", "Hay un nuevo servicio disponible");            
        //}
        NotifyServiceStatus::dispatchAfterResponse($usersTokens->pluck("token"), $service->id, "Nuevo servicio", "Hay un nuevo servicio disponible");
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
    public function show($id) //serviceStatusInvitado, suggestedServicePricesInvitado
    {
        //
        switch ($id) {
            case 'status':
                $rules = [
                    'serviceId' => 'exists:services,id',
                ];
                $validator = Validator::make(request()->all(), $rules);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), self::HTTP_UNPROCESSABLE_ENTITY); //{"repeat_password":["Los campos repeat password y password deben coincidir."]}
                }
            
                $serviceId = request()->serviceId;
                $services = DB::table("services")
                ->leftJoin("routes as R", "R.service_id", "=", "services.id")
                //->groupBy("S.id")
                ->where("services.id", $serviceId)
                ->orderBy("services.id", "DESC");
                $servicesData = $services->get($this->getServicesResponseFields());
                if(strtolower($servicesData[0]->tipo_pago) == "card" || strtolower($servicesData[0]->tipo_pago) == "yappy"){
                    $servicesData[0]->response = Transaction::where("service_id", $serviceId)->first()->status;
                }
                $response = array('error' => false, 'msg' => "", "data" => $servicesData[0]);

                return response()->json($response, self::HTTP_OK);
                break;
            case 'suggested_price':
                
                $serviceId = request()->service_id;
                //$driver_id = request()->driverId;
                //$query = $this->db->get_where('mytable', array('id' => $id), $limit, $offset);
                $subQuery = [];
            
                $query = DriverService::where([
                    ['service_id', "=", $serviceId],
                    //['status', "=", "Pendiente"],
                ])->whereIn("status", ["Pendiente", "Reserva"]);
                if(!empty(request()->driver_id)){
                    $driverId = Driver::whereRaw("SHA2(id, 256) = ?", [request()->driver_id])->first()->id;
                    $query = $query->where("driver_id", $driverId);
                }
                $dServices = $query->get();
                if( count($dServices) > 0 ){
                    foreach ($dServices as $key => $value) {
                        $subQuery[] = $value["driver_id"];
                    }
                }
            
                
                $query = DriverService::where([
                    ['driver_services.service_id', "=", $serviceId],
                    ['u.userable_type', "=", Driver::class],
                ])
               
                ->join('drivers', 'drivers.id', "=", 'driver_services.driver_id')
                ->join('users as u', 'u.userable_id', "=", 'drivers.id')
                ->leftJoin('countries as p', 'u.country_id', "=", 'p.id')
                ->join('driver_vehicles as DV', 'DV.driver_id', "=", 'drivers.id')
                ->leftJoin("models as Mo", "Mo.id", "=", "DV.model_id")
                ->whereIn("driver_services.status", ["Pendiente"])
                ->whereIn("DV.driver_id", $subQuery)
                ->groupBy("service_id");
                $services = DB::table("services")->where([
                //  ["user_id", "=", $user->id],
                    ['u.userable_type', "=", Driver::class],
                    ['service_id', "=", $serviceId],
                ])
                //->whereIn("services.estado", ["Pendiente", "Reserva"])
                ->whereIn("DS.status", ["Pendiente"])
                ->whereIn("DS.driver_id", $subQuery)
                ->join("driver_services as DS", "DS.service_id", "=", "services.id")
                ->leftJoin('drivers', 'drivers.id', "=", 'DS.driver_id')
                ->leftJoin('users as u', 'u.userable_id', "=", 'drivers.id')
                ->leftJoin('countries as p', 'u.country_id', "=", 'p.id')    
                
                ->get($this->suggestedPriceField());

                foreach ($services as $key => $value) {
                    //$services[$key]->fotoConductor = Driver::find($value->driver_id)->url_foto_perfil;
                    
                    $driverVehicleInfo = DriverVehicle::where([
                        ["driver_vehicles.driver_id", "=", $value->driver_id],
                    ])->leftJoin("models as Mo", "Mo.id", "=", "driver_vehicles.model_id")->first();
                    if(!empty($driverVehicleInfo)){
                        $services[$key]->vehicle = $driverVehicleInfo->name;
                        $services[$key]->rating = 0;
                        $services[$key]->plate = $driverVehicleInfo->plate;
                    }
                }
                //dd($services);
                $services = $this->getADefaultSuggestedPrice($serviceId, $services);
                if( count( $services) > 0 ){
                    $response = array('error' => false, 'msg' => 'Cargando servicios activos', 'conductores'=> $services);
                        return response()->json( $response , self::HTTP_OK );
                }else{
                    $response = array('error' => true, 'msg' => 'Error cargando conductores activos');
                    return response()->json( $response , self::HTTP_NO_CONTENT );	
                }
                break;
            case 'payment_status': //Testing
                return;
                return StripeCustomClass::getInstance()->getCheckoutSession(Transaction::where("service_id", request()->serviceId)->first()->transaction_id);
            case 'test_refund': //Testing
                //return;
                $t = Transaction::where("transaction_id", request()->tid)->first();
                $pIntent =  StripeCustomClass::getInstance()->getCheckoutSession($t->transaction_id)["payment_intent"];
                $response =  StripeCustomClass::getInstance()->refundPaymentIntent($pIntent, $t->total * 100);
                $t->status = $response["status"] == "succeeded" ? "canceled" : $response["status"];
                $t->transaction_id = $response["id"];
                $t->save();
                return $response;
                
                # code...
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
    public function update(Request $request, $id) //postPrecioSugeridosInvitado, cancelServiceInvitado
    {
        //
        switch ($id) {
            case 'confirm_driver':
                $rules = [
                    'servicio_id' => 'exists:services,id'
                ];
                $this->validate($request, $rules);
                $servicioId = $request->servicio_id;
                $driverId = $request->driver_id;
                //$usuarioId = $request->usuario_id;
                $driver = Driver::whereRaw("SHA2(id, 256) = ?", [$driverId])->first();
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
                $service->driver_id = $driver->id;
                
                $driverId = $driver->id;
                $driver = User::where([
                    ["userable_id", "=", $driverId],
                    ["userable_type", "=", Driver::class],
                ])->first();
                K_HelpersV1::getInstance()->confirmDriver((array) $request->all(), $driver->id);
                
                $service->save();
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
                $driverService->status = $service->estado == "AGENDADO" ? $service->estado : 'En Curso';
                $driverService->confirmed = 'SI';
                $driverService->ispaid = 'Pendiente';
                $driverService->commission = '';
                $driverService->save();
                    //'vehiculos_id' => $driverVehicle->first()->id,
                    //'role' => 'CONDUCTOR',
                    //'fecha_reserva' => $serviceInfo->fecha_reserva,
                    //'creado' => date("Y-m-d H:i:s"),
                $tokens = FcmToken::where("user_id", $driver->id)->get();
                //$sends = [];
                foreach ($tokens as $key => $token) {
                    $sended = notifyToDriver($token->token,  "Estado del servicio", "Cliente ha aceptado tu oferta", [
                        //"url" => "ServActScreen",
                        "key" => $service->estado == "AGENDADO" ? "SCHEDULE_ACCEPTED_PRICE" : "ACCEPTED_PRICE",
                    ]);
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
                    //$sends[] = $sended;
                }
                //dd($sends);
                $response = array('error' => false, 'msg' => 'La respuesta a precio sugerido fue enviada', 'data'=> []);
                return response()->json($response, self::HTTP_OK);
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
                return $this->cancelService($request, $service->user_id);
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
    private function getADefaultSuggestedPrice($serviceId, $drivers = []){
        if((Configuration::where("descripcion", "def_suggested_price")->where("comision", ">", 0)->count() > 0) && (User::role("Administrador")->count() > 0)){
            $service = Service::find($serviceId);
            $admin = User::role("Administrador")->first();
            $default = [
                //"driver_id" => hash("sha256", $admin->id),
                "driver_id" => Service::SERVICE_ADMIN_OFFER,
                "name" => "Kamgus",
                "celular" => "",
                "email" => $admin->email,
                "pais_idpais" => "",
                "country" => $admin->country ? $admin->country->name : null,
                "fotoConductor" => null,
                "codigounico" => $admin->id,
                "registro" => "Administrador",
                "timeOfArrival" => strtotime("+1 minutes") * 1000,
                "price" => $service->precio_sugerido > $service->precio_real ? $service->precio_sugerido : $service->precio_real,
                "idservicios" => $serviceId,
                "created_at" => $service->created_at->format("Y-m-d H:i:s"),
            ];
            $drivers[] = (object)$default;
        }
        return $drivers;
    }
    private function addDefaultSuggestedPrice($serviceId, $price){
        if((Configuration::where("descripcion", ">=", 1)->count() > 0) && (User::role("Administrador")->count() > 0)){
            return $this->registerDriverSuggestedPrice(User::role("Administrador")->first(), new Request([
                "servicio_id" => $serviceId,
                "user_lat" => null,
                "user_lng" => null,
                "precio" => $price,
            ]));
        }
    }
    /**
     * Registra a un administrador con una oferta al servicios del usuario.
     * PRECAUCION: la funcion no esta completa debido a que el administrador debe tener un registro en la tabla de drivers.
     */
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
            $fcmData = ServicesController::getDataOfNewService(
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
                $customerUser->notify(new K_SendPushNotification('Estado de servicio', "Un conductor tiene una oferta para el servicio.", $fcmToken, $fcmData));
            }
            Mail::to($customerUser)->send(new StatusService('Estado de servicio', "Un conductor tiene una oferta para el servicio.", $estado));
            return response()->json($response);
        }
    }
    private function uploadAFile($file, $identif, $default = null){
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
    private function getTokensFromDrivers($drivers){
        return Driver::whereIn("drivers.id", $drivers)
            ->join("users as U", "drivers.id", "=", "U.userable_id")
            ->join("fcm_tokens as F", "U.id", "=", "F.user_id")
            ->where([
                ["U.status", "=", "Activo"],
                ["U.userable_type", "=", Driver::class],
            ])
            ->select(["F.token"])
            ->get(["token", "drivers.id"])
            //->get()->pluck("token")
            ;
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
            "R.primer_punto as punto_inicial",
            "R.segundo_punto as punto_final",
            DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"}","]") as coordenas'),
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
			"drivers.url_foto_perfil as url_foto_perfil",
			"u.email",
			"u.country_id as pais_idpais",
			"p.name as country",
			"drivers.url_foto_perfil as fotoConductor",
			"u.id as codigounico",
			//"SHA2(u.id, 256) as registro",
			//"if(second(tiempo) > 30, minute(tiempo) + 1, minute(tiempo)) as timeOfArrival",
			DB::raw("UNIX_TIMESTAMP(TIMEDIFF(DS.startTime, DS.endTime)) * 1000 as timeOfArrival"),
			"DS.suggested_price as price",
			"DS.service_id as idservicios",
			"DS.created_at as created_at"
		];
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
        //}
        return response()->json( $response , self::HTTP_OK );
	}

}
