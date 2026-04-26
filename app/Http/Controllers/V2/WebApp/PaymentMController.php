<?php
namespace App\Http\Controllers\V2\WebApp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleCollection;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\License;
use App\Models\Transaction;
use App\Models\User;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class PaymentMController extends Controller
{
    public function index(){
        $user = request()->user();
        if($user->can('listar pagos')){
            $offset = empty(request()->offset) ? 0 : request()->offset;
            $limit = empty(request()->limit) ? 10 : request()->limit;
            $text = empty(request()->search) ? "" : request()->search;
            $status = empty(request()->status) ? "preordered" : request()->status;

            $data = $this->addSearchQuery($limit, $offset, $status, $text);
            $data = $this->addBankInfo($data);
            $count = $this->getCount($limit, $offset, $status, $text);
          
            return response()->json([
                "data" => $data,
                "meta" => [
                    "total" => $count,
                ],
                "resum" => $this->calculateTotals()

            ]);
        }
        return response([
            "error" => true,
            "msg" => "No esta autorizado para ver el listado"
        ], self::HTTP_UNAUTHORIZED);
    }
    public function showCustomer($id){
        $user = request()->user();
        if($user->can('listar pagos')){
            $offset = empty(request()->offset) ? 0 : request()->offset;
            $limit = empty(request()->limit) ? 10 : request()->limit;
            $status = empty(request()->status) ? "preordered" : request()->status;
            $balance = 0;
            $types = "\"Deposito\", \"Retiro\"";
            if($status == "preordered"){
                $balance = calculateDriverBalance(User::where("users.userable_id", $id)->where("users.userable_type", Driver::class)->first()->id, DB::table("transactions"));
                $types = "\"Retiro\"";
            }
            $data = DB::select("SELECT DP.driver_id, identifier, DP.id, ttype, if(DP.ttype in ('Efectivo', 'Card'), C.nombres, D.nombres) as nombres
            , if(DP.ttype in ('Efectivo', 'Card'), C.apellidos, D.apellidos) as apellidos,
            envio, fee, (? + envio) as total, DP.fecha_reserva, DP.status
            FROM `driver_payouts` as DP 
            left join drivers as D on D.id = DP.driver_id 
            left join services as S on S.id = DP.id 
            left join users as U on U.id = S.user_id 
            left join customers as C on U.userable_id = C.id 
            where DP.ttype in (".$types.") and DP.status = ?
            and DP.driver_id=?
            order by DP.fecha_reserva DESC limit ? offset ?", [$balance, $status, $id, $limit, $offset]);
          
            return response()->json([
                "data" => $data,
                "meta" => [
                    "total" => DB::select("SELECT count(*) as total
                    FROM `driver_payouts` as DP 
                    where DP.ttype in (\"Deposito\", \"Retiro\") and DP.status = ?
                    and driver_id=?
                    order by fecha_reserva DESC", [$status, $id])[0]->total,
                ],

            ]);
        }
        return response([
            "error" => true,
            "msg" => "No esta autorizado para ver el listado"
        ], self::HTTP_UNAUTHORIZED);
    }
    //Lista de transacciones de conductores agrupados por empresa
    public function pendingDrivers(){
        $user = request()->user();
        if($user->can('listar pagos')){
            $offset = empty(request()->offset) ? 0 : request()->offset;
            $limit = empty(request()->limit) ? 10 : request()->limit;
            $status = empty(request()->status) ? "preordered" : request()->status;
            
            $driversNotCompany = DB::table("transactions as T")
            ->whereRaw("T.user_id not in (select U.id from users as U where userable_id in (select CD.employee_id from companies_drivers as CD) and userable_type = ?)", [Driver::class])
            ->whereNull("T.service_id")
            ->where([
                ["T.status", "=", $status],
                ["T.amount", "<", "0"],
            ])
            ->select([
                DB::raw("-1 as id"),
                DB::raw("'Sin empresa' as nombre_empresa"),
                DB::raw("'Sin empresa' as nombre_contacto"),
                DB::raw("max(T.created_at) as fecha"),
                DB::raw("abs(sum(T.total)) as articulo"),
                DB::raw("abs(sum(T.tax)) as fee"),
                DB::raw("abs(sum(T.total) - sum(T.tax)) as envio"),
            ])
            ;
            $companies = DB::table("companies_drivers as CD")->join("companies as C", "CD.company_id", "=", "C.id")
            ->leftJoin("transactions as T", "CD.employee_id", "=", DB::raw("(select U.userable_id from users as U where U.id = T.user_id limit 1)"))
            ->whereNull("T.service_id")
            ->where([
                ["T.status", "=", $status],
                ["T.amount", "<", "0"],
            ])
            ->groupBy("C.id")
            ->orderBy("C.id", "DESC")
            ->union($driversNotCompany)
            ->select([
                "C.id",
                "C.nombre_empresa",
                "C.nombre_contacto",
                DB::raw("max(T.created_at) as fecha"),
                DB::raw("abs(sum(T.total)) as articulo"),
                DB::raw("abs(sum(T.tax)) as fee"),
                DB::raw("abs(sum(T.total) - sum(T.tax)) as envio"),
            ])
            ;
            $data = (clone $companies)->limit($limit)->offset($offset)->get();
            $driversNotCompany = $driversNotCompany->first();
            $total = intval((clone $companies)->count());
            if(!empty($driversNotCompany)){
                //$total += 1;
            }
            return response()->json([
                "data" => $data,
                "meta" => [
                    "total" => $total,
                ],
                "resum" => $this->calculatePendingTotals($status)

            ]);
        }
        return response([
            "error" => true,
            "msg" => "No esta autorizado para ver el listado"
        ], self::HTTP_UNAUTHORIZED);
    }
    public function showPendingDriverPayments($id){
        $user = request()->user();
        if($user->can('listar pagos')){
            $offset = empty(request()->offset) ? 0 : request()->offset;
            $limit = empty(request()->limit) ? 10 : request()->limit;
            $status = empty(request()->status) ? "preordered" : request()->status;

            $companyId = $id;
            if($companyId == -1){
                //transacciones pendientes de conductores que no pertenecen a ninguna empresa
          
                $data = DB::table("transactions as T")
                ->whereNull("service_id")
                ->orderBy("T.id", "DESC")
                ->where([
                    ["T.status", "=", $status],
                    ["T.amount", "<", "0"],
                ])
                ->whereRaw("T.user_id in (select U.id from users as U where U.userable_id in (select D.id from drivers as D where D.id not in (SELECT employee_id from companies_drivers)) and U.userable_type = ?)", [Driver::class])
                ->leftJoin("users as U", "T.user_id", "=", "U.id")
                ->leftJoin("drivers as D", "U.userable_id", "=", "D.id")
                ->select([
                    "D.nombres",
                    "D.apellidos",
                    "T.*",
                    DB::raw("abs(T.total) as articulo"), 
                    DB::raw("abs(T.tax) as fee"), 
                    DB::raw("abs(T.total - T.tax) as envio"),
                ])
                ;
                return response()->json([
                    "data" => (clone $data)->limit($limit)->offset($offset)->get(),
                    "meta" => [
                        "total" => $data->count(),
                    ],    
                ]);
            }
            //transacciones pendientes de conductores de una empresa especificada.
            /**
             * select T.* from transactions as T where service_id is null and T.status = 'preordered' and user_id in (select U.id from users where userable_id in (SELECT employee_id from companies_drivers where company_id = ?) and userable_type = ?)
             */
            $data = DB::table("transactions as T")
            ->whereNull("service_id")
            ->orderBy("T.id", "DESC")
            ->where([
                ["T.status", "=", $status],
                ["T.amount", "<", "0"],
            ])
            ->leftJoin("users as U", "T.user_id", "=", "U.id")
            ->leftJoin("drivers as D", "U.userable_id", "=", "D.id")
            ->select([
                "D.nombres",
                "D.apellidos",
                "T.*",
                DB::raw("abs(T.total) as articulo"), 
                DB::raw("abs(T.tax) as fee"), 
                DB::raw("abs(T.total - T.tax) as envio"),
            ])
            ->whereRaw("T.user_id in (select U.id from users as U where U.userable_id in (SELECT employee_id from companies_drivers where company_id = ?) and U.userable_type = ?)", [$id, Driver::class])
            ;
            return response()->json([
                "data" => (clone $data)->limit($limit)->offset($offset)->get(),
                "meta" => [
                    "total" => $data->count(),
                ],    
            ]);
        }
        return response([
            "error" => true,
            "msg" => "No esta autorizado para ver el listado"
        ], self::HTTP_UNAUTHORIZED);
    }
    public function getTransacionStates(){
        $user = request()->user();
        if($user->can('listar pagos')){
            return response()->json([
                "data" => DB::table("transactions")->select("transactions.status")->groupBy("transactions.status")->get()->pluck("status"),
               
            ]);
        }   
        return response([
            "error" => true,
            "msg" => "No esta autorizado para ver el listado"
        ], self::HTTP_UNAUTHORIZED);
    }   

    public function update(Request $request, $id){
        $user = request()->user();
        if($user->can('listar pagos')){
            $transaction = Transaction::find($id);
            if($transaction->status != 'preordered'){
                return response()->json([
                    "error" => true,
                    "msg" => "Transacción pendiente no disponible. La transacción tiene tiene el estado: ". $transaction->status,
                ], self::HTTP_NOT_FOUND);
            }
            $userBalance = calculateDriverBalance($transaction->user_id, DB::table("transactions"));
            if (round($userBalance, 2) < Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision ) {
                return response()->json([
                    "error" => true,
                    "msg" => "No tiene balance suficiente: ".($userBalance >= 0 ? "$".$userBalance : "-$".abs($userBalance)),
                ], self::HTTP_UNAUTHORIZED);
            }
            $transaction->status = "succeeded";
            if(!empty($request->status) && $request->status == "cancel"){
                $transaction->status = "canceled";
            }
            $transaction->save();
            return response(null, 204);
        }
        return response([
            "error" => true,
            "msg" => "No esta autorizado para realizar esta acción"
        ], self::HTTP_UNAUTHORIZED);
    }
    private function calculateTotals(){
        return DB::select("SELECT sum(if(ttype = 'Efectivo', 0, DP.envio)) as envio, sum(DP.fee) as fee, sum(DP.total) as total
        FROM `driver_payouts` as DP 
        where !(DP.ttype in ('Deposito', 'Retiro') and DP.status not in ('succeeded', 'complete'))
        order by driver_id DESC")[0];
    }
    private function calculatePendingTotals($status){
        $driversNotCompany = DB::table("transactions as T")
            ->whereRaw("T.user_id not in (select U.id from users as U where userable_id in (select CD.employee_id from companies_drivers as CD) and userable_type = ?)", [Driver::class])
            ->whereNull("T.service_id")
            ->where([
                ["T.status", "=", $status],
                ["T.amount", "<", "0"],
            ])
            ->select([
                DB::raw("-1 as id"),

                DB::raw("abs(sum(T.total)) as articulo"),
                DB::raw("abs(sum(T.tax)) as fee"),
                DB::raw("abs(sum(T.total) - sum(T.tax)) as envio"),
            ])
            ;
            $companies = DB::table("companies_drivers as CD")->join("companies as C", "CD.company_id", "=", "C.id")
            ->leftJoin("transactions as T", "CD.employee_id", "=", DB::raw("(select U.userable_id from users as U where U.id = T.user_id limit 1)"))
            ->whereNull("T.service_id")
            ->where([
                ["T.status", "=", $status],
                ["T.amount", "<", "0"],
            ])
            ->orderBy("C.id", "DESC")
            ->groupBy("C.id")
            ->union($driversNotCompany)
            ->select([
                DB::raw("C.id as id"),
                DB::raw("abs(sum(T.total)) as articulo"),
                DB::raw("abs(sum(T.tax)) as fee"),
                DB::raw("abs(sum(T.total) - sum(T.tax)) as envio"),
            ])
            ;
            $data = (clone $companies)->get();
            $auxResum = [];
            foreach ($data as $key => $value) {
                
                foreach ($value as $cKey => $cValue) {
                    if(empty($auxResum[$cKey])){
                        $auxResum[$cKey] = 0;
                    }
                    $auxResum[$cKey] += $cValue;
                }
            }
            return $auxResum;
    }
    private function getCount($limit, $offset, $status = "preordered", $text = ""){
        $driverPayoutPending = collect([]);
        $ids = [];
        $marks = "";
        if($status == "preordered"){
            $driverPayoutPending = DB::table("driver_payouts as DP")->where("status", "preordered")->groupBy("DP.driver_id")->get(["DP.driver_id"])->pluck("driver_id");
            $ids = $driverPayoutPending->toArray();
            $marks = "and DP.driver_id in (".(implode(", ",array_fill(0, count($ids), '?'))).")";
        }
        if ($text == ""){
            return count(DB::select("SELECT count(*) as total FROM `driver_payouts` as DP 
            where !(DP.ttype in ('Deposito', 'Retiro') and DP.status not in ('succeeded', 'complete'))
            $marks
            group by driver_id
            order by driver_id", array_merge($ids, [$status])));
        }
            return count(DB::select("SELECT count(*) as total FROM `driver_payouts` as DP 
            left join drivers on drivers.id = DP.driver_id 
            where (drivers.nombres like ? or drivers.apellidos like ?)
            and !(DP.ttype in ('Deposito', 'Retiro') and DP.status not in ('succeeded', 'complete'))
            $marks
            group by driver_id
            order by driver_id", array_merge($ids, ["%".$text."%", "%".$text."%", $status])));

    }
    private function addSearchQuery($limit, $offset, $status = "preordered", $text = ""){
        $driverPayoutPending = collect([]);
        $ids = [];
        $marks = "";
        if($status == "preordered"){
            $driverPayoutPending = DB::table("driver_payouts as DP")->where("status", "preordered")->groupBy("DP.driver_id")->get(["DP.driver_id"])->pluck("driver_id");
            $ids = $driverPayoutPending->toArray();
            $marks = "and DP.driver_id in (".(implode(", ",array_fill(0, count($ids), '?'))).")";
        }
        //exit(implode(",", $driverPayoutPending->toArray()));
        if ($text == ""){
            return DB::select("SELECT 
                driver_id, max(DP.fecha_reserva) as fecha_reserva, sum(if(DP.ttype = 'Efectivo', 0, DP.envio)) as envio, sum(DP.fee) as fee, sum(DP.total) as total, drivers.nombres, drivers.apellidos
            FROM `driver_payouts` as DP 
            left join drivers on drivers.id = DP.driver_id 
            where !(DP.ttype in ('Deposito', 'Retiro') and DP.status not in ('succeeded', 'complete'))
            $marks
            group by driver_id 
            order by driver_id DESC limit ? offset ?", array_merge($ids, [$limit, $offset]));
        }
        return DB::select("SELECT 
            driver_id, max(DP.fecha_reserva) as fecha_reserva, sum(if(DP.ttype = 'Efectivo', 0, DP.envio)) as envio, sum(DP.fee) as fee, sum(DP.total) as total, drivers.nombres, drivers.apellidos
        FROM `driver_payouts` as DP 
        left join drivers on drivers.id = DP.driver_id 
        where !(DP.ttype in ('Deposito', 'Retiro') and DP.status not in ('succeeded', 'complete'))
            $marks
            AND (drivers.nombres like ? or drivers.apellidos like ?)
        group by driver_id 
        order by driver_id DESC limit ? offset ?", array_merge($ids, ["%".$text."%", "%".$text."%", $limit, $offset]));
        return DB::table("driver_payouts as DP")->select(DB::raw("driver_id, max(DP.fecha_reserva) as fecha_reserva, sum(DP.envio) as envio, sum(DP.fee) as fee, sum(DP.total) as total, drivers.nombres, drivers.apellidos"))
        ->leftJoin("drivers", "drivers.id", "=", "DP.driver_id")
        ->leftJoin("driver_accounts as DA", "drivers.id", "=", "DA.driver_id")
        ->whereRaw("where DP.ttype in (\"Deposito\", \"Retiro\") and DP.status = \"preordered\"")
        ->where("drivers.nombres", "like", '%'.$text.'%')
        ->groupBy("driver_id")
        ->orderBy("driver_id", "DESC")
        ->get();
    }

    //Historial de transacciones
    private function addSearchQuery_($limit, $offset, $text = ""){
        if ($text == ""){
            return DB::select("SELECT driver_id, max(DP.fecha_reserva) as fecha_reserva, sum(DP.envio) as envio, sum(DP.fee) as fee, sum(DP.total) as total, drivers.nombres, drivers.apellidos
            FROM `driver_payouts` as DP 
            left join drivers on drivers.id = DP.driver_id 
            where (if(DP.ttype in (\"Deposito\", \"Retiro\"), DP.status = \"preordered\", true)) 
            group by driver_id 
            order by driver_id DESC limit ? offset ?", [$limit, $offset]);
        }
        return DB::select("SELECT DP.driver_id, max(DP.fecha_reserva) as fecha_reserva, sum(DP.envio) as envio, sum(DP.fee) as fee, sum(DP.total) as total, drivers.nombres, drivers.apellidos,
            DA.bank as bank_name, DA.account_number as bank_number
        FROM `driver_payouts` as DP 
        left join drivers on drivers.id = DP.driver_id 
        left join driver_accounts as DA on drivers.id = DA.driver_id 
        where (if(DP.ttype in (\"Deposito\", \"Retiro\"), DP.status = \"preordered\", true)) 
            AND (drivers.nombres like ? or drivers.apellidos like ?)
        group by driver_id 
        order by driver_id DESC limit ? offset ?", ["%".$text."%", "%".$text."%", $limit, $offset]);
        return DB::table("driver_payouts as DP")->select(DB::raw("driver_id, max(DP.fecha_reserva) as fecha_reserva, sum(DP.envio) as envio, sum(DP.fee) as fee, sum(DP.total) as total, drivers.nombres, drivers.apellidos"))
        ->leftJoin("drivers", "drivers.id", "=", "DP.driver_id")
        ->leftJoin("driver_accounts as DA", "drivers.id", "=", "DA.driver_id")
        ->whereRaw("(if(DP.ttype in (\"Deposito\", \"Retiro\"), DP.status = \"preordered\", true))")
        ->where("drivers.nombres", "like", '%'.$text.'%')
        ->groupBy("driver_id")
        ->orderBy("driver_id", "DESC")
        ->get();
        /*
        */
    }
  
    private function addBankInfo($data){
        $dat = $data;
        foreach ($data as $key => $row) {
            $bank = DB::table("driver_accounts")->where("driver_id", $row->driver_id)->first();
            $dat[$key]->bank_name = null;
            $dat[$key]->bank_account_number = null;
            if(!empty($bank)){
                $dat[$key]->bank_name = $bank->bank;
                $dat[$key]->bank_account_number = $bank->account_number;
                $dat[$key]->bank_type = $bank->type;
            }
        }
        return $dat;

    }
}