<?php

namespace App\Http\Controllers;

use App\Classes\K_HelpersV1;
use App\Events\WelcomeEmailEvent;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Mail;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Mail\NewUser;
use App\Mail\NewDriver;
use App\Mail\NewCompany;
use App\Http\Resources\Admin\UserCollection;
use App\Http\Resources\Admin\UserResource;
use App\Models\FcmToken;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roleS = '';
        $status = '';

        if($request->status){
            $status = $request->status;
        }

        if($request->role){
            $roleS = Role::where('name', $request->role)->first();
        }

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

        return new UserCollection(
            User::RoleFilter($roleS)
            ->UserFilter($initial_date, $final_date)
            ->Status($status)
            ->orderBy('created_at')
            ->paginate($request->perPage)
        );

        // return User::RoleFilter($roleS);
        
        // return new UserCollection(User::paginate($request->perPage));
    }

    /**
     * registration_customers a newly created resource in storage.
     *  Create an instance of users and customers
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registration_customers(Request $request)
    {

        $rules = [
            'nombres' => 'required|max:255',
            'apellidos' => 'required|max:255',
            'telefono'  => 'required|max:255|unique:customers,telefono',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ];

        $this->validate($request, $rules);

        $customer = new Customer();
        $customer->nombres = $request->nombres;
        $customer->apellidos = $request->apellidos;
        $customer->telefono = $request->telefono;
        $customer->save();
        
        $user = new User();
        if(K_HelpersV1::ENABLE){
            //Optional. Usado para sincronizar el id del usuario en ambas bases de datos en el registro
            $user->id = K_HelpersV1::getInstance()->registerCustomer(array_merge($request->all(),[
                "url_foto" => null,
            ]));
        }
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->userable_id = $customer->id;
        $user->userable_type = "App\Models\Customer";
        $user->save();

        // Asignar un rol
        $user->assignRole('Cliente');
     
        //crear cliente de stripe
        $user->createAsStripeCustomer();

        //actualizar el nombre
        $user->updateStripeCustomer(
            ['name' => $user->userable->nombres]
        );

        // $mail = new NewCustomerMailable;
        // Mail::to($user->email)->send($mail);
        event(new WelcomeEmailEvent($user->email));

        $data = [
            'mensaje' => 'Cliente registrado correctamente',
            'codigo' => 200,
            'data' => [$customer, $user ]
        ];

        return $this->successResponse($data);

    }

     /**
     * registration_drivers a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registration_drivers(Request $request)
    {

        $rules = [
            'nombres' => 'required|max:255',
            'apellidos' => 'required|max:255',
            'telefono'  => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ];

        $this->validate($request, $rules);

        // return $request->file('cedula');
        
        $driver = new Driver();
        $driver->nombres = $request->input("nombres");
        $driver->apellidos = $request->input("apellidos");
        $driver->telefono = $request->input("telefono");
        $driver->save();
        $urlCedula = "";
        $urlLicencia = "";
        
        if($request->file('cedula')){
            // $path = $request->file('cedula')->store('public/documentos');
            $file = $request->file('cedula');
            $path = public_path() . '/documentos'; 
            $name = time() . $file->getClientOriginalName();
            $urlCedula = $name;
            $file->move($path, $name);

            $documento = new Document();
            $documento->tipo = "CEDULA";
            $documento->url_foto = $name;
            $documento->driver_id = $driver->id;
            $documento->save();
        }
        
        if($request->file('pasaporte')){
            // $request->file('pasaporte')->store('public/documentos');

            $file = $request->file('pasaporte');
            $path = public_path() . '/documentos'; 
            $name = time() . $file->getClientOriginalName();
            $file->move($path, $name);

            $documento = new Document();
            $documento->tipo = "PASAPORTE";
            $documento->url_foto = $name;
            $documento->driver_id = $driver->id;
            $documento->save();
        }
        
        if($request->file('licencia')){
            // $path = $request->file('licencia')->store('´public/documentos');
            $file = $request->file('licencia');
            $path = public_path() . '/documentos'; 
            $name = time() . $file->getClientOriginalName();
            $urlLicencia = $name;
            $file->move($path, $name);

            $documento = new Document();
            $documento->tipo = "LICENCIA";
            $documento->url_foto = $name;
            $documento->driver_id = $driver->id;
            $documento->save();
        }
        $user = new User();
        if(K_HelpersV1::ENABLE){
            //Optional. Usado para sincronizar el id del usuario en ambas bases de datos en el registro
            $user->id = K_HelpersV1::getInstance()->registerDriver(array_merge($request->all(),[
                "url_foto" => null,
                "url_licencia" => $urlLicencia,
            ]));
        }
        
        $user->email = trim($request->input("email"));
        $user->password = Hash::make($request->password);
        $user->userable_id = $driver->id;
        $user->userable_type = "App\Models\Driver";
        $user->save();

        // Asignar un rol
        $user->assignRole('Conductor');

        //enviar email de registro de conductor
        Mail::to($user->email)->send( new NewDriver('Motivo', 'Mensaje') );
        
        return response()->json([
            'mensaje' => 'Consulta realizada',
            'codigo' => 200,
            'data' => [$driver, $user]
        ]);
    
        
    }

    /**
     * registration_companies a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registration_companies(Request $request)
    {

        $rules = [
            'nombre_empresa' => 'required|max:255',
            'nombre_contacto' => 'required|max:255',
            // 'nombres' => 'required|max:255',
            // 'apellidos' => 'required|max:255',
            'telefono'  => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ];

        $this->validate($request, $rules);

        $company = new Company();
        $company->nombre_empresa = $request->nombre_empresa;
        $company->nombre_contacto = $request->nombre_contacto;
        $company->telefono = $request->telefono;
        $company->save();
        
        $user = new User();
        if(K_HelpersV1::ENABLE){
            //Optional. Usado para sincronizar el id del usuario en ambas bases de datos en el registro
            $user->id = K_HelpersV1::getInstance()->registerEnterprise(array_merge($request->all(),[
                "nombres" => $request->nombre_empresa,
                "apellidos" => $request->nombre_contacto,
                "telefono" => $request->telefono,
                "email" => $request->email,
                "password" => $request->password,
            ]));
        }
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->userable_id = $company->id;
        $user->userable_type = "App\Models\Company";
        $user->save();
        
        
        // Asignar un rol
        $user->assignRole('Empresa');

        //enviar el correo cuando se registre una empresa
        Mail::to($user->email)->send( new NewCompany('Motivo', 'Mensaje') );


        return response()->json([
            'mensaje' => 'Consulta realizada',
            'codigo' => 200,
            'data' => [$company, $user]
        ]);

    }

    /**
     * verificacion_correo a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verificacion_correo(Request $request)
    {
        $phoneRules = !empty($request->type) && ($request->type === "driver") ? 
            'required|unique:companies,telefono':
            'required|unique:drivers,telefono|unique:customers,telefono|unique:companies,telefono';
        $rules = [
            'email' => 'required|email|unique:users,email',
            //'telefono' => 'required|unique:drivers,telefono|unique:customers,telefono|unique:companies,telefono',
            'telefono' => $phoneRules,
        ];

        $this->validate($request, $rules);
        
        return response()->json([
            'msg' => 'Puede continuar',
            'code' => 200,
        ]);
    }

    /**
     * login a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email'    => 'required|max:255|exists:users,email',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }

        $user = User::whereEmail($request->email)->first();

        //aqui se obtiene el fcmToken
        


        if(!is_null($user) && (Hash::check($request->password, $user->password) || K_HelpersV1::getInstance()->isValidPass($user->password, $request->password))){

            $token = $user->createToken('APIKamgus')->accessToken;
            return response()->json(['success' => 'Bienvenido!',
                                     'token'   =>  $token,
                                     //'role' => $user->roles[count($user->roles) - 1]->name,
                                     'role' => $user->roles[0]->name,
                                    //  'license_modules' => $user->licenses->first()->modules
                                    ]); 
                                                                         
        }else{
            return response()->json(['error' => 'Correo o contraseña incorrectos!']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {

        $user = Auth::user();

        return new UserResource($user);

    }

    public function update(Request $request, $id){

        switch ($id) {
            case 'driver_status':
                $statusDecode = [
                    "En revision",
                    'In activo',
                    'Activo',
                    'In activo',
                    'Bloqueado',
                    'Eliminado'
                ];
                $userId = $request->user_id;
                $status = empty($request->status) ? 0 : $request->status;
                User::where("id", $userId)->update([
                    "status" => $statusDecode[$status], 
                    "userable_type" => Driver::class,
                ]);
                return response(null, 204);
                break;
      
            
            default:
                # code...
                break;
        }
        $userId = $id;
        $user = User::find($userId);
        $user->assignRole('Empresa');
        $user->assignRole('Administrador');
        //$user->userable_id = null;
        //$user->userable_type = null;
        $user->save();

        $company = new Company();
        $company->nombre_empresa = "nombre_empresa";
        $company->nombre_contacto = "nombre_contacto";
        $company->telefono = "telefono";
        $company->save();

        $user->userable_id = $company->id;
        $user->userable_type = Company::class;
        $user->save();
        
    }

    public function users_old_db($id = null){

        DB::beginTransaction();
        try{
            if(!hasDbConnection("mysql_old")){
                return;
            }
            $usus = DB::connection("mysql_old")->table("usuarios")
                ->leftJoin("customers", "usuarios.idusuarios", "=", "customers.idusuarios")
                ->where([
                    ["usuarios.email", '!=', "undefined"],
                    ["usuarios.celular", '!=', "null"],
                    ["usuarios.registro", '!=', "APPLE"],
                    ["usuarios.registro", '!=', "FACEBOOK"],
                ])
                ->groupBy("usuarios.idusuarios");
            if(!empty($id)){
                $usus = DB::connection("mysql_old")->table("usuarios")
                ->leftJoin("customers", "usuarios.idusuarios", "=", "customers.idusuarios")
                ->where([
                    ["usuarios.idusuarios", '=', $id],               
                ])
                ->groupBy("usuarios.idusuarios");
            }
            $usus = $usus->get(DB::raw("
                usuarios.idusuarios as id, 
                usuarios.email, 
                usuarios.password, 
                usuarios.nombre, 
                usuarios.apellidos, 
                usuarios.celular, 
                usuarios.celular as telefono,
                usuarios.url_foto, 
                usuarios.url_licencia,
                usuarios.tipo_idtipos_documento,
                usuarios.documento_entidad,
                usuarios.pais_idpais,
                usuarios.creado,
            CASE 
                when rol=1 then \"App\\\Models\\\Driver\"
                when rol=2 then \"App\\\Models\\\Customer\"
                when rol=4 then \"App\\\Models\\\Administrator\"
                when rol=5 then \"App\\\Models\\\Company\"
                when rol=7 then \"App\\\Models\\\Company\"
                when rol=8 then \"App\\\Models\\\Company\"
                else \"App\\\Models\\\Company\" 
                end as rol, 

            CASE 
                when estado in (0,1) then 'En revision' 
                when estado=2 then 'Activo' 
                when estado=3 then 'In activo' 
                when estado=4 then 'Bloqueado' 
                when estado=5 then 'Eliminado' 
                end as status,
            max(StripeCustomerId) as stripe_id"));
            return $usus;
            $cont = [];
            foreach ($usus as $key => $usuario) {
                switch ($usuario->rol) {
                    case "App\\Models\\Driver":
                        if(User::where("email", $usuario->email)->count()>0){
                            //return User::where("email", $usuario->email)->first();
                            break;
                        }
                        $driver = new Driver();
                        $driver->nombres = $usuario->nombre;
                        $driver->apellidos = $usuario->apellidos;
                        $driver->telefono = $usuario->telefono;
                        $driver->document_types_id = $usuario->tipo_idtipos_documento != 1 ? NULL : $usuario->tipo_idtipos_documento;
                        $driver->document_number = $usuario->documento_entidad;
                        $driver->save();

                        $user = new User();
                        $user->id = $usuario->id;
                        $user->email = $usuario->email;
                        $user->password = ($usuario->password);
                        $user->userable_id = $driver->id;
                        $user->userable_type = "App\Models\Driver";
                        $user->country_id = $usuario->pais_idpais;
                        $user->save();
                        $user->assignRole('Conductor');
                        if(!empty($usuario->url_licencia)){
                            // $path = $request->file('licencia')->store('´public/documentos');
                            $documento = new Document();
                            $documento->tipo = "LICENCIA";
                            $documento->url_foto = $usuario->url_licencia;
                            $documento->driver_id = $driver->id;
                            $documento->save();
                        }
                
                        break;
                    case "App\\Models\\Customer":
                        if(User::where("email", $usuario->email)->count()>0){
                            break;
                        }
                        $customer = new Customer();
                        $customer->nombres = $usuario->nombre;
                        $customer->apellidos = $usuario->apellidos;
                        $customer->telefono = $usuario->telefono;
                        $customer->save();
                        
                        $user = new User();
                        $user->id = $usuario->id;
                        $user->email = $usuario->email;
                        $user->password = ($usuario->password);
                        $user->userable_id = $customer->id;
                        $user->userable_type = "App\Models\Customer";
                        $user->save();

                        // Asignar un rol
                        $user->assignRole('Cliente');
                        break;
                    case "App\\Models\\Administrator":
                        $user = new User();
                        $user->id = $usuario->id;
                        $user->email = $usuario->email;
                        $user->password = ($usuario->password);
                        $user->userable_id = $usuario->id;
                        $user->userable_type = "App\Models\Administrator";
                        $user->save();
                        $user->assignRole('Administrador');
                        break;
                    case "App\\Models\\Company":
                        $company = new Company();
                        $company->nombre_empresa = $usuario->nombre;
                        $company->nombre_contacto = $usuario->apellidos;
                        $company->telefono = $usuario->telefono;
                        $company->save();
                
                        $user = new User();
                        $user->id = $usuario->id;
                        $user->email = $usuario->email;
                        $user->password = ($usuario->password);
                        $user->userable_id = $company->id;
                        $user->userable_type = "App\Models\Company";
                        $user->save();
                
                        // Asignar un rol
                        $user->assignRole('Empresa');
                        break;
                    
                    default:
                        $cont[$key] = $usuario->rol;
                        break;
                }  
            }
            DB::Commit();
            return "Tabla users sincronicada con usuarios";
            
        } catch (Exception $e) {
            DB::rollback();
            return $e->getMessage();
        }
    }
    public function resetPassword($token){

    }
    public function destroy($id){
        try {
            //code...
            $user = request()->user();
            $userDriver = User::find($id);
            if(!empty($userDriver)){

                Document::where("driver_id", $userDriver->userable_id)->delete();
                Driver::where([
                    ["id", "=", $userDriver->userable_id],
                    
                    ])->delete();
            }
            Transaction::where("user_id", $id)->delete();
            FcmToken::where("user_id", $id)->delete();
            User::where("id", $id)->delete();
            return response("Deleted", 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "error" => true,
                "msg" => $th->getMessage(),
            ], 422);
        }
    }
    
}
