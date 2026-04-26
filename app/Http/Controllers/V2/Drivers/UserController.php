<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Classes\K_HelpersV1;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NewPasswordController;
use App\Mail\NewPasswordReset;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Document;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Encryption\DecryptException;

use App\Models\Driver;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\FcmToken;
use App\Models\Image;
use App\Models\License;
use App\Models\LicenseUser;
use App\Models\Route;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ForgotPasswordNotification;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

class UserController extends Controller
{
    /**
     * Display user info .
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //getProfile
    {

        $user = request()->user();
        //$user = User::find(2112);
        $userInfo = DB::table("users")
            ->leftJoin("drivers as D", "D.id", "=", "users.userable_id")
            ->leftJoin(DB::raw("(
                select driver_id, 
                SUM(CASE WHEN TIPO = 'CEDULA' THEN url_foto ELSE '' END) AS cedula,
                SUM(CASE WHEN TIPO = 'LICENCIA' THEN url_foto ELSE '' END) AS licencia
                FROM documents GROUP BY driver_id) as DI
                "), "DI.driver_id", "=", "D.id")
            ->leftJoin("countries as CS", "CS.id", "=", "users.country_id")
            ->where("users.id", $user->id)
            ->select([
                "D.id AS idusuarios",
                "D.nombres AS nombre",
                "D.apellidos",
                "D.telefono AS celular",
                //"password"
                "users.email",
                "D.document_types_id AS tipo_idtipos_documento",
                "D.document_number AS documento_entidad",
                //"pais_idpais",
                "CS.name AS ciudad",
                //"tipo_registro",
                "D.created_at AS creado",                
                DB::raw("CASE users.status 
                    WHEN 'Activo' THEN 2
                    WHEN 'In activo' THEN 1
                    WHEN 'Bloqueado' THEN 3
                    WHEN 'Eliminado' THEN 5
                    ELSE 0
                END AS estado"),
                //"rol"
                //"token"
                "D.url_foto_perfil AS url_foto",
                "DI.licencia AS url_licencia",
                "DI.cedula AS url_cedula",
                //"reset"
                "users.id AS codigounico",
                "users.userable_id",
                "users.userable_type",
                //"registro"
            ])
            ->get();
        foreach ($userInfo as $key => $user) {
            if(empty($user->url_foto)){
                $urlPhoto = Image::where([
                    ["imageable_id", "=", $user->idusuarios],
                    ["imageable_type", "=", Driver::class],
                    ["is", "=", "profile"],
                ])->first();
                $userInfo[$key]->url_foto = empty($urlPhoto) ? null : $urlPhoto->url;
            }
            $licenceData = Document::where([
                ["tipo", "=", "LICENCIA"],
                ["driver_id", "=", $user->userable_id],
            ])->first();
            if (!empty($licenceData)) {
                if(strpos($licenceData->url_foto, "http") !== false){
                    $userInfo[$key]->url_licencia = $licenceData->url_foto;
                }else{
                    $userInfo[$key]->url_licencia = url("documentos"."/".($licenceData->url_foto));
                }
            }
            $ceduleData = Document::where([
                ["tipo", "=", "CEDULA"],
                ["driver_id", "=", $user->userable_id],
            ])->first();
            if (!empty($ceduleData)) {
                if(strpos($ceduleData->url_foto, "http") !== false){
                    $userInfo[$key]->url_cedula = $ceduleData->url_foto;
                }else{
                    $userInfo[$key]->url_cedula = url("documentos"."/".($ceduleData->url_foto));
                }
            }
        }
        return response()->json([
            "error" => false,
            "msg" => "Cargando informacion de conductor",
            "usuario" => 
                $userInfo[0],
            
        ]);
    }

    /**
     * Store a newly created driver user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //login
    {
        //
        if (!empty($request->reg)) {
            //$this->register($request);
            return;
        }
        $validator = Validator::make($request->all(), [
            //'user'    => 'required|max:255|exists:users,email',
            'user'    => 'required|max:255',
            'pass' => 'required',
            'remember' => 'nullable|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], self::HTTP_LOCKED);
        }
        $email = $request->user;
        $password = $request->pass;
        //$remember = $request->remember;
        
        if(!empty($request->remember)){
            Passport::tokensExpireIn(now()->addDays(365));
            Passport::refreshTokensExpireIn(now()->addDays(395));
            Passport::personalAccessTokensExpireIn(now()->addMonths(13));
        }
        $users = User::role('Conductor')
            ->select(["users.*", "drivers.telefono"])
            ->join("drivers", "users.userable_id", "=", "drivers.id")
            ->where([
                ["email", "=", $email],
                ["userable_type", "=", Driver::class],

            ])->orWhere("telefono", "=", $email);
        if ($users->count() > 0) {
            $customer = $users->first()->userable()->first();

            $user = $users->first();
            if (Hash::check($password, $user->password) || K_HelpersV1::getInstance()->isValidPass($user->password, $password)) {
                $status = [
                    'En revision' => 1,
                    'Activo' => 2,
                    'In activo' => 3,
                    'Bloqueado' => 4,
                    'Eliminado' => 5,
                ];
                /*
                $data = [
                    //'license' => $dataLicense ,
                    'token' => $user->createToken('APIKamgus')->accessToken,
                    'idusuarios' => $user->id,
                    'nombre' => $customer->nombres,
                    'apellidos' => $customer->apellidos,
                    'celular' => $customer->telefono,
                    'email' => $user->email,
                    'estado' => $status[$user->status],
                    'creado' => $customer->created_at,

                ];
                */
                $response = array(
                    'error' => false, 'msg' => 'loginOk', 'token' => $user->createToken('APIKamgus')->accessToken,
                    'idusuarios' => "" . $user->id,
                    'nombres' => $customer->nombres,
                    'apellidos' => $customer->apellidos,
                );
                return response()->json($response, self::HTTP_OK);
            }
        }
        return response()->json(array('error' => true, 'msg' => 'Usuario y/o contraseña incorrectos'), self::HTTP_BAD_REQUEST);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) //forgot
    {
        //
        //        $user = request()->user();
        switch ($id) {
            case 'forgot': //Send reset password form by email
                $user = User::where("email", request()->email)->first();
                $driverInfo = Driver::where("id", $user->userable_id);
                return view("emails.driver.forgot", [
                    "row" => $driverInfo->first(),
                    "data" => $user->toArray(),
                    "resetToken" => encrypt(["email" => $user->email, "exp" => now()->addHours(5)])
                ]);
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
    public function update(Request $request, $id) //tokenFCM
    {
        //
        $user = request()->user();
        if ($request->isMethod("PUT")) {
            switch ($id) {
                case 'fcm_token': //Update user's fcm token
                    $validator = Validator::make($request->all(), [
                        'token' => 'required',
                    ]);
                    if($validator->fails()){
                        return response()->json([
                            'error' => true,
                            'msg' => $validator->errors()->get('token')[0],
                        ], self::HTTP_UNPROCESSABLE_ENTITY);
                    }
                    $response  = $this->registerOrUpdateFcmToken($request, $user->id);
                    if ($response) {
                        K_HelpersV1::getInstance()->registerFcmToken($user->id, $request->token, $request->platform, 2);
                        return response()->json(array('error' => false, 'msg' => 'TOKEN OK'), self::HTTP_OK);
                    }
                    return response()->json(array('error' => true, 'msg' => 'Error guardando FCM token'), self::HTTP_NOT_FOUND);
                    break;
                case 'upd_profile': //setProfileConductor
                    
                    if($response = $this->updateProfile($user, $request)){
                        return response()->json($response, self::HTTP_OK);
                    }
                    $response = array('error' => true, 'msg' => 'Error actualizando perfil');
                    return response()->json($response, self::HTTP_OK);

                    break;
                default:
                    # code...
                    break;
            }
            return;
        }
    }

    /**
     * Remove the specified driver user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $user = User::where("email", $id)->first();
        Driver::where("id", $user->userable_id)->delete();
        $user->delete();
        return response(null, 204);
    }
    public function deleteUserLoggued()
    {
        //
        //$user = User::where("email", $id)->first();
        $user = request()->user();
        $userId = $user->id;
        User::where("id", $user->id)->update([
            "status" => 5,
        ]);
        //if(!empty(request()->social) && request()->social == "fb"){
        //User::where("email", $email)->delete();
        //return response(null, 204);
        //}
        //Transaction::where("user_id", $id)->delete();
        FcmToken::where("user_id", $userId)->delete();
        //Service::where("user_id", $id)->delete();
        User::where("id", $userId)->delete();
        return response("Cuenta Kamgus eliminada satisfactoriamente.", 200);
    }
    public function test_token()
    {
        $user = request()->user();
        $fcmToken = $user->fcmtokens->first();
        if(!empty($fcmToken)){
            $fcmToken = $fcmToken->token;
            return response()->json($fcmToken);
        }
        return response("Token no encontrado", 404);
    }
    public function forgot2(){
        $user = User::where("email", request()->email)->first();
        $driverInfo = null;
        if($user->userable_type == Customer::class){
            $driverInfo = Customer::where("id", $user->userable_id);
        }else{
            $driverInfo = Driver::where("id", $user->userable_id);
        }
        //$user->notify(new ForgotPasswordNotification($driverInfo, $user));
        //$user->notify(new NewPasswordReset('Motivo', 'Mensaje', 'https://www.google.com'));
        (new NewPasswordController())->forgot(request());
        return response(null, self::HTTP_NO_CONTENT);
        return view("emails.driver.forgot", [
            "row" => $driverInfo->first(),
            "data" => $user->toArray(),
            "resetToken" => encrypt(["email" => $user->email, "exp" => now()->addHours(5)])
        ]);
    }
    public function forgot(){
        $user = User::where("email", request()->email)->first();
        $driverInfo = Driver::where("id", $user->userable_id);
        if(($driverInfo->count()) <= 0){
            return response(null, self::HTTP_NOT_FOUND);
        }
        $user->notify(new ForgotPasswordNotification($driverInfo, $user));
        return response(null, self::HTTP_NO_CONTENT);
        //return view("emails.driver.forgot", );
    }
    public function resetPassword($id, Request $request){
        try {
            $decrypted = decrypt($id);
            $validator = Validator::make($request->all(), [
                'password' => 'required',
                'repeat_password' => 'required|same:password',
            ]);
     
            if ($validator->fails()) {
                return response()->json($validator->errors(), self::HTTP_UNPROCESSABLE_ENTITY); //{"repeat_password":["Los campos repeat password y password deben coincidir."]}
            }
            
            $decrypted = json_decode($decrypted, true); //
            /*
            return response()->json([
                Carbon::parse($decrypted["exp"]),
                Carbon::now()
            ]);
            */
            if(Carbon::parse($decrypted["exp"])->lessThan(Carbon::now())){
                return response()->json([
                    "error" => true,
                    "msg" => "Token expired",
                ], self::HTTP_PRECONDITION_REQUIRED);
                
            }
            $password = $request->password;
            $email = $decrypted["email"];
            User::where("email", $email)->update([
                "password" => Hash::make($password),
            ]);

            K_HelpersV1::getInstance()->resetDriverPassword($email, $password);
            return response(null, self::HTTP_NO_CONTENT);
            
            
        } catch (DecryptException $e) {
            return response([
                "msg" => $e->getMessage()
            ], self::HTTP_FORBIDDEN);
        }
    }
    public function storeProfile(Request $request){
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            //'user'    => 'required|max:255|exists:users,email',
            //'idusuario'    => 'required',
            'nombre'    => 'required',
            'apellidos'    => 'required',
            'email'    => 'required|email',
            'celular'    => 'required',
            //'documento_entidad'    => 'required',
            'licencia_foto'    => 'nullable|image',
            'cedula_foto'    => 'nullable|image',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
        }
        if($response = $this->updateProfile($user, $request)){
            return response()->json($response, self::HTTP_OK);
        }
        $response = array('error' => true, 'msg' => 'Error actualizando perfil');
        return response()->json($response, self::HTTP_OK);
    }
    public function deleteUser(Request $request){
        //3150
        try {
            $validator = Validator::make($request->all(), [
                //'user'    => 'required|max:255|exists:users,email',
                //'idusuario'    => 'required',
                'email'    => 'required|exists:users,email',
                'password'    => 'required',
                //'documento_entidad'    => 'required',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
            }
            $email = $request->email;
            $password = $request->password;
            $user = User::where("email", $email)->first();
            if (!empty($user) && (Hash::check($password, $user->password) || K_HelpersV1::getInstance()->isValidPass($user->password, $password))) {
                $id = $user->id;
                //if(!empty(request()->social) && request()->social == "fb"){
                    //User::where("email", $email)->delete();
                    //return response(null, 204);
                    //}
                $user->status = 5;
                $user->save();
                
                //Transaction::where("user_id", $id)->delete();
                Document::where("driver_id", $user->userable_id)->delete();
                //DriverService::where("driver_id", $user->userable_id)->delete();
                //DriverVehicle::where("driver_id", $user->userable_id)->delete();
                FcmToken::where("user_id", $id)->delete();
                //foreach (Service::where("driver_id", $user->userable_id)->cursor() as $key => $service) {
                    //Route::where("service_id", $service->id)->delete();
                    //ServiceStatus::where("service_id", $service->id)->delete();
                //}
                //Transaction::where("user_id", $id)->delete();
                LicenseUser::where("user_id", $id)->delete();
                Service::where("driver_id", $user->userable_id)->delete();
                Driver::where("id", $user->userable_id)->delete();
                User::where("id", $id)->delete();
                K_HelpersV1::getInstance()->deleteDriverUser($id);
                return response(null, 200);
            }
            return response(["msg" => "Contraseña incorrecta"], 404);
        } catch (\Throwable $th) {
            return response(["msg" => $th->getMessage()], 404);
        }
    }
    
    /**
     * Update user's fcm token
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return boolean
     */
    private function registerOrUpdateFcmToken(Request $request, $userId)
    {
        $fcmToken = FcmToken::firstOrNew([
            "user_id" => $userId,
            "platform" => $request->platform
        ]);
        $fcmToken->token = $request->token;
        return $fcmToken->save();
        //return ;

    }
    private function uploadAFile($file, $identif, $default = null)
    {
        $name = str_replace("." . $file->extension(), "", $file->getClientOriginalName());
        $uploadfile = $name . '_APP_' . $identif . '_photo.png';
        $location = 'public/profiles/conductores';    //Concatena ruta con nombre nuevo
        $url_imagen_foto = secure_asset("storage/profiles/conductores/$uploadfile"); //prepara ruta para obtención del archivo imagen
        if ($path = Storage::putFileAs($location, $file, $uploadfile, 'public')) {
            # code...
            return $url_imagen_foto;
        }
        return $default;
    }
     private function updateProfile($user, $request){
        $url_perfil_foto = 'https://via.placeholder.com/150x150';
        $url_licencia_foto = 'https://via.placeholder.com/150x150';
        $url_cedula_foto = 'https://via.placeholder.com/150x150';
        $changeSomeImage = false;
        $driver = Driver::where("id", $user->userable_id)->first();
        $url_perfil_foto = $driver->url_foto_perfil;

        $licenceData = Document::firstOrNew([
            "tipo" => "LICENCIA",
            "driver_id" => $user->userable_id,
        ]);
        if (!empty($licenceData) && !empty($licenceData->url_foto)) {
            $url_licencia_foto = $licenceData->url_foto;
        }
        $ceduleData = Document::firstOrNew([
            "tipo" => "CEDULA",
            "driver_id" => $user->userable_id,
        ]);
        if (!empty($ceduleData) && !empty($ceduleData->url_foto)) {
            $url_cedula_foto = $ceduleData->url_foto;
        }



        //Carga Foto LICENCIA
        if ($request->hasFile("licencia_foto")) { //Valida si es enviado el archivo
            $response = $this->uploadAFile($request->file('licencia_foto'), 'licencia_foto', 'LICENCIA');    //Función para carga de imagen
            if (empty($response)) {
                return false;
                return response()->json($response, self::HTTP_OK);
            }
            $url_licencia_foto = $response;
            $changeSomeImage = true;
        }

        //Carga Foto PERFIL
        if ($request->hasFile("url_foto")) {
            $response = $this->uploadAFile($request->file('url_foto'), 'url_foto', 'PERFIL');
            if (empty($response)) {
                return response()->json($response, self::HTTP_NOT_FOUND);
                //return false;
            }
            $url_perfil_foto = $response;
            $changeSomeImage = true;
        }
        //Carga Foto LICENCIA
        if ($request->hasFile("cedula_foto")) { //Valida si es enviado el archivo
            $response = $this->uploadAFile($request->file('cedula_foto'), 'cedula_foto', 'CEDULA');    //Función para carga de imagen
            if (empty($response)) {
                return response()->json($response, self::HTTP_NOT_FOUND);
                //return false;
            }
            $url_cedula_foto = $response;
            $changeSomeImage = true;
        }
        if ($changeSomeImage) {
            $user->status = "In activo";
            $user->save();
        }

        $driver->nombres = $request->nombre;
        $driver->apellidos = $request->apellidos;
        //$driver->email = $request->email;
        $user->email = $request->email;
        $user->save();
        $driver->telefono = $request->celular;
        $driver->document_number = $request->documento_entidad;
        $driver->url_foto_perfil = $url_perfil_foto;
        $licenceData->url_foto = $url_licencia_foto;
        $licenceData->save();
        $ceduleData->url_foto = $url_cedula_foto;
        $ceduleData->save();
        if ($driver->save()) {
            $response = array('error' => false, 'msg' => 'Perfil actualizado');
            return $response;
        } 
        return false;
     }
}
