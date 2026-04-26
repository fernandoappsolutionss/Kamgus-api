<?php

namespace App\Http\Controllers;

use App\Classes\StripeCustomClass;
use App\Constants\Constant;
use App\Events\CustomerServiceCancelled;
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
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SendPushNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use GGInnovative\Larafirebase\Facades\Larafirebase;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Arr;

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
            return new AdminServiceCollection(Service::whereIn('estado', ['ACTIVO', 'PENDIENTE', "RESERVA", "AGENDADO"])
                ->orderBy("services.id", "DESC")
                ->paginate(10));

        }else{
            
            return new ServiceCollection(Service::where(
                [ 
                    ['user_id', '=', $user->id], 
                    ['estado', '=', 'ACTIVO']
                ]
            )
            ->orderBy("services.id", "DESC")
            ->paginate(10));

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

            return new AdminServiceCollection(Service::whereIn('estado', ['PROGRAMAR', 'AGENDADO', 'RESERVA'])
                ->orderBy("services.id", "DESC")
                ->paginate(10));

        }else{

            return new ServiceCollection(Service::where(
                [ 
                    ['user_id', '=', $user->id], 
                    ['estado', '=', 'PROGRAMAR']
                ]
            )
            ->orderBy("services.id", "DESC")
            ->paginate(10));

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
            )
            ->orderBy("services.id", "DESC")
            ->paginate(10));

        }else{

            return new ServiceCollection(Service::where(
                [ 
                    ['user_id', '=', $user->id], 
                    ['estado', '!=', 'ACTIVO'],
                    ['estado', '!=', 'PROGRAMAR']
                ]
            )
            ->orderBy("services.id", "DESC")
            ->paginate(10));

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
                ->orderBy("services.created_at", "DESC")
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
                ->orderBy("services.created_at", "DESC")
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
            $driver = User::where([
                ['userable_id', '=', $service->driver_id],
                ['userable_type', '=', Driver::class],
            ])->first();
            CustomerServiceCancelled::dispatch($driver, $service->id, $service->user_id, $service->estado); //Dispara evento para notificar al usuario y conductor via email y push notification
            if(Transaction::where([["service_id", "=", $service->id]])->whereIn("status", Transaction::SUCCESS_STATES)->count() > 0){
                //Realizar reembolso
                $serviceTransaction = Transaction::where([["service_id", "=", $service->id]])->whereIn("status", Transaction::SUCCESS_STATES)->first();
                switch ($serviceTransaction->gateway) {
                    case 'stripe':
                        $servicioId = $service->id;
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
            return response()->json(['msg' => Constant::CANCEL_SERVICE]);
        }

    }

}
