<?php

namespace App\Http\Controllers\Dashboard_V1;

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

class DriversController extends Controller
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

   
    public function add_vehicle(Request $request){
        
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
        switch ($id) {
            case 'cancel':
                return $this->cancel_service($request);
                break;
            case 'value':
                return $this->updateServiceValue($request);
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
