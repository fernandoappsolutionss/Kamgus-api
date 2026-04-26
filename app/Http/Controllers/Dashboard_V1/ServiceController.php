<?php

namespace App\Http\Controllers\Dashboard_V1;

use App\Classes\K_HelpersV1;
use App\Mail\RetiroFinalizadoMail;
use App\Http\Controllers\Controller;
use App\Constants\Constant;
use App\Http\Controllers\V2\Customers\ServiceController as CustomersServiceController;
use App\Http\Controllers\V2\Drivers\ServiceController as DriverServiceController;
use App\Http\Resources\Admin\AdminServiceResource;
use App\Http\Resources\Admin\AdminServiceCollection;
use App\Http\Resources\Admin\UserCollection;
use App\Http\Resources\Customers\ServiceResource;
use App\Http\Resources\Customers\ServiceCollection;
use App\Http\Resources\Drivers\DriverCollection;
use App\Http\Resources\Drivers\DriverTokenCollection;
use App\Mail\StatusService;
use App\Models\ArticleService;
use App\Models\Configuration;
use App\Models\CustomArticle;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\FcmToken;
use App\Models\Route;
use App\Models\Service;
use App\Models\Serviceable;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\K_SendPushNotification;
use App\Notifications\SendPushNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use GGInnovative\Larafirebase\Facades\Larafirebase;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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
    private $convertSToTT = [
        'PANEL' => "Panel",
        'PICK UP' => "Pick up",
        'CAMIÓN PEQUEÑO' => "Camión Pequeño",
        'CAMIÓN GRANDE' => "Camión Grande",
        'MOTO' => "Moto",
        'SEDAN' => "Sedan",
    ];
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
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function servicesxarticles(Request $request)
    {
    }
    public function store(Request $request, $id)
    {
        switch ($id) {
            case 'gethistorialserviciosbyconductor':
                //{ "idconductor": idusuario },
                $driverId = request()->idconductor;
                return ($this->gethistorialserviciosbyconductor($driverId));
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
            case 'notify_takeout': //PUT
                //id,
                //id_usuario
                $driverId = request()->driver_id;
                $transactionId = request()->id;

                return response()->json($this->notify_takeout($transactionId, $driverId));
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
                # code...
                break;
        }
    }
    public function store2(Request $request, $id)
    {
        switch ($id) {
            case 'gethistorialserviciosbyconductor':
                //{ "idconductor": idusuario },
                $driverId = User::find(request()->idconductor)->userable_id;
                return ($this->gethistorialserviciosbyconductor($driverId));
                break;
            case 'setPagosServicios':
                //{ "idservicios": ids, "conductor_id": idusuario, "tabActive": tabActive },
                $driverId = request()->conductor_id;
                return response()->json($this->setPagosServicios($driverId));
                break;
            case 'gethistorialpagosbyconductor':
                //"idconductor": idusuario
                $driverId = request()->idconductor;
                return response()->json($this->gethistorialpagosbyconductor($driverId));
                break;
            case 'makePartialTransaction':
                $transactionId = request()->id;
                $driverId = request()->id_usuario;
                return response()->json($this->makePartialTransaction($transactionId, $driverId));
                break;
            case 'notify_takeout': //PUT
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
            case 'gethistorialpagosbyconductor_test':
                /*
                return DB::select("SELECT SUM(s.precio_real) as valor 
                FROM driver_services AS DS
                JOIN services AS s ON DS.service_id = s.id
                WHERE DS.driver_id = :idconductor
                AND DS.ispaid IN ('Pendiente')
                AND DS.status IN ('Terminado')
                AND s.tipo_pago = 'Efectivo'
                AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                group by DS.driver_id
                LIMIT 1
                ");
                */
                DB::select("SELECT  
                MAX(s.precio_real) AS valor,
                s.tipo_pago AS tipo_pago,
                any_value(u.nombres) as nombres, 
                any_value(u.apellidos) as apellidos, 
                any_value(DS.status) AS estadoF,
                any_value(DS.endTime) AS endTime,
                any_value(DS.status) AS cestado,
                any_value(DS.ispaid) AS ispagado,
                                (
                                    SELECT SUM(s.precio_real) as valor 
                                    FROM driver_services AS DS
                                    JOIN services AS s ON DS.service_id = s.id
                                    WHERE DS.driver_id = :idconductor1
                                    AND DS.ispaid IN ('Pendiente')
                                    AND DS.status IN ('Terminado')
                                    AND s.tipo_pago = 'Efectivo'
                                    AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                                    group by DS.driver_id
                                    LIMIT 1
                                ) AS totalSemanalEfectivo,
                                (
                                    SELECT SUM(s.precio_real) as valor 
                                    FROM driver_services AS DS
                                    JOIN services AS s ON DS.service_id = s.id
                                    WHERE DS.driver_id = :idconductor2
                                    AND DS.ispaid IN ('Pendiente')
                                    AND DS.status IN ('Terminado')
                                    AND (s.tipo_pago = 'Tarjeta Crédito' OR s.tipo_pago = 'Yappy' OR s.tipo_pago = 'pago_cash' OR s.tipo_pago = 'Transferencia')
                                    AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                                    group by DS.driver_id
                                    LIMIT 1
                                ) AS totalSemanalCredito,
                                (
                                    SELECT SUM(s.precio_real) as valor
                                    FROM driver_services AS DS
                                    JOIN services AS s ON DS.service_id = s.id
                                    WHERE DS.driver_id = :idconductor3
                                    AND DS.ispaid IN ('Pendiente')
                                    AND DS.status IN ('Rechazado')
                                    AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                                    group by DS.driver_id
                                    LIMIT 1 
                                ) AS totalRechazadoSemanal,
                                (SELECT sum(T.amount + T.tax) as valor from transactions as T WHERE user_id = :user_id and status = 'preordered') 
                                AS balance
                               
                            FROM driver_services AS DS
                            JOIN services AS s ON DS.service_id = s.id
                            JOIN drivers AS u ON DS.driver_id = u.id
                            WHERE DS.driver_id = :idconductor4
                            AND DS.ispaid IN ('Pendiente')
                            AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                            group by s.id
                            ORDER BY endTime DESC", [
                                "idconductor1" => 15679, 
                                "idconductor2" => 15679, 
                                "idconductor3" => 15679, 
                                "idconductor4" => 15679, 
                                "user_id" => 2112,
                            ])[0];
                            /*
                            [
  "idconductor" => 15679
  "user_id" => 2112
]
                            */
                break;

            default:
                # code...
                break;
        }
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
    public function show2($id)
    {
        return $this->getopcionesNV3($id);
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
        switch ($id) {
            case 'cancel':
                return $this->cancel_service($request);
                break;
            case 'value':
                return $this->updateServiceValue($request);
                break;
            case 'driver':
                /*			$sql = $db->prepare("INSERT INTO conductor_servicios (servicios_id, conductores_id, vehiculos_id, startTime, endTime, estado, role, fecha_reserva, ispagado, creado)

                VALUES($servicio, $conductor, $vehiculo, NOW(), NOW(), 'Agendado', 'CONDUCTOR', '$fecha', 'Pendiente', NOW())"); 

                and update service estado a Agendado
                */
                $serviceId = $request->service_id;
                $vehicleId = $request->vehicle_id;
                $driverId = $request->driver_id;
                $driverUser = User::find($driverId);
                $service = Service::find($serviceId);
                $service->driver_id = $driverId;
                $service->save();
                $driverService = DriverService::firstOrNew([
                    "service_id" => $serviceId,
                    "driver_id" => $driverUser->userable_id,
                ]);
                //K_HelpersV1::getInstance()->acceptService($request->all(), $user->id, $driverTimeV);
                $estado = "Agendado";
                $driverService->startTime = date("Y-m-d H:i:s");
                $driverService->endTime = date("Y-m-d H:i:s");
                $driverService->driver_id = $driverUser->userable_id;
                $driverService->status = $estado;
                $driverService->confirmed = "No";
                $driverService->reservation_date = date("Y-m-d H:i:s");
                $driverService->observation = "";
                $driverService->suggested_price = $service->precio_real;
                $driverService->ispaid = "Pendiente";
                $driverService->commission = "";
                $driverService->save();
                $customerUser = User::find(Service::find($serviceId)->user_id);
                $fcmToken = $customerUser->fcmtokens->first();
               
                DB::table("service_statuses")->insertGetId([
                    "service_id" => $serviceId,
                    "status" => 'Estado de servicio',
                    "it_was_read" => 0,
                    "description" => "Un conductor tiene una oferta para el servicio.",
                    "servicestatetable_id" => $customerUser->userable_id,
                    "servicestatetable_type" => Customer::class,
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
                if(!empty($fcmToken)){
                    $fcmToken = $fcmToken->token;
                    $fcmData = DriverServiceController::getDataOfNewService(
                        $serviceId,
                        $customerUser->userable_id,
                        $service->precio_sugerido,
                        //(time() + $driverTimeV) * 1000
                        (time() + 360) * 1000
                    );
                    $customerUser->notify(new K_SendPushNotification('Estado de servicio', "Un conductor tiene una oferta para el servicio.", $fcmToken, $fcmData));
                }
                Mail::to($customerUser)->send(new StatusService('Estado de servicio', "Un conductor tiene una oferta para el servicio.", $estado));
                return response()->json(["error" => false, "msg" => "Conductor asignado"]);
                break;
            case 'camion':
                $serviceId = $request->service_id;
                $tipoCamionId = $request->tipo_camion_id;
                $ttype = is_numeric($tipoCamionId) ? $this->transportType[$tipoCamionId] : $tipoCamionId;
                $service = Service::find($serviceId);
                $service->tipo_transporte = $ttype;
                $service->save();
                return response()->json(["error" => false, "msg" => "Tipo de camión cambiado"]);
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

    /**
     * returns all the services active for the auth user
     * @return \App\Models\Service
     * 
     */


    public function cancel_service(Request $request)
    {

        $rules = [
            'service_id' => 'required'
        ];

        $this->validate($request, $rules);

        $service = Service::findOrFail($request->service_id);
        $service->estado = 'ANULADO';

        if ($service->save()) {
            return response()->json(['msg' => Constant::CANCEL_SERVICE]);
        }
    }
    private function updateServiceValue($request)
    {
        $rules = [
            'service_id' => 'required'
        ];

        $this->validate($request, $rules);

        $service = Service::findOrFail($request->service_id);
        $service->precio_real = $request->value;

        if ($service->save()) {
            return response()->json(['msg' => Constant::CANCEL_SERVICE]);
        }
    }
    private function gethistorialserviciosbyconductor($driverId)
    {
        $servicesByDriver = DB::table("driver_services as DS")
            ->join("services as S", "DS.service_id", "=", "S.id")
            ->join("drivers as D", "DS.driver_id", "=", "D.id")
            ->leftJoin("routes as R", "S.id", "=", "R.service_id")
            ->where("DS.driver_id", $driverId)
            ->select([
                "S.*",
                "D.nombres",
                "D.apellidos",
                "R.*",
                "S.tipo_servicio as tipo_translado",
                "S.precio_real as valor",
                "S.created_at",
                "R.primer_punto as inicio_punto",
                "R.segundo_punto as punto_final",
                DB::raw('CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"}","]") as coordenas'),
            ])

            ->orderBy("S.created_at", "DESC");
        $html = '<div class="row detallesServicios">
            <div class="row headertab">
                <div class="col-2">
                    <b>Cliente</b> 
                </div>
                <div class="col-2">
                    <b>Dirección Inicio</b> 
                </div>
                <div class="col-2">
                    <b>Dirección Final</b>
                </div>
                <div class="col-2">
                    <b>Estado</b> 
                </div>
                <div class="col-1">
                    <b>Tipo Acarreo</b>
                </div>
                <div class="col-1">
                    <b>Valor</b>
                </div>
                <div class="col-2">
                    <b>Creado</b>
                </div>
            </div>';
        $class = 'mintcream';
        foreach ($servicesByDriver->cursor() as $key => $value) {
            $html .= '
                        <div class="row listitem ' . $class . '">
                            <div class="col-2">
                                ' . $value->nombres . ' ' . $value->apellidos . '
                            </div>
                            <div class="col-2">
                                ' . $value->inicio_punto . '
                            </div>
                            <div class="col-2">
                                ' . $value->punto_final . '
                            </div>
                            <div class="col-2">
                                ' . $value->estado . '
                            </div>
                            <div class="col-1">
                                ' . $value->tipo_translado . '
                            </div>
                            <div class="col-1">
                                USD $' . $value->valor . '
                            </div>
                            <div class="col-2">
                                ' . $value->created_at . '
                            </div>
                        </div>';
            $class = ($class == 'mintcream') ? 'lavender' : 'mintcream';
        }
        $html .= '</div>';
        return (array('html' => $html));
    }
    private function setPagosServicios($driverId)
    {
        $tabActive = request()->tabActive;
        $serviceIds = request()->idservicios;
        $conductorId = $driverId;

        if ($tabActive == 'acumulado') {
            if ($serviceIds != 0) {
                $ids = [];
                foreach ($serviceIds as $key => $value) {
                    $ids[] = intval($value);
                }
                //dd(User::find($conductorId)->userable_id);
                $ds = DriverService::where("driver_id", User::find($conductorId)->userable_id)
                    ->whereIn("service_id", $ids);
                $ds->update(["ispaid" => 'Pagado']);

                K_HelpersV1::getInstance()->updateDriverServicePaymentByIds($conductorId, $serviceIds);
            } else {
                $ds = DriverService::where("driver_id", User::find($conductorId)->userable_id)
                    ->whereRaw("WEEK(endTime) < (WEEK(CURRENT_DATE()) - 1)")
                    ->whereIn("ispaid", ['Pendiente']);
                $ds->update(["ispaid" => 'Pagado']);
                K_HelpersV1::getInstance()->updateDriverServicePayment($conductorId);
            }
        } else if ($tabActive == 'semanal') {
            $ds = DriverService::where("driver_id", User::find($conductorId)->userable_id)
                ->whereRaw("WEEK(endTime) BETWEEN (WEEK(CURRENT_DATE()) - 1) AND WEEK(CURRENT_DATE())")
                ->whereIn("ispaid", ['Pendiente']);
            $ds->update(["ispaid" => 'Pagado']);
            K_HelpersV1::getInstance()->updateDriverServicePayment($conductorId, 'semanal');
        }
        return (true);
    }
    private function gethistorialpagosbyconductor($driverId)
    {
        $driver = User::role('Conductor')->where("id", $driverId)->first();
        $config = DB::select('SELECT configurations.comision, balance_minimo from (
			select max(if(configurations.id=1, comision, 0)) as comision, max(if(configurations.id=4, comision, 0)) as balance_minimo
			from configurations ) as configurations')[0];
        
        $total1 = DB::table("driver_services as DS")
        ->join("services as s", "DS.service_id", "=", "s.id")
        ->where([
            ["DS.driver_id", "=",  $driver->userable_id],
            ["s.tipo_pago", "=", 'Efectivo'],
        ])
        ->whereIn("DS.ispaid", ['Pendiente'])
        ->whereIn("DS.status", ['Terminado'])
        ->groupBy("TP")
        ->groupBy("s.tipo_pago")
        ->select([
            DB::raw("SUM(s.precio_real) AS valor"),
            DB::raw("s.tipo_pago AS tipo_pago"),
            DB::raw("s.tipo_pago AS TP"),
            DB::raw("MAX(DS.endTime) AS endTime"),
            DB::raw('\'CONDUCTOR\' AS role'),
            DB::raw('\'Pendiente\' AS ispagado'),
            DB::raw('\'Terminado\' AS cestado'),
        ])->first();
        
        $total2 = DB::table("driver_services as DS")
        ->join("services as s", "DS.service_id", "=", "s.id")
        ->where([
            ["DS.driver_id", "=",  $driver->userable_id],
        ])
        ->whereIn("DS.ispaid", ['Pendiente'])
        ->whereIn("DS.status", ['Terminado'])
        ->whereIn("s.tipo_pago", ["card", 'Tarjeta Crédito', 'Yappy', 'pago_cash', 'Transferencia'])
        ->groupBy("TP")
        ->groupBy("s.tipo_pago")
        ->select([
            DB::raw("SUM(s.precio_real) AS valor"),
            DB::raw("s.tipo_pago AS tipo_pago"),
            DB::raw("s.tipo_pago AS TP"),
            DB::raw("MAX(DS.endTime) AS endTime"),
            DB::raw('\'CONDUCTOR\' AS role'),
            DB::raw('\'Pendiente\' AS ispagado'),
            DB::raw('\'Terminado\' AS cestado'),
        ])->first();
      
        $total3 = DB::table("transactions as T")
        ->where([
            ["user_id", "=",  $driver->id],
            //[DB::raw("SUM(T.amount + T.tax)"), ">=",  0],
        ])
        ->whereRaw("(T.amount + T.tax) <  0")
        ->whereNull("service_id")
        ->select([
            DB::raw("SUM(T.amount + T.tax) AS valor"),
            DB::raw('\'Deposito\' AS tipo_pago'),
            DB::raw("MAX(T.created_at) AS endTime"),
            DB::raw('\'CONDUCTOR\' AS role'),
            DB::raw('\'pending\' AS ispagado'),
        ])->first();
        $total4 = DB::table("transactions as T")
        ->where([
            ["user_id", "=",  $driver->id],
            //[DB::raw("SUM(T.amount + T.tax)"), "<",  0],
        ])
        ->whereRaw("(T.amount + T.tax) <  0")       
        ->whereNull("service_id")
        ->select([
            DB::raw("SUM(T.amount + T.tax) AS valor"),
            DB::raw('\'Retiro\' AS tipo_pago'),
            DB::raw("MAX(T.created_at) AS endTime"),
            DB::raw('\'CONDUCTOR\' AS role'),
            DB::raw('\'T.status\' AS ispagado'),
        ])->first();
        $semanal = DB::select('SELECT 
            ABS(T.amount + T.tax) AS valor,
            IF((T.amount + T.tax) >= 0, \'Deposito\', \'Retiro\') AS tipo_pago,
            T.status AS estadoF,
            (T.created_at) AS endTime,
            T.status AS cestado,
            IF(
                T.status = "preordered",
                "Pendiente",
                T.status
            ) AS ispagado,
            \'CONDUCTOR\' AS role  
            FROM transactions as T
            WHERE service_id is null AND user_id = :idconductor', [":idconductor" => $driver->id,]);
        array_push($semanal, $total2);
        array_push($semanal, $total1);
        $totalValue1 =  empty($total1->valor) ? 0 : $total1->valor;
        $totalValue2 =  empty($total2->valor) ? 0 : $total2->valor;
        $totalValue3 =  empty($total3->valor) ? 0 : $total3->valor;
        $totalValue4 =  empty($total4->valor) ? 0 : $total4->valor;
        $comisionEfectivo = ($totalValue1 * $config->comision) / 100.0;
        $comisionTarjeta = ($totalValue2 * $config->comision) / 100.0;
        //$comisionRechazado = ($semanal[0]->totalRechazadoSemanal * $config->comision) / 100;
        $totalSemanalEfectivo = ($totalValue1 - $comisionEfectivo);
        $totalSemanalCredito = ($totalValue2 - $comisionTarjeta);
        //echo $sql->debugDumpParams();  
        //$totalSemanal = $totalSemanalCredito - ($comisionEfectivo + $comisionRechazado) + $semanal[0]->balance;
        //$totalSemanal = $totalSemanalCredito - ($comisionEfectivo) + $total3->valor - $totales[3]->valor;
        $totalSemanal = $totalSemanalCredito - ($comisionEfectivo) + $totalValue3 + $totalValue4;
        //dd(["idconductor" => intval($driver->userable_id), "user_id" => intval($driver->id)]);
        $acomulados = DB::select("SELECT  
                MAX(s.precio_real) AS valor,
                s.tipo_pago AS tipo_pago,
                any_value(u.nombres) as nombres, 
                any_value(u.apellidos) as apellidos, 
                any_value(DS.status) AS estadoF,
                any_value(DS.endTime) AS endTime,
                any_value(DS.status) AS cestado,
                any_value(DS.ispaid) AS ispagado,
                                (
                                    SELECT SUM(s.precio_real) as valor 
                                    FROM driver_services AS DS
                                    JOIN services AS s ON DS.service_id = s.id
                                    WHERE DS.driver_id = :idconductor1
                                    AND DS.ispaid IN ('Pendiente')
                                    AND DS.status IN ('Terminado')
                                    AND s.tipo_pago = 'Efectivo'
                                    AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                                    group by DS.driver_id
                                    LIMIT 1
                                ) AS totalSemanalEfectivo,
                                (
                                    SELECT SUM(s.precio_real) as valor 
                                    FROM driver_services AS DS
                                    JOIN services AS s ON DS.service_id = s.id
                                    WHERE DS.driver_id = :idconductor2
                                    AND DS.ispaid IN ('Pendiente')
                                    AND DS.status IN ('Terminado')
                                    AND (s.tipo_pago = 'Tarjeta Crédito' OR s.tipo_pago = 'Yappy' OR s.tipo_pago = 'pago_cash' OR s.tipo_pago = 'Transferencia')
                                    AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                                    group by DS.driver_id
                                    LIMIT 1
                                ) AS totalSemanalCredito,
                                (
                                    SELECT SUM(s.precio_real) as valor
                                    FROM driver_services AS DS
                                    JOIN services AS s ON DS.service_id = s.id
                                    WHERE DS.driver_id = :idconductor3
                                    AND DS.ispaid IN ('Pendiente')
                                    AND DS.status IN ('Rechazado')
                                    AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                                    group by DS.driver_id
                                    LIMIT 1 
                                ) AS totalRechazadoSemanal,
                                (SELECT sum(T.amount + T.tax) as valor from transactions as T WHERE user_id = :user_id and status = 'preordered') 
                                AS balance
                               
                            FROM driver_services AS DS
                            JOIN services AS s ON DS.service_id = s.id
                            JOIN drivers AS u ON DS.driver_id = u.id
                            WHERE DS.driver_id = :idconductor4
                            AND DS.ispaid IN ('Pendiente')
                            AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
                            group by s.id
                            ORDER BY endTime DESC", [
                                "idconductor1" => intval($driver->userable_id), 
                                "idconductor2" => intval($driver->userable_id), 
                                "idconductor3" => intval($driver->userable_id), 
                                "idconductor4" => intval($driver->userable_id), 
                                "user_id" => intval($driver->id),
                            ]);
                            //$acomulados = empty($acomulados) ? [((object)["totalSemanalEfectivo" => 0, "totalSemanalCredito" => 0, "totalRechazadoSemanal" => 0, "balance" => 0, ])] : $acomulados;
        $comisionEfectivoA = empty($acomulados) ? 0 : ($acomulados[0]->totalSemanalEfectivo * $config->comision) / 100;
        $comisionTarjetaA = empty($acomulados) ? 0 : ($acomulados[0]->totalSemanalCredito * $config->comision) / 100;
        $comisionRechazadoA = empty($acomulados) ? 0 : ($acomulados[0]->totalRechazadoSemanal * $config->comision) / 100;

        $totalSemanalEfectivoA = empty($acomulados) ? 0 : ($acomulados[0]->totalSemanalEfectivo - $comisionEfectivoA);
        $totalSemanalCreditoA = empty($acomulados) ? 0 : ($acomulados[0]->totalSemanalCredito - $comisionTarjetaA);
        //echo $sql->debugDumpParams();  
        //$totalSemanal = $totalSemanalCredito - ($comisionEfectivo + $comisionRechazado);        

        //$totalAcumulados = ($acomulados[0]->totalAcumulado - (($acomulados[0]->totalAcumulado * $config->comision) / 100) ); 
        $totalAcumulados =  $totalSemanalCreditoA - ($comisionEfectivoA + $comisionRechazadoA) + (empty($acomulados) ? 0 : $acomulados[0]->balance);
        $realizados = DB::select('SELECT DS.*, 
            S.id as idservicios,
            S.user_id as usuarios_id,
            S.precio_real as valor,
            S.fecha_reserva,
            S.tipo_servicio as tipo_translado,
            S.estado,
            S.created_at as creado,
            S.kilometraje as kms,
            S.tipo_pago,
            S.tiempo,
            S.tipo_transporte as id_tipo_camion,
            S.tipo_transporte as nombre_camion,
            S.descripcion as description,
            S.assistant as assistant,
            R.*,
            R.primer_punto as inicio_punto,
            R.segundo_punto as punto_final,
            CONCAT("[", "{\"coord_punto_inicio\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"},{\"coord_punto_final\":\"", R.latitud_primer_punto, ",", R.longitud_primer_punto, "\"}","]") as coordenas,
            DS.status AS estadoF, u.nombres as nombre, u.apellidos, DS.ispaid AS ispagado
                            FROM driver_services AS DS
                            JOIN services AS S ON DS.service_id = S.id
                            JOIN drivers AS u ON DS.driver_id = u.id
                            JOIN routes as R on R.service_id = S.id
                            WHERE DS.driver_id = :idconductor
                            AND S.estado IN (\'Terminado\',\'Anulado\')
                            AND DS.ispaid IN (\'Pagado\')
                            ORDER BY DS.endTime DESC', [":idconductor" => $driver->userable_id,]);

        $columns = '<div class="row headertab">
            <div class="col-1">
                <b>Cliente</b> 
            </div>
                                    
            <div class="col-1 itempago">
                <b>Tipo Pago</b>
            </div>
            <div class="col-1">
                <b>Valor Servicio</b>
            </div>
            <div class="col-1">
                <b>Comisión</b>
            </div>
            <div class="col-1">
                <b>Pago Conductor</b>
            </div>
            <div class="col-1">
                <b>Pagado</b>
            </div>
            <div class="col-2">
                <b>Fecha Terminado</b>
            </div>                       
        </div>';
        $html = '
            <ul class="nav nav-pills nav-fill" style="width: 80%;margin: 0 auto;">
                <li class="nav-item">
                    <a class="nav-link active btnSemanal" href="#semanal" role="tab" data-toggle="tab" data-total="$' . money_format('%i', $totalSemanal) . '">Pagos Semanal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btnAcumalado" href="#acomulados" role="tab" data-toggle="tab" data-total="$' . money_format('%i', $totalAcumulados) . '">Pagos Acumulados</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btnRealizado" href="#buzz" role="tab" data-toggle="tab" data-total="0">Pagos Realizados</a>
                </li>                  
            </ul>
            <div class="tab-content">';
        $html .= '  <div role="tabpanel" class="tab-pane in active" id="semanal">
            <div class="row detallesServicios">
                ' . str_replace("itempago", "", $columns) . '                         
                <div class="contentList">';
        $totalServicio = 0;
        $totalComision = 0;
        $totalConductor = 0;

        foreach (array_reverse($semanal) as $key => $value) {
            $value = $value;
            if(empty($value)){
                continue;
            }
            $totalServicio += $value->valor;

            $pagoAutorizado = 0;
            if (!empty($value->cestado) && ($value->cestado == 'Terminado' || $value->cestado == 'preordered' || ($value->cestado == 'Rechazado' && $value->role == 'USUARIO'))) {
                $pagoAutorizado = ($value->tipo_pago !== "Retiro" && $value->tipo_pago !== 'Deposito'  ? ($value->valor - (($value->valor * $config->comision) / 100)) : $value->valor);
                $totalComision += ($value->tipo_pago !== "Retiro" && $value->tipo_pago !== 'Deposito'  ? ($value->valor * $config->comision / 100) : 0);
                $totalConductor += ($value->tipo_pago !== "Retiro" && $value->tipo_pago !== 'Deposito'  ? ($value->valor - ($value->valor * $config->comision / 100)) : $value->valor);
            }

            $html .= '
                    <div class="row listitem mintcream" data-tid="' . (empty($value->id) ? "" : $value->id) . '" data-driver_id="' . $driverId . '">
                        <div class="col-1">
                            ' . $driver->nombres . ' ' . $driver->apellidos . '
                        </div>
                                             
                        <div class="col-1">
                            ' . $value->tipo_pago . '
                        </div>
                        <div class="col-1 valorItem">
                            USD $' . number_format($value->valor, 2) . '
                        </div>
                        <div class="col-1 valorItem">
                            USD $' . ($value->tipo_pago !== "Retiro" && $value->tipo_pago !== 'Deposito'  ? (($value->valor * $config->comision) / 100) : 0) . '
                        </div>
                        <div class="col-1 valorItem">
                            USD $' . number_format($pagoAutorizado, 2) . '
                        </div>
                        <div class="col-1">
                            ' . (($value->tipo_pago === "Retiro" && $value->ispagado != "succeeded") ?
                            "
                            <a class='authorizeTakeOut' href='#' onclick='doMakeOut(event)'>" . $value->ispagado . "</a>
                            " : $value->ispagado) . '
                        </div>
                        <div class="col-2">
                            ' . $value->endTime . '
                        </div>                        
                    </div>';
        }
        $html .= '
                <div class="row listitem mintcream">
                    <div class="col-6 text-right" style="line-height: 50px;">Total:</div>
                    <div class="col-1 valorItem">USD $' . $totalServicio . '</div>
                    <div class="col-1 valorItem">USD $' . $totalComision . '</div>
                    <div class="col-1 valorItem">USD $' . $totalConductor . '</div>
                </div>
        </div></div></div>';
        $html .= '  <div role="tabpanel" class="tab-pane in" id="acomulados">
                <div class="row detallesServicios">
                    ' . $columns . '
                    <div class="contentList listAcumlados">';
        $totalServicio = 0;
        $totalComision = 0;
        $totalConductor = 0;
        //dd($acomulados);
        foreach ($acomulados as $key => $value) {
            if(empty($value)){
                continue;
            }
            $totalServicio += $value->valor;
            $pagoAutorizado = 0;

            if (!empty($value->cestado) && ($value->cestado == 'Terminado' || $value->cestado == 'preordered' || ($value->cestado == 'Rechazado' /*&& $value->role == 'USUARIO'*/))) {
                $pagoAutorizado = ($value->valor - (($value->valor * $config->comision) / 100));
                $totalConductor += ($value->valor - ($value->valor * $config->comision / 100));
                $totalComision += ($value->valor * $config->comision / 100);
            }
            //<input type="checkbox" value="' . $value->servicios_id . '" name="selectAcumulados[]" class="radio_tarjeta" data-valor="' . ($value->valor - ($value->valor * $config->comision / 100)) . '">

            $html .= '
                <div class="row listitem mintcream opciones" data-tid="' . (empty($value->id) ? "" : $value->id) . '">
                    <div class="col-1">
                        ' . $driver->nombres . ' ' . $driver->apellidos . '
                        <input type="checkbox" value="' . $key . '" name="selectAcumulados[]" class="radio_tarjeta" data-valor="' . ($value->valor - ($value->valor * $config->comision / 100)) . '">
                    </div>
                                                   
                    <div class="col-1">
                        ' . $value->tipo_pago . '
                    </div>
                    <div class="col-1 valorItem">
                        USD $' . $value->valor . '
                    </div>
                    <div class="col-1 valorItem">
                        USD $' . ($value->valor * $config->comision / 100) . '
                    </div>
                    <div class="col-1 valorItem">
                        USD $' . $pagoAutorizado . '
                    </div>
                    <div class="col-1">
                        ' . $value->ispagado . '
                    </div>
                    <div class="col-2">
                        ' . $value->endTime . '
                    </div>                        
                </div>';
        }

        $html .= '
                <div class="row listitem mintcream">
                    <div class="col-6 text-right" style="line-height: 50px;">Total:</div>
                    <div class="col-1 valorItem">USD $' . $totalServicio . '</div>
                    <div class="col-1 valorItem">USD $' . $totalComision . '</div>
                    <div class="col-1 valorItem">USD $' . $totalConductor . '</div>
                </div>
        </div></div></div>';

        $html .= '<div role="tabpanel" class="tab-pane fade" id="buzz">
            <div class="row detallesServicios">
                ' . $columns . '                         
                <div class="contentList">';
        $totalServicio = 0;
        $totalComision = 0;
        $totalConductor = 0;
        //dd($realizados);
        foreach ($realizados as $key => $value) {
            if(empty($value)){
                continue;
            }
            $value = (object) $value;
            $totalServicio += $value->valor;
            $totalComision += ($value->valor * $config->comision / 100);
            $totalConductor += ($value->valor - ($value->valor * $config->comision / 100));

            $html .= '
                    <div class="row listitem mintcream">
                        <div class="col-1">
                            ' . $driver->nombres . ' ' . $driver->apellidos . '
                        </div>
                                             
                        <div class="col-1">
                            ' . $value->tipo_pago . '
                        </div>
                        <div class="col-1 valorItem">
                            USD $' . $value->valor . '
                        </div>
                        <div class="col-1 valorItem">
                            USD $' . ($value->valor * $config->comision / 100) . '
                        </div>
                        <div class="col-1 valorItem">
                            USD $' . ($value->valor - ($value->valor * $config->comision / 100)) . '
                        </div>
                        <div class="col-1">
                            ' . $value->ispagado . '
                        </div>
                        <div class="col-2">
                            ' . $value->endTime . '
                        </div>                        
                    </div>';
        }
        $html .= '
                <div class="row listitem mintcream">
                    <div class="col-6 text-right" style="line-height: 50px;">Total:</div>
                    <div class="col-1 valorItem">USD $' . $totalServicio . '</div>
                    <div class="col-1 valorItem">USD $' . $totalComision . '</div>
                    <div class="col-1 valorItem">USD $' . $totalConductor . '</div>
                </div>
        </div></div></div>';
        $html .= '</div>';
        return (array('html' => $html));
    }
    private function gethistorialpagosbyconductorempresa($driverId){
        $driver = User::role('Conductor')->where("id", $driverId)->first();
        $config = DB::select('SELECT configurations.comision, balance_minimo from (
			select max(if(configurations.id=1, comision, 0)) as comision, max(if(configurations.id=4, comision, 0)) as balance_minimo
			from configurations ) as configurations')[0];
        "SELECT MAX(s.precio_real) AS valor,
        s.tipo_pago AS tipo_pago,
        any_value(u.nombres) as nombres, 
        any_value(u.apellidos) as apellidos, 
        any_value(DS.status) AS estadoF,
        any_value(DS.endTime) AS endTime,
        any_value(DS.status) AS cestado,
        any_value(DS.ispaid) AS ispagado, (
            SELECT SUM(s.precio_real) as valor 
            FROM driver_services AS DS
            JOIN services AS s ON DS.service_id = s.id
            WHERE DS.driver_id = :idconductor1
            AND DS.ispaid IN ('Pendiente')
            AND DS.status IN ('Terminado')
            AND s.tipo_pago = 'Efectivo'
            AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
            group by DS.driver_id
            LIMIT 1
        ) AS totalSemanalEfectivo,
        (
            SELECT SUM(s.precio_real) as valor 
            FROM driver_services AS DS
            JOIN services AS s ON DS.service_id = s.id
            WHERE DS.driver_id = :idconductor2
            AND DS.ispaid IN ('Pendiente')
            AND DS.status IN ('Terminado')
            AND (s.tipo_pago = 'Tarjeta Crédito' OR s.tipo_pago = 'Yappy' OR s.tipo_pago = 'pago_cash' OR s.tipo_pago = 'Transferencia')
            AND WEEK(DS.endTime) < (WEEK(CURRENT_DATE()) - 1)
            group by DS.driver_id
            LIMIT 1
        ) AS totalSemanalCredito";
    }
    private function makePartialTransaction($transactionId, $driverId)
    {
        $totalBalance = calculateDriverBalance($driverId, DB::table("transactions"));
        if ($totalBalance > 0) {
            $oldBalanceTId = K_HelpersV1::getInstance()->getBalanceTransactionId($transactionId);
            if (!empty($oldBalanceTId)) {
                Transaction::where("id", $oldBalanceTId)->update([
                    "status" => "succeeded"
                ]);
                K_HelpersV1::getInstance()->refreshBalance($transactionId, "succeeded");
            } else {
                Transaction::where("id", $transactionId)->update([
                    "status" => "succeeded"
                ]);
                K_HelpersV1::getInstance()->refreshBalance($transactionId, "succeeded");
            }

            $totalBalance = calculateDriverBalance($driverId, DB::table("transactions"));
            if (round($totalBalance, 2) < Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision) {
                //activate driver
                //User::where("id", $driverId)->update(["status" => "Bloqueado"]);
                //K_HelpersV1::getInstance()->disableDriver($driverId);
            }
            return ["error" => false];
        }
        return [$totalBalance];
    }
    private function getconduresdisponiblesempresas($fecha_reserva, $idcamion, $idempresa)
    {
        $fecha_reserva = explode(' ', $fecha_reserva);
        $fecha_reserva = $fecha_reserva[0];
        /*
        $data = $request->getParsedBody();
        //$data = $data['requestForm']; 
        $data['fecha_reserva'] = explode(' ', $data['fecha_reserva']);
        $data['fecha_reserva'] = $data['fecha_reserva'][0];
        try {
            $db = new db();
            $db = $db->conectar();
            $sql = $db->prepare("SELECT u.*, v.placa, v.url_foto AS foto_camion, v.m3, v.carga, t.nombre_camion, u.url_foto AS foto_conductor, v.idvehiculos
                                FROM usuarios AS u
                                INNER JOIN vehiculos AS v ON v.conductores_id = u.idusuarios 
                                INNER JOIN tipo_camion AS t ON v.tipo_camion = t.id_tipo_camion 
                                JOIN empresa_conductor AS ec ON ec.id_usuario_conductor = u.idusuarios
                                WHERE u.rol = 1
                                AND ec.id_usuario_empresa = :idempresa
                                AND v.tipo_camion = :id_tipo_camion
                                AND NOT EXISTS (
                                    SELECT * FROM conductor_servicios AS cs     
                                    WHERE cs.conductores_id = u.idusuarios 
                                    AND DATE(cs.creado) = :fecha_reserva
                                    AND cs.estado IN('Pendiente','En Curso', 'Reservado', 'Agendado')
                                )");  
            $sql->bindParam(':fecha_reserva', $data['fecha_reserva'], PDO::PARAM_STR);
            $sql->bindParam(':id_tipo_camion', $data['idcamion'], PDO::PARAM_INT);
            $sql->bindParam(':idempresa', $data['idempresa'], PDO::PARAM_INT);
            $sql->execute();
            $conductores = $sql->fetchAll(PDO::FETCH_OBJ);
            ///return $sql->debugDumpParams();   
        
            return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($conductores));
            
        } catch(PDOException $e) {
            $er = array('text'=>'Error obtener los conductores', 'error'=>$e->getMessage());
            return $response->withStatus(404)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($er));
        }
        */
    }
    private function getconduresdisponibles($fecha_reserva, $idcamion){
        $fecha_reserva = explode(' ', $fecha_reserva);
        $fecha_reserva = $fecha_reserva[0];
        try {
            $Activedrivers = DriverService::whereIn("status", ['En Curso', 'Reservado', 'Agendado'])
                ->select("driver_id")
                ->groupBy("driver_id")
                ->get()
                ->pluck("driver_id");

            //dd($Activedrivers);
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
    private function notify_takeout($transactionId, $driverId)
    {
        //return $transactionId;
        $oldBalanceTId = K_HelpersV1::getInstance()->getBalanceTransactionId($transactionId);
        if (!empty($oldBalanceTId)) {
            $transactionId = $oldBalanceTId;
        }
        $transaction = Transaction::find($transactionId);
        if (empty($transaction)) {
            return null;
        }
        //Notify to driver and admin
        /*
        $sended1 = mail($driver->email, "Retiro aprobado", $template, $headers1); 
        $sended2 = mail("info@kamgus.com", "Retiro aprobado", $template, $headers1);
        */
        //dd(User::find($driverId)->email);
        $user = User::find($driverId);
        $mail = new RetiroFinalizadoMail([
            'driverName'    => trim($user->userable->nombres ?? ''),
            'amount'        => abs($transaction->amount),
            'transactionId' => (string) $transaction->id,
            'date'          => $transaction->updated_at->format('Y-m-d H:i:s'),
        ]);
        // Send to admin (info@kamgus.com); also notify driver if you want by uncommenting:
        // Mail::to($user->email)->send($mail);
        Mail::to('info@kamgus.com')->send($mail);
        return null;
    }

    private function getopcionesNV3($idLevel2){
        /*/**
        SELECT *
                            FROM articulos_acarreo_nivel_2
                            WHERE id_articulo_acarreo = :idNV1" */
        try {
            return K_HelpersV1::getInstance()->getopcionesNV3($idLevel2);
        } catch (\Throwable $th) {
            return response()->json([], self::HTTP_NOT_FOUND);
        }
        
    }
}
