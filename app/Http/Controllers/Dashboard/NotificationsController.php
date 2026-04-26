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
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\Image;
use App\Models\Transaction;
use App\Notifications\K_SendPushNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificationsController extends Controller
{
    public function store(Request $request, $id)
    {
        switch ($id) {
            case 'fcmService':
                $validator = Validator::make($request->all(), [
                    //'user'    => 'required|max:255|exists:users,email',
                    //'idusuario'    => 'required',
                    'title'    => 'required',
                    'id_usuario'    => 'required',
                    'body'    => 'required',
                    //'documento_entidad'    => 'required',
                ]);
        
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                }
                
                $title = $request->title;
                $id_usuario = $request->id_usuario;
                $body = $request->body;
                $user = User::find($id_usuario);
                if(empty($user)){
                    $response = array('error' => true, 'msg' => 'No se encontro el usuario' );
                    return response()->json($response, self::HTTP_NOT_FOUND);
                }
                $fcmTokens = $user->fcmtokens()->orderBy("updated_at", "DESC")->get();
                $completed = [];
                foreach ($fcmTokens as $key => $fcmT) {
                    # code...
                    $fcmToken = $fcmT->token;
                    $completed[] = $user->notify(new K_SendPushNotification($title, $body, $fcmToken));
                }
                return $completed;
                break;
            case 'fcmtopics':
                $validator = Validator::make($request->all(), [
                    //'user'    => 'required|max:255|exists:users,email',
                    //'idusuario'    => 'required',
                    'title'    => 'required',
                    //'id_usuario'    => 'required',
                    'body'    => 'required',
                    //'documento_entidad'    => 'required',
                ]);
        
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                }
                $title = $request->title;
                $id_usuario = $request->id_usuario;
                $body = $request->body;
                $url = "https://fcm.googleapis.com/fcm/send";
                $token = "/topics/marketing-kamgus";
                $serverKey = env("AUTHENTICATION_KEY_FIREBASE") ;
                $notification = array(
                    'title' =>$title , 
                    'body' => $body, 
                    'sound' => 'default', 
                    'badge' => '1'
                    );
                $dataNot = array(
                    'url'=>$body
                );
                $arrayToSend = array(
                    'to' => $token, 
                    'data'=>$dataNot,
                    'notification' => $notification,
                    'priority'=>'high'
                    );
                $json = json_encode($arrayToSend);
                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization: key='. $serverKey;
                
            
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                //Send the request
                $responses = curl_exec($ch);
                curl_close($ch);
                //dd($responses);
                return response($responses);
                break;
            
            default:
                # code...
                break;
        }
    }
}