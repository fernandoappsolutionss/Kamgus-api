<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Classes\K_HelpersV1;
use App\Http\Controllers\Controller;
use App\Models\DriverAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBankAccount extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //getDriverBanks
    {
        //
        $user = request()->user();

        $dAccounts = DriverAccount::where("driver_id", $user->userable_id)
            ->leftJoin("drivers as D", "driver_accounts.driver_id", "=", "D.id");
        if ($dAccounts->count() > 0) {
            $response = array('error' => false, 'msg' => 'Cargando cuentas de bancos', 'data' => $dAccounts->get($this->getDriverAccountFields()));
            return response()->json($response, self::HTTP_OK);
        } else {
            $response = array('error' => true, 'msg' => 'Error consultando cuentas de bancos');
            return response()->json($response, self::HTTP_OK);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //setDriverBanks
    {
        //
        $user = request()->user();
        $rules = [
            'banco'   => 'required',
            'numero_cuenta'   => 'required',
            'doc_identidad'   => 'required',
        ];

        $this->validate($request, $rules);
        try {
            DB::beginTransaction();
            $banco = $request->banco;
            $numeroCuenta = $request->numero_cuenta;
            $docIdentidad = $request->doc_identidad;
            $created = DriverAccount::firstOrCreate([
                'driver_id' => $user->userable_id,
            ], [
                "bank" => $banco,
                "account_number" => $numeroCuenta,
            ]);

            $created->bank = $banco;
            $created->account_number = $numeroCuenta;
            $created->type = $docIdentidad;
            $created->save();
            if ($created) {
                K_HelpersV1::getInstance()->setDriverBanks($request->all(), $user->id);
                DB::commit();
                $response = array('error' => false, 'msg' => 'Datos de banco actualizado');
                return response()->json($response, self::HTTP_OK);
            } else {
                DB::commit();
                $response = array('error' => true, 'msg' => 'Error actualizando datos de banco');
                return response()->json($response, self::HTTP_OK);
            }
        } catch (\Exception $e) {
            DB::rollback();
            $response = array('error' => true, 'msg' => 'Error actualizando datos de banco', "detail" => $e->getMessage());
            return response()->json($response);
            //return $e->getMessage();
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //updDriverBank
    {
        //
        $rules = [
            'banco'   => 'required',
            'numero_cuenta'   => 'required',
            'doc_identidad'   => 'required',
        ];

        $this->validate($request, $rules);
        $user = request()->user();
        $driverAccount = DriverAccount::find($id);
        $driverAccount->bank = $request->banco;
        $driverAccount->account_number = $request->numero_cuenta;
        $driverAccount->save();
        K_HelpersV1::getInstance()->setDriverBanks($request->all(), $user->id);
        //$docIdentidad = $request->doc_identidad;

        return response()->json(null, self::HTTP_NO_CONTENT);
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
    private function getDriverAccountFields()
    {
        //, , nombres
        return [
            "driver_accounts.bank as banco",
            "driver_accounts.account_number as numero_cuenta",
            "driver_accounts.type as account_type",
            //"D.document_number as doc_identidad",
            DB::raw("concat(D.nombres, ' ', D.apellidos) as nombres"),

        ];
    }
}
