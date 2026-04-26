<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Http\Controllers\Controller;
use App\Mail\RequestTransaction;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserPayoutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() // balance_info
    {
        //
        $user = request()->user();
        $driver = Driver::find($user->userable_id);
        //$comision = DB::select('SELECT configurations.comision from configurations where id=1')[0]->comision; //Obtener Comision de kamgus
        $comision = Configuration::find(Configuration::COMISION_KAMGUS)->comision; //Obtener el Impuesto del país
        $kfees = $comision/100.0;
        //if (K_HelpersV1::ENABLE) {
        //    $response = array('error' => false, 'msg' => 'cargando servicios', 'transactions'=> K_HelpersV1::getInstance()->getDriverTransactions($user->id));
        //    return response()->json( $response , self::HTTP_OK );
        //}
        $services = DB::table("services as S")
			->leftJoin("driver_services as DS", "DS.service_id", "=", "S.id")
            //->whereRaw("S.id in (SELECT DS.service_id from driver_services as DS where DS.driver_id = ?)", $driver->id)
            //->where([["S.estado", "=", "Terminado"]])
            ->where([
                ["DS.driver_id", "=", $driver->id], 
                ["S.estado", "=", "Terminado"],
                ["DS.status", "=", "Terminado"],
            ])->whereIn("S.pago", [
                "pendiente", 
            //	"transferido"
            ])
            ->whereIn("S.tipo_pago", [
                "Card", 
                "Yappy", 
                //"PagoCash"
                "transferencia",
                "Efectivo",
            ])
            ->orderBy("S.created_at", "DESC")
            ->select([
                "S.id as id",
                DB::raw("sha1(concat('SERVICE', '_', S.id)) as hcode"),
                DB::raw("(S.precio_real * '$kfees') AS comision"),
                DB::raw("(S.precio_real) - (S.precio_real * '$kfees') AS valor"),
                DB::raw("0.0 as tax"),
                "S.tipo_pago AS tipo_pago",
                DB::raw("DS.status as estadoF"),
                DB::raw("DS.endTime as endTime"),
                DB::raw("DS.status as cestado"),
                DB::raw("if(S.estado = 'terminado', 'Yes','No') as ispagado"),
                "S.created_at AS created_at",
                "S.tipo_pago as gateway",
                DB::raw("'Conductor' as role"),
            ])
            //->get()
            ; 
        //$response = array('error' => false, 'msg' => 'cargando servicios', 'transactions'=> $services);
        //return response()->json( $response , self::HTTP_OK );
        $balance = DB::table("transactions")
			->leftJoin("users", "users.id", "=", "transactions.user_id")
			->where([
                ["transactions.user_id", "=", $user->id], 
            ])
            ->whereIn("status", Transaction::SUCCESS_STATES)
            ->whereNull("transactions.service_id")
            ->orderBy("transactions.created_at", "DESC")
			->select([
                "transactions.id as id",
                DB::raw("sha1(concat('TRANSACTION', '_', transactions.id)) as hcode"),

                DB::raw("(transactions.tax) as comision"),
                DB::raw("(transactions.amount - (transactions.amount * $kfees)) as total"),
                "transactions.amount as valor",
                //"transactions.tax as valor",
                DB::raw("if(transactions.amount < 0, 'Retiro', 'Deposito')  as tipo_pago"),
                "transactions.status as estadoF",
                'transactions.created_at AS endTime',
                'transactions.status AS cestado',
                DB::raw('if(transactions.status in ("succeeded", "complete"), "Yes", "No") AS ispagado'),
                'transactions.created_at AS created_at',
                "transactions.gateway as gateway",
                "users.id as user_id",
            ])
            ->union($services)
            //->orderBy(`created_at`, "DESC")
            //->get()
            ;
        $balance = DB::query()->fromSub($balance, "balance")->orderBy("balance.created_at", "DESC")->get();
        foreach ($balance as $key => $value) {
            if (!empty($value->user_id)) {
                $role = DB::table("model_has_roles")->where([
                    ["model_id", "=", $value->user_id],
                    ["model_type", "=", User::class],
                ]);
                if($role->count() > 0){
                    $balance[$key]->role = DB::table("roles")->where("id", $role->first()->role_id)->first()->name;
                }
            }
        }
        $response = array('error' => false, 'msg' => 'cargando servicios', 'transactions'=> 
            //array_merge(
                //$services->toArray(), 
                $balance->toArray()
            //)
        );
        return response()->json( $response , self::HTTP_OK );
    }
    public function create() //stripeCustomer
    {
       
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
    public function show($id) //getDriverBalance, getPaymentHistoryDriverById
    {
        //
        $user = request()->user();
        //$user = User::find(2869);
        //$user = User::find(2556);
        $driver = Driver::where([
            ["id", "=", $user->userable_id],
        ]);
        switch ($id) {
            case 'balance':
                $balance = calculateDriverBalance($user->id, DB::table("transactions"));
                if (!empty(request()->update) && ($user->status == 3 || $user->status == "Bloqueado") && (round($balance, 2) >= Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision) ) {
                    $user->status = "Activo";
                    $user->save();
                    K_HelpersV1::getInstance()->enableDriver($user->id);
                }
                $response = array('error' => false, 'msg' => 'Cargando balance de cuenta', 'data'=> [
                    "balance" => $balance,
                    //"balanceD" => [$tServiceCard , $query->row()->balance, $feeServiceCash],
                ]);
                return response()->json( $response , self::HTTP_OK );
                break;
            case 'history':
                //dd($user);
                $query = DB::table("driver_services as DS")
                    ->join("drivers", "DS.driver_id", "=", "drivers.id")
                    ->join("services as S", "DS.service_id", "=", "S.id")
                    ->join("users as U", "S.user_id", "=", "U.id")
                    ->join("customers as C", "U.userable_id", "=", "C.id")
                    ->join("driver_vehicles as DV", "DV.driver_id", "=", "drivers.id")
                    ->leftJoin("models as Mo", "Mo.id", "=", "DV.model_id")
                    ->leftJoin("routes as R", "R.service_id", "=", "S.id")
                    ->whereIn("S.estado", ["ACTIVO","AGENDADO","INACTIVO","PENDIENTE","RESERVA","PROGRAMAR","REPETIR"])
                    ->whereIn("DS.status", ["EN CURSO", "PENDIENTE", "AGENDADO", "TERMINADO"])
                    ->where("DS.driver_id", $driver->id)
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
                        DB::raw("if(S.estado = 'AGENDADO', 'Agendado', S.estado) AS servicios_estado"),
				        "S.id as servicio_id",
                        DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"}","]") as coordenas'),
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
    public function update(Request $request, $id) //requestPartialTransaction
    {
        $user = request()->user();
        $driver = Driver::find($user->userable_id);
        switch ($id) {
            case 'take_out':
                $userId = $user->id;
                $value = $request->amount;
                $totalBalance = calculateDriverBalance($user->id, DB::table("transactions"));
                if($totalBalance > 0){
                    if($value <= $totalBalance){
                
                        $charge = Transaction::create([
                            "user_id" => $user->id,
                            "service_id" => null,
                            "type" => "0",
                            "amount" => -1 * $value,
                            "currency" => "USD",
                            "transaction_id" => "",
                            "status" => "preordered",
                            "gateway" => null,
                            "receipt_url" => "",
                        ]);
                        $chargeId = $charge->id;
                        K_HelpersV1::getInstance()->requestPartialTransaction($user->id, $value, $chargeId);
                        //Notificar via email al administrador
                        $administrators = User::role('Administrador');
                        if( $administrators->count() > 0 ){
                            foreach ($administrators->cursor() as $key => $admin) {
                                $email = $admin->email;//Email del usuario administrador
                            
                                //Notificar via email la solicitud al administrador
                                //SendinBlueEmail::getInstance()->sendEmailWithCurl($email, $template, "Solicitud de transacción");
                                Mail::to($admin)->send(new RequestTransaction($value, $driver->nombres." ".$driver->apellidos, $chargeId));
                            }
                        }
                        //Notificar via email la solicitud
                        //SendinBlueEmail::getInstance()->sendEmailWithCurl("info@kamgus.com", $template, "Solicitud de transacción");
                        Mail::to("info@kamgus.com")->send(new RequestTransaction($value, $driver->nombres." ".$driver->apellidos, $chargeId));
                        //SendinBlueEmail::getInstance()->sendEmailWithCurl($user->email, $template, "Solicitud de transacción");
                        Mail::to($user->email)->send(new RequestTransaction($value, $driver->nombres." ".$driver->apellidos, $chargeId));
                        $response = array('error' => false, 'msg' => 'Transacción iniciada' );
                        return response()->json( $response , self::HTTP_NOT_FOUND );	
                        //return;
                    }
                    $response = array('error' => true, 'msg' => 'Balance no disponible' );
                    return response()->json( $response , self::HTTP_NOT_FOUND );	
                }
                $response = array('error' => true, 'msg' => 'Fondos insuficientes. Balance: '.($totalBalance < 0 ? "-$".abs($totalBalance): "$".$totalBalance)." " );
                return response()->json( $response , self::HTTP_NOT_FOUND );	
                break;
            
            case 'balance':
                $balance = calculateDriverBalance($user->id, DB::table("transactions"));
                if (!empty(request()->update) && ($user->status == 3 || $user->status == "Bloqueado") && (round($balance, 2) >= Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision) ) {
                    $user->status = "Activo";
                    $user->save();
                    K_HelpersV1::getInstance()->enableDriver($user->id);
                }
                $response = array('error' => false, 'msg' => 'Cargando balance de cuenta', 'data'=> [
                    "balance" => $balance,
                    //"balanceD" => [$tServiceCard , $query->row()->balance, $feeServiceCash],
                ]);
                return response()->json( $response , self::HTTP_OK );
                break;
            default:
                # code...
                break;
        }
    }
//
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
