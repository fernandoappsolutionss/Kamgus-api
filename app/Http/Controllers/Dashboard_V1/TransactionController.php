<?php

namespace App\Http\Controllers\Dashboard_V1;

use App\Classes\K_HelpersV1;
use App\Http\Controllers\Controller;
use App\Constants\Constant;
use App\Http\Resources\Admin\AdminServiceResource;
use App\Http\Resources\Admin\AdminServiceCollection;
use App\Http\Resources\Admin\UserCollection;
use App\Http\Resources\Customers\ServiceResource;
use App\Http\Resources\Customers\ServiceCollection;
use App\Http\Resources\Drivers\DriverCollection;
use App\Http\Resources\Drivers\DriverTokenCollection;
use App\Mail\StatusService;
use App\Models\ArticleService;
use App\Models\CustomArticle;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TransactionController extends Controller
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
    public function calculateBalance($id)
    {
        return response()->json(["balance" => calculateDriverBalance($id, DB::table("transactions"))]);
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
            case 'deposit':
                $amount = $request->amount;
                $userId = $request->userId;
                $date = $request->date;
                $description = $request->description;
                $transaction = Transaction::create([
                    "user_id" => $userId,
                    "service_id" => null,
                    "type" => "0",
                    "amount" => $amount,
                    "currency" => "USD",
                    "transaction_id" => "admin",
                    "status" => "succeeded",
                    "gateway" => "0",
                    "receipt_url" => "",
                    "description" => $description,
                    "created_at" => $date,
                ]);
                return response()->json(["error" => false, "msg" => "Deposito agregado", "data" => ["id" => $transaction->id]]);
                break;
            case 'takeout':
                $amount = $request->amount;
                $userId = $request->userId;
                $date = $request->date;
                $description = $request->description;
                $transaction = Transaction::create([
                    "user_id" => $userId,
                    "service_id" => null,
                    "type" => "0",
                    "amount" => -1 * $amount,
                    "currency" => "USD",
                    "transaction_id" => "admin",
                    "status" => "succeeded",
                    "gateway" => "0",
                    "receipt_url" => "",
                    "description" => $description,
                    "created_at" => $date,
                ]);
                return response()->json(["error" => false, "msg" => "Retiro agregado", "data" => ["id" => $transaction->id]]);
                
                break;
                case 'delete':
                    $transactionId = request()->tid;
                    $old = K_HelpersV1::getInstance()->getBalance($transactionId)->transaction_id;
                    if(K_HelpersV1::ENABLE){
                        Transaction::where("id", $old)->delete();
                        return response()->json(["error" => false, "msg" => "Transaction eliminada"]);
                    }
                    Transaction::where("id", $transactionId)->delete();
                    return response()->json(["error" => false, "msg" => "Transaction eliminada"]);

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

    

}
