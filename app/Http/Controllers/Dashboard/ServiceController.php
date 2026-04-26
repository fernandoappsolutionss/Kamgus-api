<?php

namespace App\Http\Controllers\Dashboard;

use App\Classes\K_HelpersV1;
use App\Constants\Constant;
use App\Http\Resources\Admin\AdminServiceResource;
use App\Http\Resources\Admin\AdminServiceCollection;
use App\Http\Resources\Admin\UserCollection;
use App\Http\Resources\Customers\ServiceResource;
use App\Http\Resources\Customers\ServiceCollection;
use App\Http\Resources\Drivers\DriverCollection;
use App\Http\Resources\Drivers\DriverTokenCollection;
use App\Models\ArticleService;
use App\Models\CustomArticle;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\FcmToken;
use App\Models\Route;
use App\Models\Service;
use App\Models\Serviceable;
use App\Models\User;
use App\Notifications\SendPushNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use GGInnovative\Larafirebase\Facades\Larafirebase;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Arr;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\Image;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services for auth user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ServiceResource::collection(
            Service::where('customer_id', '=', Auth::user()->userable->id)->paginate(10)
        );
    }

    public function store2(Request $request, $id)
    {
        return "";
        switch ($id) {
            case 'gethistorialserviciosbyconductor':
                //{ "idconductor": idusuario },
                $driverId = request()->idconductor;
                return response()->json($this->gethistorialserviciosbyconductor($driverId));
                break;
            case 'setPagosServicios':
                //{ "idservicios": ids, "conductor_id": idusuario, "tabActive": tabActive },
                $driverId = request()->driver_id;
                return response()->json($this->setPagosServicios($driverId));
                break;
            case 'gethistorialpagosbyconductor':
                //"idconductor": idusuario
                $driverId = request()->driver_id;
                return response()->json($this->gethistorialpagosbyconductor($driverId));
                break;
            case 'makePartialTransaction':
                $transactionId = request()->id;
                $driverId = request()->id_usuario;
                return response()->json($this->makePartialTransaction($transactionId, $driverId));
                break;
            case 'notify_takeout': 
                //id,
                //id_usuario
                $tId = request()->id;
                $driverId = request()->id_usuario;
                return response()->json($this->notify_takeout($tId, $driverId));

                break;
            case 'getconduresdisponiblesempresas':
                //{ "fecha_reserva" : fecha, "idcamion" : idcamion, "idempresa" : idEmpresa },
                $driverId = request()->driver_id;
                return response()->json($this->getconduresdisponiblesempresas($driverId));
                break;
            case 'getopcionesNV3': //GET
                //nvl
                $driverId = request()->driver_id;
                return response()->json($this->getopcionesNV3($driverId));
                break;
            case 'getconduresdisponibles': 
                //{ "fecha_reserva" : fecha, "idcamion" : idcamion },
                $fechaReserva = request()->fecha_reserva;
                $idcamion = request()->idcamion;
                return response()->json($this->getconduresdisponibles($fechaReserva, $idcamion));
                break;
            
            default:
                return "Opcion invalida";
                break;
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function servicesxvehicles(Request $request)
    {

        $rules = [
            'tiempo' => 'required|max:255',
            'kilometraje' => 'required|max:255',
            'valor' => 'required|max:255',
            'id_tipo_camion' => 'required|max:255',
            'tipo_servicio' => 'required',
            'precio_real' => 'required|max:255',
            'precio_sugerido' => 'nullable|max:255',
            'tipo_pago' => 'required|max:255',
            'descripcion' => 'required|max:255',

            'primer_punto' => 'required|max:255',
            'latitud_primer_punto'  => 'required|max:255',
            'longitud_primer_punto' => 'required|max:255',
            'segundo_punto' => 'required|max:255',
            'latitud_segundo_punto'  => 'required|max:255',
            'longitud_segundo_punto' => 'required|max:255',
            'tercer_punto' => 'nullable|max:255',
            'latitud_tercer_punto'  => 'nullable|max:255',
            'longitud_tercer_punto' => 'nullable|max:255',
        ];

        $this->validate($request, $rules);

        //guardar el servicio
        $service = new Service();
        $service->tiempo = $request->tiempo;
        $service->kilometraje = $request->kilometraje;
        $service->fecha_reserva = Carbon::now();
        $service->tipo_transporte = $request->id_tipo_camion;
        $service->tipo_servicio = $request->tipo_servicio;
        $service->precio_real = $request->precio_real;
        $service->precio_sugerido = $request->precio_sugerido;
        $service->tipo_pago = $request->tipo_pago;
        $service->descripcion = $request->descripcion;
        $service->customer_id = Auth::user()->userable->id;
        $service->user_id     = Auth::user()->id;
        $service->save();
        
        //guardar la ruta del servicio
        $route = new Route();
        $route->primer_punto = $request->primer_punto;
        $route->latitud_primer_punto = $request->latitud_primer_punto;
        $route->longitud_primer_punto = $request->longitud_primer_punto;
        $route->segundo_punto = $request->segundo_punto;
        $route->latitud_segundo_punto = $request->latitud_segundo_punto;
        $route->longitud_segundo_punto = $request->longitud_segundo_punto;
        $route->service_id = $service->id;

        if($route->save()){
            return response()->json([ 'msg' => 'Servicio solicitado']);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function servicesxarticles(Request $request)
    {
       
        $rules = [
            'tiempo' => 'required|max:255',
            'kilometraje' => 'required|max:255',
            'valor' => 'required|max:255',
            'id_tipo_camion' => 'required|max:255',
            'tipo_servicio' => 'required',
            'precio_real' => 'required|max:255',
            'precio_sugerido' => 'nullable|max:255',
            'tipo_pago' => 'required|max:255',
            'descripcion' => 'required|max:255',

            'primer_punto' => 'required|max:255',
            'latitud_primer_punto'  => 'required|max:255',
            'longitud_primer_punto' => 'required|max:255',
            'segundo_punto' => 'required|max:255',
            'latitud_segundo_punto'  => 'required|max:255',
            'longitud_segundo_punto' => 'required|max:255',
            'tercer_punto' => 'nullable|max:255',
            'latitud_tercer_punto'  => 'nullable|max:255',
            'longitud_tercer_punto' => 'nullable|max:255',
            'articulos' => 'required|array'
        ];

        $this->validate($request, $rules);

        //guardar el servicio
        $service = new Service();
        $service->tiempo = $request->tiempo;
        $service->kilometraje = $request->kilometraje;
        $service->fecha_reserva = Carbon::now();
        $service->tipo_transporte = $request->id_tipo_camion;
        $service->tipo_servicio = $request->tipo_servicio;
        $service->precio_real = $request->precio_real;
        $service->precio_sugerido = $request->precio_sugerido;
        $service->tipo_pago = $request->tipo_pago;
        $service->descripcion = $request->descripcion;
        $service->customer_id = Auth::user()->userable->id;
        $service->user_id     = Auth::user()->id;
        $service->estado      = "ACTIVO";
        $service->save();
        
        //guardar la ruta del servicio
        $route = new Route();
        $route->primer_punto = $request->primer_punto;
        $route->latitud_primer_punto = $request->latitud_primer_punto;
        $route->longitud_primer_punto = $request->longitud_primer_punto;
        $route->segundo_punto = $request->segundo_punto;
        $route->latitud_segundo_punto = $request->latitud_segundo_punto;
        $route->longitud_segundo_punto = $request->longitud_segundo_punto;
        $route->service_id = $service->id;
        $route->save();

        //almacenamos los articulos en la nueva variable
        $articulos = $request->articulos;

        //recorremos el array de articulos
        foreach ($articulos as $articulo){

            if($articulo["id"]){

                $articles = new Serviceable();
                $articles->service_id = $service->id;
                $articles->serviceable_id = $articulo["id"];
                $articles->serviceable_type = 'App\Models\Article';
                $articles->amount = $articulo["cantidad"];
                $articles->save();

            }else{
                //añadir articulo personalizado
                $custom_article = new CustomArticle();
                $custom_article->name = $articulo["name"];

                //evaluar si llega una imagen
                if($articulo["imagen"]){
                    // $name = time() . $articulo["imagen"]->getClientOriginalName();
                    // $file = $request->file('cedula');
                    $path = public_path() . '/article/images'; 
                    $name = time() . $articulo["imagen"]->getClientOriginalName();
                    $articulo["imagen"]->move($path, $name);
                    // $file->move($path, $name);

                    $custom_article->url_imagen = $name;
                }else{
                    $custom_article->url_imagen = '123';
                }

                $custom_article->m3 = $articulo["m3"];
                $custom_article->altura = $articulo["altura"];
                $custom_article->ancho = $articulo["ancho"];
                $custom_article->largo = $articulo["largo"];
                $custom_article->price = $articulo["precio"];
                $custom_article->sub_category_id = $articulo["sub_category_id"];

                $custom_article->save();

                //guardar en la tabla serviceable
                $articles = new Serviceable();
                $articles->service_id = $service->id;
                $articles->serviceable_id = $custom_article->id;
                $articles->serviceable_type = 'App\Models\CustomArticle';
                $articles->amount = $articulo["cantidad"];
                $articles->save();
            }


        }
    
        return response()->json([ 'msg' => 'Servicio solicitado']);    
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new ServiceResource(Service::findOrFail($id));
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

    /**
     * returns all the services active for the auth user
     * @return \App\Models\Service
     * 
    */
    public function own_active_services(){

        $user =  Auth::user();

        if($user->can('listar todos los servicios activos')){

            return new AdminServiceCollection(Service::where('estado', '=', 'ACTIVO')->paginate(10));

        }else{
            
            return new ServiceCollection(Service::where(
                [ 
                    ['user_id', '=', $user->id], 
                    ['estado', '=', 'ACTIVO']
                ]
            )->paginate(10));

        }


    }

    /**
     * returns all the services scheduled for the auth user
     * @return \App\Models\Service
     * 
    */
    public function own_scheduled_services(){

        $user =  Auth::user();

        if($user->can('listar todos los servicios programados')){

            return new AdminServiceCollection(Service::where('estado', '=', 'PROGRAMAR')->paginate(10));

        }else{

            return new ServiceCollection(Service::where(
                [ 
                    ['user_id', '=', $user->id], 
                    ['estado', '=', 'PROGRAMAR']
                ]
            )->paginate(10));

        }

    }

    /**
     * returns all the history of services for the auth user
     * @return \App\Models\Service
     * 
    */
    public function own_historical_services(){

        $user = Auth::user();

        if($user->can('listar todo el historial de servicios')){

            return new AdminServiceCollection(Service::where(
                [
                    ['estado', '!=', 'ACTIVO'],
                    ['estado', '!=', 'PROGRAMAR'],
                ]
            )->paginate(10));

        }else{

            return new ServiceCollection(Service::where(
                [ 
                    ['user_id', '=', $user->id], 
                    ['estado', '!=', 'ACTIVO'],
                    ['estado', '!=', 'PROGRAMAR']
                ]
            )->paginate(10));

        }

    }

    public function search_service_history(Request $request){

        $user = Auth::user();

        $status = $request->status;

        if($request->initial_date){
            $initial_date = $request->initial_date;
        }else{
            $initial_date = Carbon::now()->subYears(2);
        }

        if($request->final_date){
            $final_date = $request->final_date;
        }else{
            $final_date = Carbon::now();
        }

        if($user->can('filtrar servicios administrador')){
            
            return new AdminServiceCollection(
                Service::where(
                    [
                        ['estado', '!=', 'ACTIVO'],
                        ['estado', '!=', 'PROGRAMAR'],
                    ]
                )
                ->history($initial_date, $final_date)
                ->status($status)
                ->orderBy('created_at')
                ->paginate($request->perPage)
            );
            
        }else{
            
            return new ServiceCollection(
                Service::where(
                    [
                        ['user_id', '=', $user->id],
                        ['estado', '!=', 'ACTIVO'],
                        ['estado', '!=', 'PROGRAMAR'],
                    ]
                )->history($initial_date, $final_date)
                ->status($status)
                ->orderBy('created_at')
                ->paginate($request->perPage)
        );

        }
        
    }

    public function notify_drivers(){
        
        $users = Driver::all()->load('user')->pluck('user');

        $id_users = Arr::pluck($users, 'id');

        $tokens = [];

        foreach ($id_users as $value) {

            $user = User::find($value);
            
            foreach ($user->fcmtokens as $value) {
                $tokens = Arr::prepend($tokens, $value->token);
            }
        }

        Notification::send('esteban', new SendPushNotification('otra prueba cristian', 'probando', $tokens));

    }

    public function cancel_service(Request $request){

        $rules = [
            'service_id' => 'required'
        ];

        $this->validate($request, $rules);

        $service = Service::findOrFail($request->service_id);
        $service->estado = 'CANCELADO';

        if($service->save()){
            return response()->json(['msg' => Constant::CANCEL_SERVICE]);
        }

    }
    public function getAvailableDrivers(){
        $data = request()->all();
        $data['fecha_reserva'] = explode(' ', $data['fecha_reserva']);
        $data['fecha_reserva'] = $data['fecha_reserva'][0];
        $data["idcamion"] = $data["idcamion"] == 8 ? 6 : $data["idcamion"];
        /*
        "id": "20",
        "email": "kamgus-driver_test_002@outlook.com",
        "email_verified_at": null,
        "password": "$2y$10$9ym6/0WDwFnr/mI70BGmc.YsMnV200bednByxgaagQGivf/rmYVky",
        "remember_token": null,
        "u.direccion_ip": null,
        "u.userable_id": "15646",
        "u.userable_type": "App\\Models\\Driver",
        "u.country_id": null,
        "u.created_at": "2023-07-28 16:14:53",
        "u.updated_at": "2023-08-31 02:24:06",
        "u.stripe_id": "cus_OY6laXlSQmCzez",
        "u.pm_type": null,
        "u.pm_last_four": null,
        "u.trial_ends_at": null,
        "u.status": "Activo",
        "plate": "uvw123",
        "foto_conductor": null,
        "m3": "6",
        "burden": "0",
        "nombre": "Sedan",
        "vehicle_id": "20",
        "foto_camion": null
        */
        $drivers = DB::select("SELECT u.email, u.direccion_ip, u.userable_id, u.userable_type, u.country_id,
        u.created_at, u.updated_at, u.stripe_id, u.pm_type, u.pm_last_four, u.trial_ends_at,
        u.status, v.plate, D.url_foto_perfil as foto_conductor, v.m3, v.burden, t.nombre, v.id, v.id as vehicle_id
            FROM users AS u
            JOIN drivers AS D ON u.userable_id = D.id 
            JOIN driver_vehicles AS v ON v.driver_id = u.userable_id 
            JOIN types_transports AS t ON v.types_transport_id = t.id 
            WHERE u.userable_type = ?
            AND v.types_transport_id = ?
            AND NOT EXISTS (
                SELECT * FROM driver_services AS cs     
                WHERE cs.driver_id = u.userable_id 
                AND DATE(cs.created_at) = ?
                AND cs.status IN('Pendiente','En Curso', 'Reservado', 'Agendado')
            )", [Driver::class, $data["idcamion"], $data['fecha_reserva']]);
        foreach ($drivers as $key => $driver) {
            $camionImage = Image::where([
                "imageable_id" => $driver->vehicle_id,
                "imageable_type" => DriverVehicle::class,
                "is" => "photo_url_vehicle"
            ])->first();
        
            $drivers[$key]->foto_camion = empty($camionImage) ? null : $camionImage->url;
        }
        return $drivers;
        //foto_camion
        //foto_conductor
    }
    public function getAvailableDrivers2(){
        $data = request()->all();
        //$data['fecha_reserva'] = explode(' ', $data['fecha_reserva']);
        //$data['fecha_reserva'] = $data['fecha_reserva'][0];
        $data["idcamion"] = $data["idcamion"] == 8 ? 6 : $data["idcamion"];
        
       //SELECT S.estado, cs.* FROM driver_services AS cs left join services as S on S.id = cs.service_id WHERE cs.status IN('Pendiente','En Curso', 'Reservado', 'Agendado');
        $drivers = DB::select("SELECT u.id, u.email, u.direccion_ip, u.userable_id, u.userable_type, u.country_id,
        u.created_at, u.updated_at, u.stripe_id, u.pm_type, u.pm_last_four, u.trial_ends_at,
        u.status, v.plate, D.url_foto_perfil as foto_conductor, v.m3, v.burden, t.nombre, v.id as vehicle_id
            FROM users AS u
            JOIN drivers AS D ON u.userable_id = D.id 
            JOIN driver_vehicles AS v ON v.driver_id = u.userable_id 
            JOIN types_transports AS t ON v.types_transport_id = t.id 
            WHERE u.userable_type = ?
            AND v.types_transport_id = ?
            AND u.status = 'Activo'
            AND NOT EXISTS (
                SELECT * FROM driver_services AS cs     
                WHERE cs.driver_id = u.userable_id 
                AND cs.status IN('Pendiente','En Curso', 'Reservado', 'Agendado')
            )", [Driver::class, $data["idcamion"]]);
        foreach ($drivers as $key => $driver) {
            $camionImage = Image::where([
                "imageable_id" => $driver->vehicle_id,
                "imageable_type" => DriverVehicle::class,
                "is" => "photo_url_vehicle"
            ])->first();
        
            $drivers[$key]->foto_camion = empty($camionImage) ? null : $camionImage->url;
        }
        return ["data" => $drivers];
        //foto_camion
        //foto_conductor
    }
    private function gethistorialserviciosbyconductor($driverId){

    }
    private function setPagosServicios($driverId){

    }
    private function gethistorialpagosbyconductor($driverId){

    }
    private function makePartialTransaction($transactionId, $driverId){
        $totalBalance = calculateDriverBalance($driverId, DB::table("transactions"));
        if($totalBalance > 0){
            $oldBalanceTId = K_HelpersV1::getInstance()->getBalanceTransactionId($transactionId);
            if(!empty($oldBalanceTId)){
                Transaction::where("id", $oldBalanceTId)->update([
                    "status" => "succeeded"
                ]);
                K_HelpersV1::getInstance()->refreshBalance($transactionId, "succeeded");
            }else{
                Transaction::where("id", $transactionId)->update([
                    "status" => "succeeded"
                ]);
                K_HelpersV1::getInstance()->refreshBalance($transactionId, "succeeded");
            }

            $totalBalance = calculateDriverBalance($driverId, DB::table("transactions"));
            if(round($totalBalance, 2) < Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision){
                //activate driver
                //User::where("id", $driverId)->update(["status" => "Bloqueado"]);
                //K_HelpersV1::getInstance()->disableDriver($driverId);
            }
            return ["error"=>false];
        }
        return [];
    }
    private function getconduresdisponiblesempresas($driverId){

    }
    private function getconduresdisponibles($fecha_reserva, $idcamion){
        $fecha_reserva = explode(' ', $fecha_reserva);
        $fecha_reserva = $fecha_reserva[0];
        try {
            $Activedrivers = DriverService::whereIn("status", ['Pendiente','En Curso', 'Reservado', 'Agendado'])->get()->pluck("driver_id");

            dd($Activedrivers);
            if(K_HelpersV1::ENABLE){
                $idcamion = $idcamion == 8 ? 6 : $idcamion;
                $drivers = DriverVehicle::where("types_transport_id", $idcamion)
                ->join("drivers as D", "driver_vehicles.driver_id", "=", "D.id")
                ->select(["D.nombres as nombre", "D.apellidos", "D.id"])
                ->whereNotIn("D.id", $Activedrivers)
                ->get();
                foreach ($drivers as $key => $driver) {
                    $drivers[$key]->idusuarios = User::where([
                        ["userable_id", "=", $driver->id],
                        ["userable_type", "=", Driver::class],
                    ])->first()->id;
                }
                return ($drivers);

            }
            
        } catch (\Throwable $th) {
            $er = array('text'=>'Error obtener los conductores', 'error'=>$th->getMessage());
            return ($er);
        }
    }

}
