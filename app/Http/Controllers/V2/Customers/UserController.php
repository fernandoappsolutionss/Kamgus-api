<?php

namespace App\Http\Controllers\V2\Customers;

use App\Classes\K_HelpersV1;
use App\Classes\SendinblueCustomerClass;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Driver;
use App\Models\FcmToken;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use DB;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $user = request()->user();
        $userInfo = $this->getUserData($user);
 
        return response()->json([
            "error" => false,
            "msg" => "Cargando informacion de cliente",
            "usuario" => 
                $userInfo[0],
            
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() //
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeProfile(Request $request) //updateProfile
    {

    }
    public function store(Request $request) //login, register
    {
        if(!empty($request->reg)){
            $this->register($request);
            return;
        }
        $validator = Validator::make($request->all(), [
            'user'    => 'required|max:255|exists:users,email',
            'pass' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], self::HTTP_LOCKED);
        }
        $email = $request->user;
        $password = $request->pass;
        $remember = $request->remember;

        $users = User::role('Cliente')->where([
            ["email", "=", $email],
        ]);
        
        if($users->count() > 0){
            $customer = $users->first()->userable()->first();
            
            $user = $users->first();
            if(Hash::check($password, $user->password) || K_HelpersV1::getInstance()->isValidPass($user->password, $password)){
                $status = [
                    'En revision' => 1,
                    'Activo' => 2,
                    'In activo' => 3,
                    'Bloqueado' => 4,
                    'Eliminado' => 5,
                ];
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
                $response = array('error' => false, 'msg' => 'loginOk', 'user'=> [$data], 'wallet'=> null );
                return response()->json( $response, self::HTTP_OK);
            }
        }
        return response()->json(array('error' => true, 'msg' => 'Usuario y/o contraseña incorrectos' ), self::HTTP_BAD_REQUEST);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) // , getProfile
    {
        //
        switch ($id) {
            case 'forgot':
                $user = User::where("email", request()->email)->first();
                $driverInfo = Customer::where("id", $user->userable_id);
                return view("emails.customer.forgot", [
                    "row" => $driverInfo->first(),
                    "data" => $user->toArray(),
                    "resetToken" => encrypt(["email" => $user->email, "exp" => now()->addHours(5)])
                ]);
                break;
            case 'profile':
                
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }
    public function updateFcmToken(Request $request) // , savephone
    {
        $user = request()->id_usuario;
        try {
            if(empty($user)){
                $user = request()->user();
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(["msg" => $th->getMessage()], 400);
        }
        if (!empty($user) && $this->registerOrUpdateFcmToken($request, $user->id)){
            K_HelpersV1::getInstance()->registerFcmToken($user->id, $request->token, getPlatform());
            return response()->json([], 204);
        }
        return response()->json(["msg" => $user], 400);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) // , savephone
    {
        $user = $request->user();
        switch ($id) {
            case 'fcm_token':
                if ($this->registerOrUpdateFcmToken($request, $user->id)){
                    K_HelpersV1::getInstance()->registerFcmToken($user->id, $request->token, getPlatform());
                    return response()->json([], 204);
                }
                return response()->json([], 400);
                
                break;
            case 'phone':
                $validator = Validator::make($request->all(), [
                    'email'    => 'required|email|exists:users,email',
                    'apellidos' => 'required',
                    'nombre' => 'required',
                    'celular' => [
                        'regex:/(\+?\d)/i',
                        'not_regex:/[a-z]/i',
                    ],
                  
                ]);
        
                if($validator->fails()){
                    return response()->json(['error' => $validator->errors()], self::HTTP_LOCKED);
                }
                $customer = Customer::find($user->userable->id);          
                $customer->telefono = $request->celular;
                $customer->apellidos = $request->apellidos;
                $customer->nombres = $request->nombre;
                $user->email = $request->email;
                $user->save();
                $customer->save();
                return response(null, self::HTTP_OK);
                break;
            case 'update':
                $validator = Validator::make($request->all(), [
                    'email'    => 'required|email',
                    'apellidos' => 'required',
                    'nombre' => 'required',
                    'celular' => 'required|alpha_num',
                ]);
        
                if($validator->fails()){
                    return response()->json(['error' => $validator->errors()], self::HTTP_LOCKED);
                }
                //dd($user);
                $customer = Customer::find($user->userable->id);          
                $customer->telefono = $request->celular;
                $customer->apellidos = $request->apellidos;
                $customer->nombres = $request->nombre;
                $user->email = $request->email;
                $user->save();
                $customer->save();
                $userInfo = $this->getUserData($user);

                $response = array('error' => false, 'msg' => 'Perfil actualizado', 'user'=> $userInfo );
                return response()->json($response, self::HTTP_OK);
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
    
    public function validateCode(){
        
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function socialLogin(Request $request, $id){ //userapple, userfacebook
        switch ($id) {
            case 'facebook':
                $token = md5(rand().rand().rand().rand().'kamgusconductor');
                $data = $this->existUserFacebook($request->password);
        
                if(is_array($data)) {
                    return response()->json($data);
                }
                $user = User::where("email", $request->email)->first();
                if(empty($user)){
                    $user = $this->register(new Request([
                        "nombre" => $request->nombre,
                        "nombres" => $request->nombre,
                        "apellidos" => $request->apellidos,
                        "password" => $request->password,
                        "email" => $request->email,
                        "telefono" => date("Ymd"),
                    ]));
                }

                DB::table("social_auth_token")->insertGetId([
                    "token" => $request->password,
                    "user_id" => User::where("email", $request->email)->first()->id,
                ]);
                
                $data = array(
                    'error' => false,
                    'msg' => 'loginOkInsert',
                    'user' => array(
                        array(
                            'idusuarios' => $user->id,
                            'nombre' => $request->apellidos,
                            'apellidos' => $request->password,
                            'email' => $user->email,
                            'token' => $user->createToken('APIKamgus')->accessToken,
                        )
                    ),
                    //'wallet' => getWalletUser($_POST['id_user'])

                );  
                return response()->json($data); 
                break;
            case 'apple':
                $codigo = $this->randID(8); 
                $validator = Validator::make($request->all(), [
                    'email'    => 'required|email',
                ]);
        
                if($validator->fails()){
                    return response()->json(['error' => $validator->errors()], self::HTTP_LOCKED);
                }
                $nombre = $request->nombre;
                if(empty($request->nombre)) {
                    $nombre = '';
                }
        
                $apellidos = $request->apellidos;
                if(empty($request->apellidos)) {
                    $apellidos = '';
                }
        
                $email = $request->email;
                if(empty($request->email)) {
                    $email = '';
                }
                $data = $this->existAppleUser($request->password);
        
                if($data !== false && is_array($data)) {
                    return response()->json($data);
                }
                $user = User::where("email", $email)->first();
                if(empty($user)){
                    $user = $this->register(new Request([
                        "nombre" => $nombre,
                        "nombres" => $nombre,
                        "apellidos" => $apellidos,
                        "password" => $request->password,
                        "email" => $email,
                        "telefono" => date("Ymd"),
                    ]));
                }
                    
                DB::table("social_auth_token")->insertGetId([
                    "token" => $request->password,
                    "user_id" => User::where("email", $email)->first()->id,
                ]);
                
                $data = array(
                    'error' => false,
                    'msg' => 'loginOkInsert',
                    'user' => array(
                        array(
                            'idusuarios' => $user->id,
                            'nombre' => $nombre,
                            'apellidos' => $apellidos,
                            //'apellidos' => $request->password,
                            'email' => $user->email,
                            'token' => $user->createToken('APIKamgus')->accessToken,
                            )
                        ),
                        //'wallet' => getWalletUser($_POST['id_user'])
                        
                    );  
                return response()->json($data); 
                break;
            default:
                # code...
                break;
        }
    }
    public function deleteUser($emailToken){
        $decoded = Crypt::decryptString($emailToken);
        $decoded = json_decode($decoded, true);
        //return Carbon::parse($decoded["expire"])->lessThan(Carbon::now()) ? 1 : 2;
        if (Carbon::parse($decoded["expire"])->lessThan(Carbon::now())) {
            return response("El enlace ha expirado.", self::HTTP_BAD_REQUEST);
        }
        $userId = $decoded['user_id'];
        User::where("id", $decoded['user_id'])->update([
            "status" => 5,
        ]);
        //if(!empty(request()->social) && request()->social == "fb"){
        //User::where("email", $email)->delete();
        //return response(null, 204);
        //}
        //return response(null, 200);
        //Transaction::where("user_id", $id)->delete();
        FcmToken::where("user_id", $userId)->delete();
        //Service::where("user_id", $id)->delete();
        User::where("id", $userId)->delete();
        return response(null, self::HTTP_NOT_FOUND);
    }
    //Envia via email confirmacion para eliminar la cuenta registrada de un usuario
    public function sendDeleteAccountConfirmation($email){
        //consultar info de usuario
        $validator = Validator::make(["email" => $email], [
            'email'    => 'required|max:255|exists:users,email',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], self::HTTP_LOCKED);
        }
        $user = User::where("email", $email)
            //->where("userable_type", Driver::class)
            ->first();
        //Enviar un enlace via email para confirmar eliminacion de cuenta
        $tempToken = Crypt::encryptString(json_encode([
            "user_id" => $user->id, 
            "expire" => Carbon::now()->addHours(2)
        ]));
        //return url("api/v2/customer/delete/".$tempToken);
        return SendinblueCustomerClass::getInstance()->sendTemplateEmailWithCurl($user->email, SendinblueCustomerClass::DELETE_ACCOUNT_CONFIRMATION, "Confirmar eliminación de cuenta", [
            'link' => url("api/v2/customer/delete/".$tempToken),
        ]);
        return "Solicitud enviada. Por favor revise su bandeja de correo electronico para continuar.";
    }
    /**
     * Update user's fcm token
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return boolean
     */
    private function registerOrUpdateFcmToken(Request $request, $userId){
        $fcmToken = FcmToken::firstOrNew([
            "user_id" => $userId,             
        ]);
        $fcmToken->token = $request->token;
        $fcmToken->platform = getPlatform();
        return $fcmToken->save();
        //return ;
        
    }
    private function register(Request $request)
    {
        $customer = new Customer();
        $customer->nombres = $request->nombres;
        $customer->apellidos = $request->apellidos;
        $customer->telefono = $request->telefono;
        $customer->save();

        $user = new User();
        $user->email = $request->email;
        $user->password = Hash::make("K12345678");
        $user->userable_id = $customer->id;
        $user->userable_type = Customer::class;
        $user->country_id = Country::where("iso", "PA")->first()->id;
        $user->save();
        K_HelpersV1::getInstance()->registerCustomer(array_merge($request->all(), [ "url_foto" => null, "url_licencia" => null ]), $user->id);
        // Asignar un rol
        $user->assignRole('Cliente');

        //enviar email de registro de conductor


        return $user;
    }
    private function randID($n) { 
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; 
        $randomString = ''; 
      
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    }
    private function existUserFacebook($idface) { 
        /*
        $db = new db();
        $db = $db->conectar();
        $sql = $db->prepare("SELECT *
                            FROM usuarios WHERE password = :id
                            AND registro = 'FACEBOOK'");  
        $sql->bindParam(':id', $idface, PDO::PARAM_INT);
        $sql->execute();
        $row = $sql->fetch(PDO::FETCH_OBJ);
        */
        
        $socialId = DB::table("social_auth_token")->where([
            ["token", "=", $idface],
            ["type", "=", "Facebook"],
        ])
        ->whereNotNull('user_id')->first();
        if(!empty($socialId)) {        
            $status = [
                'En revision' => 1,
                'Activo' => 2,
                'In activo' => 3,
                'Bloqueado' => 4,
                'Eliminado' => 5,
            ];
            $user = User::where("id", $socialId->user_id)->first();
            return array(
                'error' => false,
                'msg' => 'loginOk',
                'user' => [
                    [
                        'token' => $user->createToken('APIKamgus')->accessToken,
                        'idusuarios' => $user->id,
                        'nombre' => $user->userable->nombres,
                        'apellidos' => $user->userable->apellidos,
                        'celular' => $user->userable->telefono,
                        'email' => $user->email,
                        'estado' => $status[$user->status],
                        'creado' => $user->userable->created_at,
                    ]
                ],
                
                //'wallet' => getWalletUser($row->idusuarios)
            );
        } else
        return false;
    }
    private function existAppleUser($idApple) { 
        /*
        $db = new db();
        $db = $db->conectar();
        $sql = $db->prepare("SELECT *
                            FROM usuarios WHERE password = :id
                            AND registro = 'FACEBOOK'");  
        $sql->bindParam(':id', $idApple, PDO::PARAM_INT);
        $sql->execute();
        $row = $sql->fetch(PDO::FETCH_OBJ);
        */
        
        $socialId = DB::table("social_auth_token")->where([
            ["token", "=", $idApple],
            ["type", "=", "Apple"],
        ])
        ->whereNotNull('user_id')->first();
        if(!empty($socialId)) {        
            /*
                    */
            $user = User::where("id", $socialId->user_id)->first();
            $status = [
                'En revision' => 1,
                'Activo' => 2,
                'In activo' => 3,
                'Bloqueado' => 4,
                'Eliminado' => 5,
            ];
            return array(
                'error' => false,
                'msg' => 'loginOk',
                'user' => [
                    [
                        'token' => $user->createToken('APIKamgus')->accessToken,
                        'idusuarios' => $user->id,
                        'nombre' => $user->userable->nombres,
                        'apellidos' => $user->userable->apellidos,
                        'celular' => $user->userable->telefono,
                        'email' => $user->email,
                        'estado' => $status[$user->status],
                        'creado' => $user->userable->created_at,
                    ]
                ],
                
                //'wallet' => getWalletUser($row->idusuarios)
            );
        } else
        return false;
    }

    private function getUserData($user){
        return DB::table("users")
            ->leftJoin("customers as U", "U.id", "=", "users.userable_id")
            ->leftJoin("countries as CS", "CS.id", "=", "users.country_id")
            ->where("users.id", $user->id)
            ->select([
                "U.id AS idusuarios",
                "U.nombres AS nombre",
                "U.apellidos",
                "U.telefono AS celular",
                //"password"
                "users.email",
                //"U.document_types_id AS tipo_idtipos_documento",
                //"U.document_number AS documento_entidad",
                //"pais_idpais",
                "CS.name AS ciudad",
                //"tipo_registro",
                "U.created_at AS creado",
                "users.status AS estado",
                //"rol"
                //"token"
                "U.url_foto_perfil AS url_foto",
                //"reset"
                "users.id AS codigounico",
                "users.userable_id",
                "users.userable_type",
                //"registro"
            ])
            ->get();
    }
}
