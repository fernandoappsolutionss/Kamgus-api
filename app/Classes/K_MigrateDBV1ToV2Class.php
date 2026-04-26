<?php
namespace App\Classes;

use App\Http\Controllers\Controller;
use App\Models\ArticleService;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Driver;
use App\Models\DriverAccount;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\Image;
use App\Models\Mark;
use App\Models\Model;
use App\Models\Qualification;
use App\Models\Route;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBonuses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class K_MigrateDBV1ToV2Class
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
    const ARTICLES_IDS = [
         1 => 80,
         2 => 81,
         3 => 82,
         4 => 83,
         8 => 84,
         14 => 85,
         12 => 86,
         13 => 87,
         15 => 88,
         17 => 89,
         18 => 90,
         19 => 91,
         21 => 92,
         22 => 93,
         24 => 94,
         25 => 95,
         26 => 96,
         27 => 97,
         28 => 98,
         29 => 99,
         30 => 100,
         31 => 101,
         33 => 102,
         34 => 103,
         35 => 104,
         36 => 105,
         37 => 106,
         38 => 107,
         39 => 108,
         40 => 109,
         41 => 110,
         42 => 111,
         43 => 112,
         44 => 113,
         45 => 114,
         46 => 115,
         47 => 116,
         48 => 117,
         49 => 118,
         50 => 119,
         52 => 120,
         53 => 121,
         54 => 122,
         55 => 123,
         56 => 124,
         57 => 125,
         58 => 126,
         59 => 127,
         60 => 128,
         61 => 129,
         63 => 130,
         96 => 131,
         65 => 132,
         66 => 133,
         67 => 134,
         68 => 135,
         69 => 136,
         70 => 137,
         71 => 138,
         74 => 139,
         75 => 140,
         76 => 141,
         79 => 142,
         80 => 143,
         81 => 144,
         82 => 145,
         83 => 146,
         85 => 147,
         86 => 148,
         87 => 149,
         88 => 150,
         89 => 151,
         90 => 152,
         91 => 153,
         92 => 154,
         93 => 155,
         94 => 156,
         95 => 157,
         97 => 158,
    ];
    public static function getInstance(){
        return new K_MigrateDBV1ToV2Class();
    }
    private function hasServicesReserved($userId){
        //$query = "SELECT conductor_servicios.* from conductor_servicios inner join servicios on conductor_servicios.servicios_id = servicios.idservicios where conductores_id = 2556 and servicios.estado in ('Agendado', 'Reserva') $userId"
        $datetime = new DateTime('now');
        $datetime->modify('+2 hours');
  
        $driverService = DriverService::where([
            ["driver_services.driver_id", "=", $userId],
            ["driver_services.status", "=", "Agendado"],
            ["S.fecha_reserva", "<=", $datetime->format('Y-m-d H:i:s')],
        ])
            ->join("services as S", "driver_services.service_id", "=", "S.id")
            ->count();
            return ($driverService > 0);
            if($driverService > 0){
                $response = array(
                    'error' => true, 
                    'msg' => 'Tiene un servicio agendado pendiente por iniciar', 
                    //"time" => $datetime->format('Y-m-d H:i:s'), 
                    //"current" => (new DateTime('now'))->format('Y-m-d H:i:s'),
                );
                return true;
                return response()->json( $response , Controller::HTTP_BAD_REQUEST );	
            }
        return false;

    }
    private function migrateResourceImages(){
        //Consultar imagenes de la tabla
        //Evaluar si el archivo existe si existe ? mover el archivo al directorio de api v2 : no hace nada
        //Renombre la url con el host de la api v2
    }
    /*
        Migra registros de servicios de la anterior base de datos a la nueva.
    */    
    public function migrateServices(){
        
        DB::beginTransaction();
        try{
            if(!hasDbConnection("mysql_old")){
                return 1;
            }
            $services = DB::connection("mysql_old")
                ->table("servicios")
                ->where([
                    ["id_tipo_camion", "!=", 0],    
                    //["idservicios", "=", 7449 ],    
                    //["idservicios", ">", 4000 ],    //pendientes: 7365 y 7366
                    //["idservicios", "<=", 6000 ],    
                ])
                
                ->whereIn("idservicios", [
                    
                    '3904'])
                
                ->whereNotIn("estado", ["Pendiente"])
     
                ->whereNotIn("usuarios_id", [2074]);
            foreach ($services->cursor() as $key => $service) {
                $auxService = Service::find($service->idservicios);
                //verificar si el servicio ya esta registrado
                //sino crear un servicio nuevo con los  datos de la vieja base de datos
                /*
                if($service->idservicios == 7445){

                    Image::where([
                    ["imageable_id", "=", $service->idservicios],
                    ["imageable_type", "=", Service::class],
                    ])->delete();
                    Transaction::where([
                        "service_id" => $service->idservicios,
                        ])->delete();
                        ArticleService::where("service_id", $service->idservicios)->delete();
                        DriverService::where("service_id", $service->idservicios)->delete();
                        Route::where("service_id", $service->idservicios)->delete();
                        Service::where("id", $service->idservicios)->delete();
                }
                */
                
                if(empty($auxService)){
                    $ttype = is_numeric($service->id_tipo_camion) 
                        ? $this->transportType[$service->id_tipo_camion] 
                        : $service->id_tipo_camion;
                    $customerPrice = DB::connection("mysql_old")
                    ->table("precio_sugerido")->where([
                        ["servicio_id", "=", $service->idservicios],
                        ["by_driver", "=", 0],
                        
                    ])
                    ->whereNotIn("usuario_id", [2074])
                    ->first();
                    $driverSelected = DB::connection("mysql_old")
                    ->table("conductor_servicios")->where([
                        ["servicios_id", "=", $service->idservicios],
                        ["estado", "=", "terminado"],
                    ])->first();
                    if(empty($customerPrice->usuario_id)){
                        //DB::rollBack();
                        //echo "Vacio";
                        //continue;
                        //return $customerPrice;
                    }
                    echo "No existe: ".$service->idservicios." - ".json_encode($customerPrice)."<br>";

                    $serviceObjects = DB::connection("mysql_old")
                        ->table("servicios_objetos")->where([
                            ["servicios_id", "=", $service->idservicios],
                        ])->first();
                    $serviceImages = DB::connection("mysql_old")
                        ->table("fotos_servicio")->where([
                            ["servicios_id", "=", $service->idservicios],
                        ])->get();
                    $userS = User::find(empty($customerPrice) ? $service->usuarios_id : $customerPrice->usuario_id);
                    //echo "s: ". json_encode($service->tipo_pago);
                    $serviceParameters = [
                        "id" => $service->idservicios,
                        "tiempo" => $service->tiempo,
                        "kilometraje" => $service->kms,
                        "fecha_reserva" => $service->fecha_reserva == "0000-00-00 00:00:00" ? $service->creado: $service->fecha_reserva,
                        "tipo_transporte" => $ttype,
                        "estado" => $service->estado,
                        "tipo_servicio" => $service->tipo_translado,
                        "precio_real" => floatval($service->valor),
                        "precio_sugerido" => floatval(empty($customerPrice) ? $service->valor : $customerPrice->precio),
                        "tipo_pago" => $this->paymentType[$service->tipo_pago],
                        "descripcion" => $service->description,
                        "pago" => $service->ispaid,
                        "assistant" => empty($service->assistant) ? 0 : $service->assistant,
                        "customer_id" => empty($userS) || $userS->userable_type == "App\\Models\\Company" || $userS->userable_type == "App\\Models\\Driver" ? null : $userS->userable_id,
                        "user_id" => $this->getUser(empty($customerPrice) ? $service->usuarios_id : $customerPrice->usuario_id),
                        "driver_id" => empty($driverSelected ) ? null: User::find($driverSelected->conductores_id)->userable_id,
                        "created_at" => $service->creado,
                    ];
                    DB::table("services")->insertGetId($serviceParameters);
                    //guardar la ruta del servicio
                    $latitudPrimerPunto = null;
                    $longitudPrimerPunto = null;
                    $latitudSegundoPunto = null;
                    $longitudSegundoPunto = null;
                    $coordenas = json_decode(stripcslashes(trim($service->coordenas,'"')));
                    if(is_array($coordenas)){
                        $pointsA = explode(",", $coordenas[0]->coord_punto_inicio);
                        $pointsB = explode(",", $coordenas[1]->coord_punto_final);
                        $latitudPrimerPunto = $pointsA[0];
                        $longitudPrimerPunto = $pointsA[1];
                        $latitudSegundoPunto = $pointsB[0];
                        $longitudSegundoPunto = $pointsB[1];
                    }else{
                        //$latitudPrimerPunto = -1;
                        //$longitudPrimerPunto = -1;
                        //$latitudSegundoPunto = -1;
                        //$longitudSegundoPunto = -1;            
                        if (!empty($coordenas->coord_punto_inicio)) {
                            # code...
                            $pointsA = explode(",", $coordenas->coord_punto_inicio);
                            $pointsB = explode(",", $coordenas->coord_punto_final);
                            $latitudPrimerPunto = $pointsA[0];
                            $longitudPrimerPunto = $pointsA[1];
                            $latitudSegundoPunto = $pointsB[0];
                            $longitudSegundoPunto = $pointsB[1];            
                        }
                    }
                    if (!empty($latitudPrimerPunto)) {
                        # code...
                        $route = new Route();
                        $route->primer_punto = $service->inicio_punto;
                        $route->latitud_primer_punto = $latitudPrimerPunto;
                        $route->longitud_primer_punto = $longitudPrimerPunto;
                        $route->segundo_punto = $service->punto_final;
                        $route->latitud_segundo_punto = $latitudSegundoPunto;
                        $route->longitud_segundo_punto = $longitudSegundoPunto;
                        $route->service_id = $service->idservicios;
                        $route->save();
                    }
                    if(!empty($serviceObjects->lista_objetos) && $service->tipo_translado == "Simple"){
                        //if($articles = json_decode('[{"idarticulo":" \r\n 2 ","cantidad":"1","nombre":"Colch\u00f3n Double \/ full o tama\u00f1o doble \/ matrimonio","m3":"3.5","m2":"1","peso":"80","tiempo":"10"}]')){
                        if($articles = json_decode($serviceObjects->lista_objetos) ){
                            $articles = !empty($articles) ? (array)$articles : [];
                            foreach ($articles as $key => $article) {
                                if(!(empty($article->idarticulo) && empty($article->id))){
                                    
                                    
                                    $articleId = trim( empty($article->id) ? $article->idarticulo : $article->id );
                                    if(
                                        $articleId == 5 
                                        || $articleId == 6 
                                        || $articleId == 7 
                                        || $articleId == 9 
                                        || $articleId == 10 
                                        || $articleId == 11 
                                        || $articleId == 16 
                                        || $articleId == 20
                                        || $articleId == 23 
                                        || $articleId == 32 
                                        || $articleId == 51 
                                        || $articleId == 62 
                                        || $articleId == 64 
                                        || $articleId > 97){
                                        
                                    }else
                                    if(!empty($article) && !is_numeric($article) && !empty($articleId)){
                                        
                                        ArticleService::insertGetId([
                                            "article_id" => self::ARTICLES_IDS[$articleId],
                                            "cantidad" => empty($article->cantidad) ? 0 : $article->cantidad,
                                            "service_id" => $service->idservicios,
                                            "created_at" => empty($article->creado) ? $serviceObjects->creado : $article->creado,
                                        ]);
                                    }else if (!empty($article) &&  is_numeric($article)) {
                                        ArticleService::insertGetId([
                                            "article_id" => self::ARTICLES_IDS[trim($article)],
                                            "cantidad" => 1,
                                            "service_id" => $service->idservicios,
                                            "created_at" => $serviceObjects->creado ,
                                        ]);
                                    }
                                };
                            }
                        };
                    }
                    foreach ($serviceImages as $key => $serviceImage) {
                        //APP_POINT_ -> driver
                        //SERVICIO_7422, _initial_ -> customer
                        $image = new Image();
                        $image->url = $serviceImage->url_foto;
                        $image->is = "service_point_".$serviceImage->punto; 
                        $image->imageable_id = $service->idservicios;
                        $image->imageable_type = Service::class;
                        $image->created_at = $serviceImage->creado;
                        $con[] = $image->save();    
                    }
                   
                    //insert payment info in transactions table
                    if($service->tipo_pago != "Efectivo" && !empty($service->tokenTarjeta)){
                        $tstatu = $service->tipo_pago == "Yappy" && $service->estado == "Cancelado" && (empty($service->Response) || $service->Response == "-") ? "canceled" :$service->Response;
                        if($tstatu == "fail"){
                            $tstatu = "failed";
                        }else
                        if($tstatu == "success"){
                            $tstatu = "succeeded";
                        
                        }else
                        if(strpos($tstatu, "{") !== false){
                            $tstatu = json_decode($tstatu)->IsSuccess ? "succeeded" : "failed";
                        }
                        $customerId = empty($customerPrice) ? $service->usuarios_id : $customerPrice->usuario_id;
                        $customerId = $this->getUser($customerId);
                        $tipoPago = null;
                        if (strlen($service->tokenTarjeta) > 0) {
                            if (strpos(substr($service->tokenTarjeta, 0, 4), "_") !== false) {
                                $tipoPago = "stripe";
                            }else if(strtotime($service->creado) < strtotime("2023-03-22")){
                                $tipoPago = $service->tipo_pago;
                                if($service->tipo_pago == "Tarjeta Crédito"){
                                    $tipoPago = "metro";
                                }
                            }else{
                                $tipoPago = "yappy";                    
                            }
                        }
                        Transaction::firstOrCreate([
                            "user_id" => $customerId,
                            "service_id" => $service->idservicios,
                        ], [
                            "user_id" => $customerId,
                            "service_id" => $service->idservicios,
                            "type" => "visa",
                            "amount" => floatval($service->valor),
                            "tax" => 0,
                            "currency" => "USD",
                            "transaction_id" => $service->tokenTarjeta,
                            "status" => (empty($tstatu) || $tstatu == "null") ? 'failed' : $tstatu,
                            "gateway" => $tipoPago,
                            "receipt_url" => "",
                            "created_at" => $service->creado,
                        ]);
                    }
                    if(!empty($driverSelected)){
                        $auxDId = User::find($driverSelected->conductores_id)->userable_id;
                        $driverPrices = DB::connection("mysql_old")
                                ->table("precio_sugerido")->where([
                                    ["servicio_id", "=", $service->idservicios],
                                    //["usuario_id", "=", $cs->conductores_id],
                                    ["by_driver", "=", 1],
                                ])->select(["precio_sugerido.*", DB::raw("ADDTIME(precio_sugerido.created_at, precio_sugerido.tiempo) as endTime")])->get();
                        foreach ($driverPrices as $key => $dprice) {
                            $ds = DriverService::firstOrCreate(
                                ['service_id' => $service->idservicios, 'driver_id' => $dprice->usuario_id],
                                [
                                    "driver_id" => $auxDId,
                                    "endTime" => $dprice->endTime,
                                    "startTime" => $dprice->created_at,
                                    "status" => "Rechazado",
                                    "confirmed" => "NO",
                                    "reservation_date" => $dprice->created_at,
                                    "observation" => "",
                                    "suggested_price" => $dprice->precio,
                                    "ispaid" => "pendiente",
                                    "commission" => "",
                                ]
                            );
                            $ds->save();
                        }
                        $driversSelected = DB::connection("mysql_old")
                            ->table("conductor_servicios")->where([
                                ["servicios_id", "=", $service->idservicios],
                            ])->get();
                        foreach ($driversSelected as $key => $cs) {
                            $driverPrice = DB::connection("mysql_old")
                                ->table("precio_sugerido")->where([
                                    ["servicio_id", "=", $service->idservicios],
                                    ["usuario_id", "=", $cs->conductores_id],
                                    ["by_driver", "=", 1],
                                ])->first();
                            DB::table("driver_services")->where([
                                ["service_id", "=", $service->idservicios],
                                ["driver_id", "=", $auxDId],
                            ])->update([
                                "service_id" => $service->idservicios,
                                "startTime" => $cs->startTime,
                                "endTime" => $cs->endTime,
                                "driver_id" => $auxDId,
                                "status" => $cs->estado,
                                "confirmed" => $cs->confirmado,
                                "reservation_date" => $cs->fecha_reserva,
                                "observation" => $cs->observacion,
                                "suggested_price" => empty($driverPrice) ? $service->valor : $driverPrice->precio,
                                "ispaid" => empty($cs->ispagado) ? "pendiente" : $cs->ispagado,
                                "commission" => $cs->comision,
                            ]);
                        }
                        

                    }
                    echo "Echo";
                }else{
                    echo "Ya existe: ".$service->idservicios."<br>";
                    
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return json_encode([
                $e->getMessage(),
                $e->getTraceAsString(),
            ]);
        }

    }
    public function migrateServicesTT(){
        $services = Service::where("tipo_transporte", "like", "%CAMIÓNN PEQUEÑO%")->update([
            "tipo_transporte" => "CAMIÓN PEQUEÑO",
        ]);
        
    }
    public function migrateServicesTT2(){
        
        DB::beginTransaction();
        try{
            if(!hasDbConnection("mysql_old")){
                return 1;
            }
            $services = DB::connection("mysql_old")
                ->table("servicios")
                ->where([
                    ["id_tipo_camion", "!=", 0],    
                    //["idservicios", "=", 7449 ],    
                    //["idservicios", ">", 4000 ],    //pendientes: 7365 y 7366
                    //["idservicios", "<=", 6000 ],    
                ])
         
                
               // ->whereNotIn("estado", ["Pendiente"])
     
                //->whereNotIn("usuarios_id", [2074])
                ;
            foreach ($services->cursor() as $key => $service) {
                $auxService = Service::find($service->idservicios);
                //verificar si el servicio ya esta registrado
                //sino crear un servicio nuevo con los  datos de la vieja base de datos
                /*
                if($service->idservicios == 7445){

                    Image::where([
                    ["imageable_id", "=", $service->idservicios],
                    ["imageable_type", "=", Service::class],
                    ])->delete();
                    Transaction::where([
                        "service_id" => $service->idservicios,
                        ])->delete();
                        ArticleService::where("service_id", $service->idservicios)->delete();
                        DriverService::where("service_id", $service->idservicios)->delete();
                        Route::where("service_id", $service->idservicios)->delete();
                        Service::where("id", $service->idservicios)->delete();
                }
                */
                
                    $ttype = is_numeric($service->id_tipo_camion) 
                        ? $this->transportType[$service->id_tipo_camion] 
                        : $service->id_tipo_camion;
                    
                    $serviceParameters = [
                        "tipo_transporte" => $ttype,
                    ];
                    DB::table("services")->where("id", $service->idservicios)->update($serviceParameters);
                    //guardar la ruta del servicio
                    
                    echo "Echo<br/>";
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return json_encode([
                $e->getMessage(),
                $e->getTraceAsString(),
            ]);
        }

    }
    public function migrateBalanceTransactions(){
        //Consultar y agregar registros de tabla balances_conductores
        DB::beginTransaction();
        try {
            //code...
            if(!hasDbConnection("mysql_old")){
                return;
            }
            Transaction::whereNull("service_id")->delete();
            $balances = DB::connection("mysql_old")
            ->table("balances_conductores")
            ->where([
                //["response", "=", "succeeded"],
                ["idusuarios", "!=", "2494"],
            ])
            ->cursor();
            foreach ($balances as $key => $balance) {
                $resposeStatus = $balance->response;
                $resposeStatus = $balance->response == "fail" ? "failed" : $balance->response;
                $accumulated = null;
                $transactionId = $balance->transaction_id;

                $gateway = strtolower($balance->gateway_type);
                $gateway = strlen($gateway) <= 0 ? null : $gateway;
                $gateway = $gateway == "pagocash" ? "pago_cash" : $gateway;
                if($gateway == "pago_cash" && strpos(strtolower($resposeStatus), "pcpend") !== false){
                    $accumulated = explode("_", $resposeStatus)[1];
                    $resposeStatus = "pending";
                }
                if($gateway == "pago_cash" && empty($resposeStatus)){
                    $resposeStatus = "pending";
                }
                if(strtolower($balance->gateway_type) == "yappy" && empty($transactionId)){
                    $transactionId = "-";
                }
                if(strtolower($balance->gateway_type) == "yappy" && empty($resposeStatus) && $balance->operation == 1){
                    $resposeStatus = "pending";
                }
                if($balance->gateway_type == 0 && intval($balance->gateway_type) === 0 && empty($transactionId)){
                    $transactionId = "admin";
                }

                Transaction::firstOrCreate([
                    "user_id" => $balance->idusuarios,
                    "service_id" => null,
                    "created_at" => empty($balance->fecha) ? $balance->created_at : $balance->fecha,
                ], [
                    "user_id" => $balance->idusuarios,
                    "service_id" => null,
                    "type" => "visa",
                    "amount" => $balance->operation == "1" ? $balance->valor : -1 * $balance->valor,
                    "tax" => $balance->tax,
                    "currency" => "USD",
                    "transaction_id" => $transactionId,
                    "accumulated_value" => $accumulated,
                    "status" => $resposeStatus,
                    "gateway" => $gateway,
                    "receipt_url" => "",
                    "created_at" => empty($balance->fecha) ? $balance->created_at : $balance->fecha,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }
    public function migrateDriverVehicles(){
        DB::beginTransaction();
        try {
            //code...
            if(!hasDbConnection("mysql_old")){
                return;
            }
            $vehicles = DB::connection("mysql_old")
            ->table("vehiculos")->where([
                ["tipo_camion", ">", 0],

            ])//->whereIn("conductores_id", ["2556", "2869"])
            ->cursor();
            foreach ($vehicles as $key => $vehicle) {
                if($vehicle->conductores_id && empty(User::find($vehicle->conductores_id))){
                    DB::rollBack();
                    Log::info("User with id:".$vehicle->conductores_id." Not found");
                    return "User with id:".$vehicle->conductores_id." Not found";
                }
                $driverId = User::find($vehicle->conductores_id)->userable_id;
                $oldModels = DB::connection("mysql_old")
                    ->table("marcas")->where("idmarcas", $vehicle->marcas_id)->first();
                $mark = Mark::where([
                    ["name", "=", $oldModels->nombre_marca]
                ])->first();
                $model = Model::firstOrCreate([
                    "mark_id" => $mark->id,
                    "name" => $mark->name,
                    "status" => $mark->status,
                ]);
                $driverVehicle = DriverVehicle::create([
                    'driver_id' => $driverId,
                    'model_id' => $model->id,
                    'height' => $vehicle->altura,
                    'wide' => $vehicle->ancho,
                    'long' => $vehicle->largo,
                    'plate' => $vehicle->placa,
                    'm3' => $vehicle->m3,
                    "color_id" => $vehicle->color_id < 1 ? 2 :$vehicle->color_id,
                    "types_transport_id" => $vehicle->tipo_camion == 8 ? 6 : $vehicle->tipo_camion,
                    "year" => $vehicle->year_car,
                    "burden" => $vehicle->carga,
                    "status" => $vehicle->estado,
                    "created_at" => $vehicle->creado,
                ]);
                $this->registerVehicleImage($driverVehicle->id, $vehicle->url_propiedad, "property_url_vehicle", $vehicle->creado);
                $this->registerVehicleImage($driverVehicle->id, $vehicle->url_revisado, "revised_url_vehicle", $vehicle->creado);
                $this->registerVehicleImage($driverVehicle->id, $vehicle->url_poliza, "policy_url_vehicle", $vehicle->creado);
                $this->registerVehicleImage($driverVehicle->id, $vehicle->url_foto, "photo_url_vehicle", $vehicle->creado);
                $this->registerVehicleImage($driverVehicle->id, $vehicle->url_derecha, "right_url_vehicle", $vehicle->creado);
                $this->registerVehicleImage($driverVehicle->id, $vehicle->url_trasera, "back_url_vehicle", $vehicle->creado);
                
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }
    public function refreshVehicles(){
        $vs = [
        //[191,  312, '959651', 'http://www.api.kamgus.com/profiles/vehiculos/20210129170107_APP_photo.png', 'http://www.api.kamgus.com/profiles/vehiculos/20210129170107_APP_UPDATE_cartrasera.png', 'http://www.api.kamgus.com/profiles/vehiculos/20210129170107_APP_UPDATE_carderecha.png', 'http://www.api.kamgus.com/profiles/vehiculos/20210129170107_APP_UPDATE_carizquierda.png', 'http://www.api.kamgus.com/profiles/vehiculos/20210129170107_APP_registro_vehicular_photo.png', 'http://www.api.kamgus.com/profiles/vehiculos/20210129170107_APP_revisado.png', 'http://www.api.kamgus.com/profiles/vehiculos/20210129170107_APP_UPDATE_poliza.png'],
        //    [315,  2548, 'Ed2509', 'http://www.api.kamgus.com/profiles/conductores/2548_Ed2509_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2548_Ed2509_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2548_Ed2509_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2548_Ed2509_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2548_Ed2509_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2548_Ed2509_APP_SEGURO_photo.png'],
        //    [319,  2556, 'EA0471', 'http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_SEGURO_photo.png'],
         //   [320,  2556, 'Hdjdjdjd', 'http://www.api.kamgus.com/profiles/conductores/2556_Hdjdjdjd_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_Hdjdjdjd_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_Hdjdjdjd_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2556_Hdjdjdjd_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_Hdjdjdjd_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_Hdjdjdjd_APP_SEGURO_photo.png'],
         //   [327,  2674, 'ED0940', 'http://www.api.kamgus.com/profiles/conductores/2674__APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2674__APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2674__APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2674__APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2674__APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2674__APP_SEGURO_photo.png'],
            //[330,  2556, 'E63826', 'http://www.api.kamgus.com/profiles/conductores/2556_E63826_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_E63826_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_E63826_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2556_E63826_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_E63826_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_E63826_APP_SEGURO_photo.png'],
            //[331,  2556, '152626', 'http://www.api.kamgus.com/profiles/conductores/2556_152626_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_152626_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_152626_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2556_152626_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_152626_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_152626_APP_SEGURO_photo.png'],
            //[332,  2556, 'ED370', 'http://www.api.kamgus.com/profiles/conductores/2556_ED370_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_ED370_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_ED370_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2556_ED370_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_ED370_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2556_ED370_APP_SEGURO_photo.png'],
         //   [337,  2869, 'AB8021', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_SEGURO_photo.png'],
         //   [342,  2872, 'ct6801', 'http://www.api.kamgus.com/profiles/conductores/2872_ct6801_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2872_ct6801_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2872_ct6801_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2872_ct6801_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2872_ct6801_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2872_ct6801_APP_SEGURO_photo.png'],
         //   [349,  145, '471475', 'http://www.api.kamgus.com/profiles/conductores/145_471475_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/145_471475_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/145_471475_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/145_471475_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/145_471475_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/145_471475_APP_SEGURO_photo.png'],
          //  [350,  2869, 'AB8021', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2869_AB8021_APP_SEGURO_photo.png'],
          //  [351,  2898, 'CK4339', 'http://www.api.kamgus.com/profiles/conductores/20230828080803954_fotoFrontal_photo.png', 'http://www.api.kamgus.com/profiles/conductores/20230828080852918_fotoTrasera_photo.png', 'http://www.api.kamgus.com/profiles/conductores/20230828080852675_fotoDerecha_photo.png', 'http://www.api.kamgus.com/profiles/conductores/20230828080852494_fotoIzquierda_photo.png', '', '', 'http://www.api.kamgus.com/profiles/conductores/20230828080852951_fotoPoliza_photo.png'],
          //  [357,  2112, '922653', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_SEGURO_photo.png'],
           // [358,  2112, '922653', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_FRONTAL_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_SEGURO_photo.png'],
           // [359,  2112, '922653', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_FRONTAL_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_TRASERA_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_LADO_photo.png', '', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_REGISTRO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_REVISADO_photo.png', 'http://www.api.kamgus.com/profiles/conductores/2112_922653_APP_SEGURO_photo.png'],
           // [372,  526, 'CJ8449', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_FRONTAL_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_TRASERA_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_LADO_photo.png', '', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_REGISTRO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_REVISADO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_SEGURO_photo.png'],
           // [373,  526, 'CJ8449', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_FRONTAL_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_TRASERA_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_LADO_photo.png', '', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_REGISTRO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_REVISADO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_CJ8449_APP_SEGURO_photo.png'],
           // [383,  282, 'BB1618', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/8c4a0a7afbb10de1e631_APP_FRONTAL_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_BB1618_APP_TRASERA_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_BB1618_APP_LADO_photo.png', '', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_BB1618_APP_REGISTRO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_BB1618_APP_REVISADO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/undefined_BB1618_APP_SEGURO_photo.png'],
           // [396,  2871, 'Jkl345', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/2bc4a9de212381b2bbb8_APP_FRONTAL_photo.png', '', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/2bc4a9de212381b2bbb8_APP_LADO_photo.png', '', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/2bc4a9de212381b2bbb8_APP_REGISTRO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/2bc4a9de212381b2bbb8_APP_REVISADO_photo.png', 'https://apikamgusv2.kamgus.com/storage/profiles/vehiculos/2bc4a9de212381b2bbb8_APP_SEGURO_photo.png'],
        ];
        $arr = [];
        foreach ($vs as $key => $item) {
            $userId = $item[1];
            $placa = $item[2];
            $mappedUrl = [];
            for ($i=3; $i < 9 && $i < count($item); $i++) { 
                $pathArray = explode("/", parse_url($item[$i], PHP_URL_PATH));
                $fileName = $pathArray[count($pathArray) - 1];
                $mappedUrl[$i-3] = $fileName;
            }
            
            $arr[] = ["userId" => $userId, "placa" => $placa, "images" => $mappedUrl, "has" => DriverVehicle::whereRaw('driver_id in (select users.userable_id from users where users.userable_type like "%Drive%" and users.id = ?)', $userId)->count()];
        }
        $imageReg = [];
        foreach ($arr as $key => $mappedItem) {
            foreach ($mappedItem["images"] as $fileName) {
                if(!empty(trim($fileName))){
                    $imgsV = Image::where([["url", "like", "%/".$fileName.""]])->orderBy("images.id", "DESC")->get(["images.id", "imageable_id", "imageable_type"])->first();
                    $imageReg[] = empty($imgsV) ? 
                        $fileName : 
                        $fileName." - ".$imgsV->id." - ".$imgsV->imageable_id." - ".$imgsV->imageable_type." ".DriverVehicle::whereRaw('driver_id in (select users.userable_id from users where users.userable_type like "%Drive%" and users.id = ?)', $mappedItem["userId"])->pluck("id");

                    //for ($i=1; $i < count($imgsV); $i++) { 
                    //    Image::where("id", $imgsV[$i])->delete();
                    //}
                }
                
            }
        }

        //return $arr;
        return $imageReg;
    }
    public function migrateImagesServices(){
        DB::beginTransaction();

        try {
            $services = DB::connection("mysql_old")
                ->table("servicios")
                ->where([
                    ["id_tipo_camion", "!=", 0],    
                    //["idservicios", ">", 7428],    
                ])
                ->whereNotIn("estado", ["Pendiente"])
                ->whereNotIn("usuarios_id", [2074]);
            foreach ($services->get() as $key => $service) {
                $this->registerOrUpdateServiceImages($service->idservicios);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            echo $th->getMessage();
        }
    }
    public function migrateTransactionServices(){
        DB::beginTransaction();
        try {
            //code...
            $services = DB::connection("mysql_old")
            ->table("servicios")
            ->where([
                ["id_tipo_camion", "!=", 0],    
                //["idservicios", ">", 7428],    
                ])
                ->whereNotIn("estado", ["Pendiente"])
                ->whereNotIn("idservicios", [4200]);
            foreach ($services->cursor() as $key => $service) {
                $this->registerServiceTransaction($service->idservicios);
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    //
    //
    public function migrateUserBonus(){
        DB::beginTransaction();
        try {
            //bonos_usuario, bonos_eu -> user_bonuses
            $services = DB::connection("mysql_old")
            ->table("bonos_usuario")
           ;
            foreach ($services->cursor() as $key => $bone) {
                if(DB::connection("mysql_old")
                    ->table("usuarios")
                    ->where("idusuarios", $bone->usuarios_id)->count() > 0
                    &&
                    DB::connection("mysql_old")
                    ->table("usuarios")
                    ->where("idusuarios", $bone->referido_id)->count() > 0
                ){
                    $userBone = UserBonuses::firstOrNew(["id" => $bone->idbu]);
                    $userBone->id = $bone->idbu;
                    $userBone->user_id = $this->getUser($bone->usuarios_id);
                    $userBone->referred_id = $this->getUser($bone->referido_id);
                    $userBone->bond_value = $bone->valor_bono;
                    $userBone->service_id = $bone->servicios_id != 0 ? $bone->servicios_id : null;
                    $userBone->used = $bone->utilizado;
                    $userBone->created_at = $bone->creado;
                    $userBone->updated_at = date("Y-m-d H:i:s");
                    $userBone->save();
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function migrateQualifications(){
        //calificaciones -> qualifications
        DB::beginTransaction();
        try {
            $services = DB::connection("mysql_old")
            ->table("calificaciones")
           ;
            foreach ($services->cursor() as $key => $row) {
                if(DB::connection("mysql_old")
                    ->table("servicios")
                    ->where("idservicios", $row->servicios_id)->count() > 0
                 
                ){
                    $model = Qualification::firstOrNew(["id" => $row->idcalificaciones]);
                    //$model->bond_value = $row->conductores_id;
                    $model->qualification = $row->calificacion;
                    $model->status = $row->estado;
                    $model->service_id = $row->servicios_id != 0 ? $row->servicios_id : null;
                    $model->observation = $row->observacion;
                    $model->created_at = $row->creado;
                    $model->updated_at = date("Y-m-d H:i:s");
                    $model->save();
                }
            }
            
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function migrateDriverAccounts(){
        //cuenta_conductor -> driver_accounts
        DB::beginTransaction();
        try {
            //bonos_usuario, bonos_eu -> user_bonuses
            $tableData = DB::connection("mysql_old")
            ->table("cuenta_conductor")
            ;
            foreach ($tableData->cursor() as $key => $row) {
                  if(DB::connection("mysql_old")
                          ->table("usuarios")
                          ->where("idusuarios", $row->idusuario)->count() > 0
                      
                      ){
                $model = DriverAccount::firstOrNew([
                        "id" => $row->id_cuenta,
                    ]);
                    $model->id = $row->id_cuenta;
                    $model->bank = $row->banco;
                    $model->account_number = $row->numero_cuenta;
                    //$model-> = $row->doc_identidad;
                    $model->driver_id = User::find($this->getUser($row->idusuario))->userable_id;
                    $model->save();
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function migrateDriverDocuments(){ //migra url de imagenes de cedula y licencia de cada usuario conductor
        //cuenta_conductor -> driver_accounts
        DB::beginTransaction();
        try {
            //bonos_usuario, bonos_eu -> user_bonuses
            $tableData = DB::connection("mysql_old")
            ->table("usuarios")
            ->where("rol", 1) //Obtengo los usuarios conductores
            ;
            foreach ($tableData->cursor() as $key => $userV1) {
                if($userV2 = User::find($this->getUser($userV1->idusuarios))){
                    if(!empty($userV1->url_licencia)){
                        $imgUrl = $userV1->url_licencia;
                        $documentType = "LICENCIA";
                        //echo "User found. Id= ".$userV1->idusuarios." ".$documentType." ".$imgUrl."<br/>";
                        $model = Document::firstOrNew([
                            "url_foto" => $imgUrl,
                            "tipo" => $documentType,
                            "driver_id" => $userV2->userable_id,
                        ]);
                        $model->url_foto = $imgUrl;
                        $model->tipo = $documentType;
                        $model->driver_id = $userV2->userable_id;
                        $model->save();
                    }
                    if(!empty($userV1->url_cedula)){
                        $imgUrl = $userV1->url_cedula;
                        $documentType = "CEDULA";
                        //echo "User found. Id= ".$userV1->idusuarios." ".$documentType." ".$imgUrl."<br/>";
                        $model = Document::firstOrNew([
                            "url_foto" => $imgUrl,
                            "tipo" => $documentType,
                            "driver_id" => $userV2->userable_id,
                        ]);
                        $model->url_foto = $imgUrl;
                        $model->tipo = $documentType;
                        $model->driver_id = $userV2->userable_id;
                        $model->save();
                    }
                }else{
                    echo "User not found. Id= ".$userV1->idusuarios."<br/>";
                }
                
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function migrateDriversEnterprise(){
        //empresa_conductor -> 
        DB::beginTransaction();
        try {
            //bonos_usuario, bonos_eu -> user_bonuses
            $services = DB::connection("mysql_old")
            ->table("empresa_conductor")
            ->where([
                ["id_tipo_camion", "!=", 0],    
                //["idservicios", ">", 7428],    
                ])
                ->whereNotIn("estado", ["Pendiente"])
                ->whereNotIn("idservicios", [4200]);
            
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function migrateServiceStates(){
        //servicio_estados -> service_statuses
        DB::beginTransaction();
        try {
            $tableData = DB::connection("mysql_old")
            ->table("servicio_estados")
            ;
            //,,,'Conductor activo','Descargado'
            $statusDecode = [
                "el conductor esta próximo a llegar al punto a"	=> 'estado de servicio', 
                "el conductor llego al punto a"	=> 'estado de servicio', 
                "el conductor llego al punto b"	=> 'llego al punto b', 
                "el conductor llego al punto c"	=> 'estado de servicio', 
                "el conductor llego al punto d"	=> 'estado de servicio', 
                "el conductor termina el servicio"	=> 'servicio finalizado', 
                "el conductor ya llego al punto a"	=> 'estado de servicio', 
                "el servicio fue cancelado"	=> 'estado de servicio', 
                "el servicio fue rechazado"	=> 'servicio rechazado', 
                "el servicio ha terminado"	=> 'servicio finalizado', 
                "el servicio rechazado"	=> 'estado de servicio', 
                "estado de servicio"	=> 'estado de servicio', 
                "estado del servicio"	=> 'estado de servicio', 
                "nuevo servicio"	=> 'nuevo servicio', 
                "nuevo servicio v2"	=> 'nuevo servicio', 
                "rechazado"	=> 'servicio rechazado', 
                "reserva confirmado conductor"	=> 'estado de servicio', 
                "reserva rechazada conductor"	=> 'servicio rechazado', 
                "servicio activo"	=> 'estado de servicio', 
                "servicio cancelado"	=> 'estado de servicio', 
                "servicio reservado"=> 'estado de servicio', 
            ];
            $statusDescriptions = [
                "Cliente ha aceptado tu oferta" => 'Conductor activo',

            ];
            foreach ($tableData->cursor() as $key => $row) {
                if(DB::connection("mysql_old")
                    ->table("usuarios")
                    ->where("idusuarios", $row->conductor_id)->count() > 0
                    && DB::connection("mysql_old")
                    ->table("servicios")
                    ->where([
                        ["id_tipo_camion", "!=", 0],   
                        ["idservicios", "=", $row->servicios_id],   
                    ])
                    ->count() > 0
                ){
                    $userServiceStatus = User::find($this->getUser($row->conductor_id));
                    $model = ServiceStatus::firstOrNew(["id" => $row->idse]);
                    $model->id = $row->idse;
                    $model->service_id = $row->servicios_id;
                    $model->status = empty($statusDescriptions[$row->motivo]) ? $statusDecode[strtolower($row->estado)] : $statusDescriptions[$row->motivo];
                    $model->it_was_read = $row->it_was_read;
                    $model->description = $row->motivo;
                    $model->servicestatetable_id = $userServiceStatus->userable_id;
                    $model->servicestatetable_type = $userServiceStatus->userable_type;
                    $model->created_at = $row->creado;
                    $model->updated_at = date("Y-m-d H:i:s");
                    $model->save();

                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function migrateUserPhotos(){
        DB::beginTransaction();
        try {
            //bonos_usuario, bonos_eu -> user_bonuses
            $users = DB::connection("mysql_old")
            ->table("usuarios")
            ->where([
                ["url_foto", "!=", ""],
            ])
            ->whereNotNull("url_foto");
            foreach ($users->cursor() as $key => $user) {
                if(User::where("id", $user->idusuarios)->count() > 0){
                    $tId = $user->idusuarios;
                    $type = User::class;
                    if($user->rol == 1){
                        $usu = User::find($this->getUser($tId));
                        $tId = $usu->userable_id;
                        $type = $usu->userable_type;
                        Driver::where("id", $tId)->update(["url_foto_perfil" => $user->url_foto]);
                    }else
                    if($user->rol == 2){
                        $usu = User::find($this->getUser($tId));
                        $tId = $usu->userable_id;
                        $type = $usu->userable_type;
                        Customer::where("id", $tId)->update(["url_foto_perfil" => $user->url_foto]);
                    }else{
                        $image = new Image();
                        $image->url = $user->url_foto;
                        $image->is = "profile"; 
                        $image->imageable_id = $tId;
                        $image->imageable_type = $type;
                        $image->created_at = date("Y-m-d H:i:s");
                        $image->save();  
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function refreshSuggestedPriceAndDriverServicesStatusV2($userId){
        DB::connection("mysql_old")->beginTransaction();
        try{
        $services = Service::whereIn("id", [
            "382",
            "426",
            "1525",
            "1615",
            "2116",
            "2311",
            "2442",
            "2585",
            "2633",
            "2638",
            "2670",
            "2670",
            "2743",
            "2754",
            "3001",
            "3001",
            "3029",
            "3034",
            "3055",
            "3055",
            "3057",
            "3057",
            "3119",
            "3145",
            "3145",
            "3154",
            "3163",
            "3173",
            "3173",
            "3181",
            "3183",
            "3187",
            "3205",
            "3206",
            "3209",
            "3212",
            "3214",
            "3216",
            "3227",
            "3294",
            "3303",
            "3337",
            "3374",
            "3374",
            "3380",
            "3397",
            "3400",
            "3409",
            "3409",
            "3431",
            "3438",
            "3341",
            "3523",
            "3559",
            "3576",
            "3619",
            "3667",
            "3668",
            "3668",
            "3682",
            "3687",
            "3713",
            "3644",
            "3741",
            "3753",
            "3753",
            "3750",
            "3785",
            "3805",
            "3813",
            "3904",
            "3945",
            "3945",
            "4009",
            "4040",
            "4094",
            "4094",
            "4275",
            "4275",
            "4385",
            "4509"
        ])->cursor();
        foreach ($services as $key => $service) {
            $ds = DriverService::firstOrCreate(
                ['service_id' => $service->id, 'driver_id' => $service->driver_id],
                [
                    "driver_id" => $service->driver_id,
                    "endTime" => $service->created_at,
                    "startTime" => $service->created_at,
                    "status" => $service->estado,
                    "confirmed" => "SI",
                    "reservation_date" => $service->fecha_reserva,
                    "observation" => "",
                    "suggested_price" => $service->precio_sugerido,
                    "ispaid" => "pendiente",
                    "commission" => "",
                ]
            );
            $ds->save();
        }
        DB::connection("mysql_old")->commit();
        echo "Listo";
        } catch (\Exception $e) {
            DB::connection("mysql_old")->rollBack();
            return json_encode([
                $e->getMessage(),
                $e->getTraceAsString(),
            ]);
        }   
    }
    public function refreshSuggestedPriceAndDriverServicesStatus($userId){
        //SELECT conductor_servicios.idconductor_servicios, conductor_servicios.estado, servicios.estado, servicios.* 
        //FROM `conductor_servicios` 
        //left join servicios on servicios.idservicios = conductor_servicios.servicios_id 
        //where conductores_id=94 and servicios.estado="Terminado" and servicios.usuarios_id != conductores_id 
        //ORDER BY `conductor_servicios`.`servicios_id` ASC;
        DB::connection("mysql_old")->beginTransaction();
        try{
        $services = DB::connection("mysql_old")
                ->table("conductor_servicios")
                ->leftJoin("servicios", "servicios.idservicios", "=", "conductor_servicios.servicios_id")
                ->where([
                    ["conductores_id", "=", $userId],
                    ["servicios.estado", "=", "Terminado"],
                    ["servicios.usuarios_id", "!=", "conductores_id"],
                ])
                ->get([
                    "conductor_servicios.idconductor_servicios", 
                    "conductor_servicios.estado", 
                    "servicios.estado", 
                    "servicios.*", 
                ])
                ;
        dd(($services->pluck("idservicios")));
        //Insertar precio sugerido
        foreach ($services as $key => $service) {
            if(DB::connection("mysql_old")
            ->table("precio_sugerido")->where([
                ["servicio_id", "=", $service->idservicios],
                ["usuario_id", "=", $service->usuarios_id],
                ["by_driver", "=", 0],
            ])->count() <= 0){
                
                echo json_encode([
                    "servicio_id" => $service->idservicios,
                    "precio" => $service->valor,
                    "tiempo" => $service->creado,
                    "usuario_id" => $service->usuarios_id,
                    "by_driver" => 0,
                    "usuarios_id" => $userId
                ])."<br/>";
                /*
                DB::connection("mysql_old")
                    ->table("precio_sugerido")
                    ->insertGetId([
                        "servicio_id" => $service->idservicios,
                        "precio" => $service->valor,
                        "tiempo" => $service->creado,
                        "usuario_id" => $service->usuarios_id,
                        "by_driver" => 0,
                    ]);
                DB::connection("mysql_old")
                ->table("servicios")
                ->where("idservicios", $service->idservicios)
                ->update([
                    "usuarios_id" => $userId
                ]);
                */
            }
        }
        DB::connection("mysql_old")->commit();
        echo "Listo";
        } catch (\Exception $e) {
            DB::connection("mysql_old")->rollBack();
            return json_encode([
                $e->getMessage(),
                $e->getTraceAsString(),
            ]);
        }
    }
    //Refrescar el estado de lo conductores de los servicios especificados.
    public function refreshDriverService(){
        $services = [
            //'660',
            // '900',
            // '997',
            // '2258',
            // '2670',
            // '2893',
            // '2963',
            // '3001',
            // '3055',
            // '3057',
            // '3126',
            // '3145',
            // '3151',
            // '3172',
            // '3173',
            // '3180',
            // '3193',
            // '3258',
            // '3339',
            // '3374',
            // '3409',
            // '3564',
            // '3630',
            // '3651',
            // '3668',
            // '3753',
            // '3904',
            // '3945',
            // '4012',
            // '4017',
            // '4019',
            // '4032',
            // '4053',
            // '4094',
            // '4199',
            // '4272',
            // '4275',
            // '4279',
            // '4385',
            // '4583',
            // '4786',
            // '4875',
            // '6954',
            // '7757'
            //382, 382, 1525, 1525, 1615, 1615, 2116, 2116, 2311, 2311, 2442, 2442, 2585, 2585, 2633, 2633, 2638, 2638, 2670, 2670, 2743, 2743, 2754, 2754, 2963, 2963, 3001, 3001, 3001, 3034, 3034, 3055, 3055, 3055, 3057, 3057, 3057, 3119, 3119, 3126, 3126, 3151, 3151, 3154, 3154, 3163, 3163, 3172, 3172, 3173, 3173, 3173, 3181, 3181, 3183, 3183, 3187, 3187, 3193, 3193, 3205, 3205, 3206, 3206, 3209, 3209, 3212, 3212, 3214, 3214, 3216, 3216, 3227, 3227, 3294, 3294, 3303, 3303, 3337, 3337, 3339, 3339, 3341, 3341, 3374, 3374, 3374, 3380, 3380, 3397, 3397, 3400, 3400, 3409, 3409, 3409, 3431, 3431, 3438, 3438, 3523, 3523, 3559, 3559, 3564, 3564, 3576, 3576, 3619, 3619, 3630, 3630, 3644, 3644, 3651, 3651, 3667, 3667, 3668, 3668, 3668, 3682, 3682, 3687, 3687, 3713, 3713, 3741, 3741, 3750, 3750, 3753, 3753, 3753, 3785, 3785, 3805, 3805, 3813, 3813, 3945, 3945, 3945, 4009, 4009, 4017, 4017, 4019, 4019, 4032, 4032, 4040, 4040, 4094, 4094, 4199, 4199, 4272, 4272, 4275, 4275, 4275, 4509, 4509, 7698, 7698, 7700, 7700, 7701, 7701, 7705, 7705, 7710, 7710, 7754, 7754, 7754, 7754, 7757, 7757, 7757, 7757
            3668 , 4094, 4275
        ];
        $oldDrivers = DB::connection("mysql_old")
            ->table("usuarios")->where("usuarios.rol", 1)->get()->pluck("idusuarios"); 

        DB::beginTransaction();
        try {

            $driversSelected = DB::connection("mysql_old")
                        ->table("conductor_servicios")
                        ->whereIn("servicios_id", $services)->get();
                        //->whereIn("servicios_id", DB::connection("mysql_old")
                        //->table("conductor_servicios")->whereIn("conductores_id", $oldDrivers)->get()->pluck("servicios_id"))->get();
                    foreach ($driversSelected as $key => $cs) {
                        $service = DB::connection("mysql_old")
                        ->table("servicios")->where("idservicios", $cs->servicios_id)->first();
                        $auxDId = User::find($cs->conductores_id)->userable_id;
                        if(Service::where("id", $service->idservicios)->count() <= 0){
                            continue;
                        }
                            $driverPrice = DB::connection("mysql_old")
                                ->table("precio_sugerido")->where([
                                    ["servicio_id", "=", $service->idservicios],
                                    ["usuario_id", "=", $cs->conductores_id],
                                    ["by_driver", "=", 1],
                                ])->first();
                            if(strtolower($cs->estado) == "terminado"){
                                Service::where("id", $service->idservicios)->update(["driver_id" => $auxDId]);
                            }
                            DB::table("driver_services")->where([
                                ["service_id", "=", $service->idservicios],
                                ["driver_id", "=", $auxDId],
                            ])->delete();
                            DB::table("driver_services")->where([
                                ["service_id", "=", $service->idservicios],
                                ["driver_id", "=", $auxDId],
                            ])->updateOrInsert([
                                "service_id" => $service->idservicios,
                                "startTime" => $cs->startTime != '0000-00-00 00:00:00' ? $cs->startTime : $service->creado,
                                "endTime" => $cs->endTime != '0000-00-00 00:00:00' ? $cs->endTime : $service->creado,
                                "driver_id" => $auxDId,
                                "status" => $cs->estado,
                                "confirmed" => empty($cs->confirmado) ? "no" : $cs->confirmado,
                                "reservation_date" => empty($cs->fecha_reserva) ? ($service->fecha_reserva != '0000-00-00 00:00:00' ? $service->fecha_reserva : $service->creado) : ($cs->fecha_reserva != '0000-00-00 00:00:00' ? $cs->fecha_reserva : $service->creado),
                                "observation" => empty($cs->observacion) ? "" : $cs->observacion,
                                "suggested_price" => empty($driverPrice) ? $service->valor : $driverPrice->precio,
                                "ispaid" => empty($cs->ispagado) ? "pendiente" : $cs->ispagado,
                                "commission" => empty($cs->comision) ? "" : $cs->comision,
                            ]);
                    }
                    DB::commit();
            echo "Listo";
        } catch (\Exception $e) {
            DB::rollBack();
            return json_encode([
                $e->getMessage(),
                $e->getTraceAsString(),
            ]);
        }
    }
    public function refreshPaidFieldInService(){
        if(!hasDbConnection("mysql_old")){
            return 1;
        }
        $servicesV2 = Service::get();
        foreach ($servicesV2 as $key => $service) {        
            $isPaid = DB::connection("mysql_old")
                                ->table("servicios")->where("idservicios", $service->id)->first();
                                if(!empty($isPaid)){
                                    Service::where("id", $service->id)->update(["pago" => $isPaid->ispagado]);                                    
                                }else{
                                    echo $service->id." ".$service->estado."<br>";
                                }
        }
    }
    public function migrateImageLocation(){
        $mainDirectory = "test1";
        $destinyPath = "/apikamgusv2.kamgus.com/storage/app/public/".$mainDirectory."/";
        $links = [
            "https://www.api.kamgus.com/" => "/public_html/api/",
            "http://www.api.kamgus.com/" => "/public_html/api/",
            //"https://www.kamgus.com/" => "/public_html",
            //"http://www.kamgus.com/" => "/public_html",
        ];
        //Storage::copy('old/file.jpg', 'new/file.jpg');
        $disk = Storage::build([
            'driver' => 'local',
            'root' => "/home/kamgus",
        ]);
        $mappedImages = [];
        foreach ($links as $key => $link) {
            $images = Image::where([
                ["url", "like", $key."%"],
            ])
            //->limit(10)
            //->offset(3)
            //->get();
            ->cursor();
            foreach ($images as $clave => $imgUrl) {
                $path = str_replace($key, "/", $imgUrl->url);
                if($disk->exists($link.$path)){
                    //$path = pathinfo($path)["dirname"];
                    $mappedImages[][basename($imgUrl->url)] = [
                        //$path,
                        $imgUrl->url,
                        url("storage/".$mainDirectory.$path),
                    ];
                    if(!$disk->exists($destinyPath.$path)){
                        $disk->copy($link.$path, $destinyPath.$path);
                        //$imgUrl->url = url("storage/".$mainDirectory.$path);
                        //$imgUrl->save();
                    }else{
                        $mappedImages[][basename($imgUrl->url)] = "Ya existe: ".$link.$path;
                    }
                }else{
                    $mappedImages[][basename($imgUrl->url)] = "No existe: ".$link.$path;
                }
            }
        }
        return json_encode($mappedImages);
    }
    public function refreshCreatedAtUser(){
        DB::beginTransaction();
        try {
            //bonos_usuario, bonos_eu -> user_bonuses
            //$users = User::all();
            if(!empty(request()->u)){
                $user = User::find(request()->u);
                echo $user->id." - ".$user->userable_type." - ".json_encode($user->getRoleNames())." ".$user->status."<br>";
                return;
            }
            $users = User::where("userable_type", Company::class)->get();
            foreach ($users as $key => $user) {
                //$user->assignRole("Cliente");
                echo $user->id." - ".$user->userable_type." - ".json_encode($user->getRoleNames())."<br>";
                /*
                $creado = $this->getUser2($user->id);
                User::where("id", $user->id)->update([
                    "created_at" => empty($creado) ? $user->updated_at: $creado->creado,
                ]);
                echo (empty($creado) ? $user->id: $creado->creado)."- <br/>";*/
            }
            
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." Line: ".$th->getLine()."<br>".$th->getTraceAsString());
        }
    }
    public function moveCompanyToCustomer(){
        DB::beginTransaction();
        try{
            $companies = DB::select("SELECT users.id as user_id, users.email, users.userable_id, companies.* 
                FROM users 
                inner join companies on users.userable_id = companies.id 
                WHERE users.email 
                    IN( 'fperez@alohapanama.com', 
                    'info@viralsolutionss.com', 
                    'empleado@kamgus.com', 
                    'ortiz@selecto.com.pa', 
                    'info@kamgus.com', 
                    'andres@kamgus.com', 
                    'froberts@alohapanama.com', 
                    'raulpe85@gmail.com', 
                    'panafoto@kamgus.com', 
                    'cargoexpress@kamgus.com', 
                    '1@tucargaexpress.com', 
                    'mariam@kamgus.com', 
                    'aliss@kamgus.com', 
                    'quales@kamgus.com', 
                    'estebanbetinalvarez@gmail.com' ) ORDER BY users.id;");
            foreach ($companies as $company) {
                //echo "<pre>".$company->user_id."<br/></pre>";
                $companyId = $company->userable_id;
                $customer = new Customer();
                $customer->nombres = $company->nombre_empresa;
                $customer->apellidos = $company->nombre_contacto;
                $customer->telefono = $company->telefono;
                $customer->direccion = $company->direccion;
                $customer->url_foto_perfil = $company->url_foto_perfil;
                $customer->created_at = $company->created_at;
                $customer->updated_at = $company->updated_at;
                $saved = $customer->save();
                if($saved){
                    Company::where("id", $company->userable_id)->delete();
                    User::where("id", $company->user_id)->update(["userable_id" => $customer->id, "userable_type" => Customer::class]);                    
                }
            }
            DB::commit();
            return "Listo";
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            die($th->getMessage()." Line: ".$th->getLine()."<br>".$th->getTraceAsString());
        }
                
    }
    private function registerVehicleImage($vehicleId, $url, $is, $created_at){
        if(empty($url)){
            return;
        }
        $image = new Image();
        $image->url = $url;
        $image->is = $is; 
        $image->imageable_id = $vehicleId;
        $image->imageable_type = DriverVehicle::class;
        $image->created_at = $created_at;
        $image->save();  
    }
    private function getUser($userId){
        if(User::where("id", $userId)->count() < 1){
            $oldUser = DB::connection("mysql_old")
            ->table("usuarios")->where("idusuarios", $userId)->first();
            return User::where("email", $oldUser->email)->first()->id;
        }
        return $userId;
    }
    private function getUser2($userId){
        //if(User::where("id", $userId)->count() < 1){
            $oldUser = DB::connection("mysql_old")
            ->table("usuarios")->where("idusuarios", $userId)->first();
            return $oldUser;
        //}
    }
    //Busca los registros de imagenes de una orden especificada y los copia en la base de datos del sistema.
    private function registerOrUpdateServiceImages($serviceId){
        $service = DB::connection("mysql_old")
                ->table("servicios")
                ->where([
                    ["idservicios", "=", $serviceId]
                ])
                ->first()
                ;
        if(empty($service)){
            return false;
        }
        $serviceImages = DB::connection("mysql_old")
                        ->table("fotos_servicio")->where([
                            ["servicios_id", "=", $service->idservicios],
                        ])->get();
        
        foreach ($serviceImages as $key => $serviceImage) {
            //APP_POINT_ -> driver
            //SERVICIO_7422, _initial_ -> customer
            //$image = new Image();
            $image = Image::firstOrNew([
                "imageable_id" => $service->idservicios,
                "imageable_type" => Service::class,
                "url" => $serviceImage->url_foto,
            ]);
            $image->url = $serviceImage->url_foto;
            $image->is = "service_point_".$serviceImage->punto; 
            $image->imageable_id = $service->idservicios;
            $image->imageable_type = Service::class;
            $image->created_at = $serviceImage->creado;
            $con[] = $image->save();    
        }
    }    
    private function registerServiceTransaction($serviceId){
        $service = DB::connection("mysql_old")
                ->table("servicios")
                ->where([
                    ["idservicios", "=", $serviceId],
                    ["tipo_pago", "!=", "Efectivo"],
                ])
                ->first()
                ;
        if(empty($service)){
            return false;
        }
        $customerPrice = DB::connection("mysql_old")
                    ->table("precio_sugerido")->where([
                        ["servicio_id", "=", $service->idservicios],
                        ["by_driver", "=", 0],
                        
                    ])
                    //->whereNotIn("usuario_id", [2074])
                    ->first();
        $customerId = $this->getUser(empty($customerPrice) ? $service->usuarios_id : $customerPrice->usuario_id);
        if($service->tipo_pago != "Efectivo" && !empty($service->tokenTarjeta)){
            $tstatu = strtolower($service->tipo_pago) == "yappy" && $service->estado == "Cancelado" && (empty($service->Response) || $service->Response == "-") ? "canceled" :$service->Response;
            if($tstatu == "" || $tstatu == "null"){
                $tstatu = "failed";
            }else
            if($tstatu == "fail"){
                $tstatu = "failed";
            }else
            if($tstatu == "success"){
                $tstatu = "succeeded";
            }
            $userInfo = DB::connection("mysql_old")->table("usuarios")->where([
                        ["idusuarios", "=", $customerId],
                    ])
                    //->whereNotIn("usuario_id", [2074])
                    ->first();
            if(!empty($userInfo)){
                echo "Customer info: ".$userInfo->idusuarios." ".$userInfo->rol."<br>";
            }
            echo "Registering the payment: ".$service->idservicios." ".$service->estado."<br>";
            if(strpos($tstatu, "{") !== false){
                $tstatu = json_decode($tstatu)->IsSuccess ? "succeeded" : "failed";
            }
            $customerId = empty($customerPrice) ? $service->usuarios_id : $customerPrice->usuario_id;
            $customerId = $this->getUser($customerId);
            $tipoPago = null;
            if (strlen($service->tokenTarjeta) > 0) {
                if (strpos(substr($service->tokenTarjeta, 0, 4), "_") !== false) {
                    $tipoPago = "stripe";
                }else if(strtotime($service->creado) < strtotime("2023-03-22")){
                    $tipoPago = $service->tipo_pago;
                    if($service->tipo_pago == "Tarjeta Crédito"){
                        $tipoPago = "metro";
                    }
                }else{
                    $tipoPago = "yappy";                    
                }
            }
            Transaction::firstOrCreate([
                "user_id" => $customerId,
                "service_id" => $service->idservicios,
            ], [
                "user_id" => $customerId,
                "service_id" => $service->idservicios,
                "type" => "visa",
                "amount" => floatval($service->valor),
                "tax" => 0,
                "currency" => "USD",
                "transaction_id" => $service->tokenTarjeta,
                "status" => $tstatu == "null" ? 'failed' : $tstatu,
                "gateway" => $tipoPago,
                "receipt_url" => "",
                "created_at" => $service->creado,
            ]);
        }
    }
    
}
