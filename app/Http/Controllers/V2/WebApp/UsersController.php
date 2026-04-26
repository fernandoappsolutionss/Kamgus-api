<?php

namespace App\Http\Controllers\V2\WebApp;

use App\Classes\K_HelpersV1;
use App\Http\Controllers\Controller;
use App\Mail\NewCompany;
use App\Mail\NewDriver;
use App\Models\Company;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Driver;
use App\Models\DriverVehicle;
use App\Models\Image;
use App\Models\LicenseUser;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\CompanyRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Stripe\Transfer;

class UsersController extends Controller
{
    const CACHE_TIME = 1; //in minutes
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //getUsers
    {
        //
        $page = request()->page;
        $count = request()->count;
        $user = request()->user();
        if (!$user->can('listar usuarios')) {
            return response(null, self::HTTP_UNAUTHORIZED);
        };
        //Cache::forget(request()->getMethod().'__users');
        //Cache::forget(request()->getMethod().'__users');
        //$userInfo = Cache::remember(request()->getMethod().'__users_'.$page."_".$count, Carbon::now()->addMinutes(self::CACHE_TIME), function () use($page, $count) { //Cacheo el resultado de la consulta. Se mantendra en cache por un tiempo especificado            
        $userInfo = DB::table("users as U")
            ->leftJoin("countries as CS", "CS.id", "=", "U.country_id")
            ->orderBy("U.id", "DESC")
            //->join("model_has_roles as MHR", "MHR.model_type", "=", "U.userable_id")
            //->leftJoin("roles as R", "MHR.role_id", "=", "R.id")
        ;
        $totalUsers = $userInfo->count();
        if (isset($page) && isset($count)) {
            $userInfo = $userInfo
                //->offset(($page) * $count)
                ->offset($page)
                ->limit($count);
        }
        $userInfo = $userInfo->get($this->getUserInfoFields());
        foreach ($userInfo as $key => $user) {
            //$userInfo[$key] = $this->getMappedUser($user);
            //id_plan
            //"D.nombres AS nombre", //Mapear en bucle foreach
            //"D.apellidos", //Mapear en bucle foreach
            //"D.telefono AS celular", //Mapear en bucle foreach
            //"D.document_types_id AS tipo_idtipos_documento", //Mapear en bucle foreach
            //"D.document_number AS documento_entidad", //Mapear en bucle foreach
            //"D.url_foto_perfil AS url_foto", //Mapear en bucle foreach
            $user = User::find($user->id);
            if (!$user->hasRole("Administrador")) {
                $userInfo[$key]->nombre = $user->userable->nombres;
                $userInfo[$key]->apellidos = $user->userable->apellidos;
                $userInfo[$key]->celular = $user->userable->telefono;
                $userInfo[$key]->tipo_idtipos_documento = $user->userable->document_types_id;
                $userInfo[$key]->documento_entidad = $user->userable->document_number;
                $userInfo[$key]->url_foto = $user->userable->url_foto_perfil;
            }
            $userInfo[$key]->pais_idpais = !empty($user->country) ? $user->country->name : null;
            if ($user->hasRole("Conductor")) {
                $docs = DB::table("documents")
                    ->select([
                        "driver_id",
                        DB::raw("SUM(CASE WHEN TIPO = 'CEDULA' THEN url_foto ELSE '' END) AS cedula"),
                        DB::raw("SUM(CASE WHEN TIPO = 'LICENCIA' THEN url_foto ELSE '' END) AS licencia")
                    ])
                    ->where("driver_id", $user->userable_id)
                    ->groupBy("driver_id")->first();
                if (!empty($docs)) {
                    $userInfo[$key]->cedula = $docs->cedula;
                    $userInfo[$key]->url_licencia = $docs->licencia;
                    $userInfo[$key]->balance = calculateDriverBalance($user->id, DB::table("transactions"));
                }
            }
            $auxRole = User::find($user->id)->getRoleNames();
            if (count($auxRole) > 0) {
                //userable_type
                $userInfo[$key]->rol = $auxRole[count($auxRole) - 1];
                $userInfo[$key]->id_rol = DB::table("roles")->where("name", $userInfo[$key]->rol)->first()->id;
            }

            if (empty($user->url_foto)) {
                $urlPhoto = Image::where([
                    ["imageable_id", "=", $user->idusuarios],
                    ["imageable_type", "=", $user->userable_type],
                    ["is", "=", "profile"],
                ])->first();
                $userInfo[$key]->url_foto = empty($urlPhoto) ? null : $urlPhoto->url;
            }
            $licenceData = Document::where([
                ["tipo", "=", "LICENCIA"],
                ["driver_id", "=", $user->userable_id],
            ])->first();
            if (!empty($licenceData)) {
                if (strpos($licenceData->url_foto, "http") !== false) {
                    $userInfo[$key]->url_licencia = $licenceData->url_foto;
                } else {
                    $userInfo[$key]->url_licencia = url("documentos" . "/" . ($licenceData->url_foto));
                }
            }
            $ceduleData = Document::where([
                ["tipo", "=", "CEDULA"],
                ["driver_id", "=", $user->userable_id],
            ])->first();
            if (!empty($ceduleData)) {
                if (strpos($ceduleData->url_foto, "http") !== false) {
                    $userInfo[$key]->url_cedula = $ceduleData->url_foto;
                } else {
                    $userInfo[$key]->url_cedula = url("documentos" . "/" . ($ceduleData->url_foto));
                }
            }
        }
        //return $userInfo;
        //});
        $response = array('error' => false, 'msg' => 'Usuarios encontrados', 'users' => $userInfo, 'page' => [
            "has_more" => $totalUsers > ($page + $userInfo->count()),
        ]);
        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, CompanyRepositoryInterface $companyRepository) //userRegister
    {
        $user = $request->user();
        if (!$user->can('crear usuarios')) {
            return response(null, self::HTTP_UNAUTHORIZED);
        };
        $registroForm = (object)$request->RegistroForm;
        
        $validator = Validator::make($request->all(), [
            //'user'    => 'required|max:255|exists:users,email',
            //'idusuario'    => 'required',
            'RegistroForm.email'    => 'required|email|unique:users,email|max:255',
            'RegistroForm.nombres' => 'required|max:255',
            'RegistroForm.password'    => 'required|min:8',
            'RegistroForm.apellidos'    => 'required|max:255',
            //'documento_entidad'    => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
        }
        DB::beginTransaction();
        try {
            switch (Role::find($registroForm->rol)->name) {
                case 'Administrador':
                    if(!$user->hasRole("Administrador")){
                        return response()->json(['error' => true, "msg" => "No puedes crear un administrador."], self::HTTP_LOCKED);
                    }
                    $response = $this->registerUserAdmin($registroForm, $companyRepository);
                    DB::commit();
                    return $response;
                    break;
                case 'Conductor':
                    $response = $this->registerUserDriver($request);  
                    DB::commit();
                    return $response;
                    break;
                case 'Empresa':
                    $registroForm->nombre_empresa = $registroForm->nombre;
                    $registroForm->nombre_contacto = $registroForm->apellidos;
                    //$registroForm->telefono = $registroForm->telefono;
                    $response = $this->registerUserEnterprise($registroForm);                
                    DB::commit();
                    return $response;
                    break;
                case 'Cliente':
                    $response = $this->registerUserClient($registroForm);
                    DB::commit();
                    return $response;
                    break;
                default: //Cliente
                    return response()->json(['error' => true, "msg" => "Role invalido."], self::HTTP_LOCKED);
                    break;
            }
        } catch (\Throwable $e) {
            //throw $th;
            DB::rollBack();
            $er = array('text' => 'Error al registrar el cliente, error: A0006', 'error' => $e->getMessage(), "line" => $e->getTrace());
            return response()->json($er, self::HTTP_NOT_FOUND);
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
        switch ($id) {
            case 'states': //Devuelve los estados que puede tener un un usuario registrado.
                $response = array('error' => false, 'msg' => 'Cargando estados', 'estados' => $this->getStatus());
                return response()->json($response);
                break;
            case 'roles':
                $response = array('error' => false, 'msg' => 'Cargando roles', 'roles' => DB::table("roles")->get(["id as id_rol", "name as rol"]));
                return response()->json($response);
                break;
            case 'check_email_or_phone':
                $dataId = request()->id;
                $existUser = User::where("email", $dataId)->orWhereRaw('userable_id in (select id from drivers where telefono = ?)', [$dataId])->count() > 0;

                $response = array('error' => false, 'msg' => 'Email o Celular existente', 'users' => $existUser);
                return response()->json($response);
                break;
            case 'payouts':
                $resumed = request()->resum;
                $payouts = $this->getPayoutsUsers(!empty($resumed) && $resumed == 1);
                $response = array('error' => false, 'msg' => 'Pago a conductores', 'payouts' => $payouts);
                return response()->json($response);
                break;
            default:

                break;
        }
    }
    //Obtener información de un usuario especificado y sus vehiculos asociados
    public function getUserById()
    {
        request()->validate([
            "id" => "required|exists:users,id"
        ]);
        $user = User::find(request()->id);
        $driverId = $user->userable_id;
        $userable = !$user->hasRole("Administrador") ? $this->getUserMapped($user) : $user;

        $userable["id_rol"] = $user->roles->pluck('id')[0];
        $userable["pais_idpais"] = $user->country_id; //Pais del usuario registrado
        $userable["id_estado"] = $user->status;
        $license = DB::table("license_user")->where("user_id", $user->id)->first();
        if($user->hasRole("Conductor")){
            $driverLicence = Document::where([
                ["driver_id", "=", $driverId],
                ["tipo", "=", "LICENCIA"],
            ])->first();
            $userable["url_licencia"] = empty($driverLicence) ? null : url("documentos/".$driverLicence->url_foto);
            $driverCardId = Document::where([
                ["driver_id", "=", $driverId],
                ["tipo", "=", "CEDULA"],
            ])->first();
            $userable["url_cedula"] = empty($driverCardId) ? null : url("documentos/".$driverCardId->url_foto);

        }
        $userable["id_plan"] = empty($license) ? null : $license->license_id;
        //dd($driverId);
        $vehicles = DB::table("driver_vehicles as DV")
            ->where("DV.driver_id", $driverId)
            ->leftJoin("models as Mo", "Mo.id", "=", "DV.model_id")
            //->leftJoin("marks as Ma", "Ma.id", "=", "Mo.mark_id")
            ->leftJoin("types_transports as TT", "TT.id", "=", "DV.types_transport_id")
            ->get($this->getVehicleResponseFields());
        //if (count($vehicles) <= 0) {
        //    $response = array('error' => true, 'msg' => 'Error cargando vehiculos');
        //    return response()->json($response, self::HTTP_OK);
        //}
        foreach ($vehicles as $key => $vehicle) {
            $vehicles[$key]->url_izquierda = "";
            $vehicles[$key]->url_foto = $this->getVehicleUrlImage($vehicle->key, 'photo_url_vehicle');
            $vehicles[$key]->url_trasera = $this->getVehicleUrlImage($vehicle->key, 'back_url_vehicle');
            $vehicles[$key]->url_derecha = $this->getVehicleUrlImage($vehicle->key, 'right_url_vehicle');
            $vehicles[$key]->url_propiedad = $this->getVehicleUrlImage($vehicle->key, 'property_url_vehicle');
            $vehicles[$key]->url_revisado = $this->getVehicleUrlImage($vehicle->key, 'revised_url_vehicle');
            $vehicles[$key]->url_poliza = $this->getVehicleUrlImage($vehicle->key, 'policy_url_vehicle');
        }
        $response = array('error' => false, 'msg' => 'Información del Usuario', 'users' => [$userable], 'userv' => $vehicles);
        return response($response);
    }
    public function search($id = null)
    { //getUserSearch
        $page = request()->page;
        $count = request()->count;
        $id = empty($id) ? request()->id : $id;

        $customers = Customer::where("nombres", "like", "%" . $id . "%")
            ->orWhere("apellidos", "like", "%" . $id . "%")
            ->orWhere("telefono", "like", "%" . $id . "%")->get("id")->pluck("id");
        $companies = Company::where("nombre_empresa", "like", "%" . $id . "%")
            ->orWhere("nombre_contacto", "like", "%" . $id . "%")
            ->orWhere("telefono", "like", "%" . $id . "%")->get("id")->pluck("id");
        $drivers = Driver::where("nombres", "like", "%" . $id . "%")
            ->orWhere("apellidos", "like", "%" . $id . "%")
            ->orWhere("telefono", "like", "%" . $id . "%")->get("id")->pluck("id");
        $userInfo = DB::table("users as U")

            ->leftJoin("countries as CS", "CS.id", "=", "U.country_id")
            //->join("model_has_roles as MHR", "MHR.model_type", "=", "U.userable_id")
            //->leftJoin("roles as R", "MHR.role_id", "=", "R.id")
            //->where("SU.nombres", "like", "%".$id."%")
            //->orWhere("SU.apellidos", "like", "%".$id."%")
            //->orWhere("SU.telefono", "like", "%".$id."%")
            ->where("U.email", "like", "%" . $id . "%")
            ->orWhere(function ($query) use ($drivers) {
                $query->where('userable_type', '=', Driver::class)
                    ->whereIn('userable_id', $drivers);
            })
            ->orWhere(function ($query) use ($customers) {
                $query->where('userable_type', '=', Customer::class)
                    ->whereIn('userable_id', $customers);
            })
            ->orWhere(function ($query) use ($companies) {
                $query->where('userable_type', '=', Company::class)
                    ->whereIn('userable_id', $companies);
            });
        $totalUsers = $userInfo->count();
        if (isset($page) && isset($count)) {
            $userInfo = $userInfo
                //->offset(($page) * $count)
                ->offset($page)
                ->limit($count);
        }
        $userInfo = $userInfo
            ->orderBy("U.id", "DESC")->get($this->getUserInfoFields());
        foreach ($userInfo as $key => $user) {
            $user = User::find($user->id);
            if (!$user->hasRole("Administrador")) {
                $userInfo[$key]->nombre = $user->userable->nombres;
                $userInfo[$key]->apellidos = $user->userable->apellidos;
                $userInfo[$key]->celular = $user->userable->telefono;
                $userInfo[$key]->tipo_idtipos_documento = $user->userable->document_types_id;
                $userInfo[$key]->documento_entidad = $user->userable->document_number;
                $userInfo[$key]->url_foto = $user->userable->url_foto_perfil;
            }
            $userInfo[$key]->pais_idpais = !empty($user->country) ? $user->country->name : null;
            if ($user->hasRole("Conductor")) {
                $docs = DB::table("documents")
                    ->select([
                        "driver_id",
                        DB::raw("SUM(CASE WHEN TIPO = 'CEDULA' THEN url_foto ELSE '' END) AS cedula"),
                        DB::raw("SUM(CASE WHEN TIPO = 'LICENCIA' THEN url_foto ELSE '' END) AS licencia")
                    ])
                    ->where("driver_id", $user->userable_id)
                    ->groupBy("driver_id")->first();
                if (!empty($docs)) {
                    $userInfo[$key]->cedula = $docs->cedula;
                    $userInfo[$key]->url_licencia = $docs->licencia;
                    $userInfo[$key]->balance = calculateDriverBalance($user->id, DB::table("transactions"));
                }
            }
            $auxRole = User::find($user->id)->getRoleNames();
            if (count($auxRole) > 0) {
                //userable_type
                $userInfo[$key]->rol = $auxRole[count($auxRole) - 1];
                $userInfo[$key]->id_rol = DB::table("roles")->where("name", $userInfo[$key]->rol)->first()->id;
            }
            if (empty($user->url_foto)) {
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
                if (strpos($licenceData->url_foto, "http") !== false) {
                    $userInfo[$key]->url_licencia = $licenceData->url_foto;
                } else {
                    $userInfo[$key]->url_licencia = url("documentos" . "/" . ($licenceData->url_foto));
                }
            }
            $ceduleData = Document::where([
                ["tipo", "=", "CEDULA"],
                ["driver_id", "=", $user->userable_id],
            ])->first();
            if (!empty($ceduleData)) {
                if (strpos($ceduleData->url_foto, "http") !== false) {
                    $userInfo[$key]->url_cedula = $ceduleData->url_foto;
                } else {
                    $userInfo[$key]->url_cedula = url("documentos" . "/" . ($ceduleData->url_foto));
                }
            }
        }
        $response = array('error' => false, 'msg' => 'Información del Usuario', 'users' => $userInfo, 'page' => [
            "has_more" => $totalUsers > ($page + $userInfo->count()),
        ]);
        return response()->json($response);
    }
    public function searchByDate($id = null)
    { //getUserSearchDate
        $page = request()->page;
        $count = request()->count;
        $datedesde = request()->datedesde;
        $datehasta = request()->datehasta;
        $estatus = request()->estatus;
        $rol = request()->rol;
        $limit = empty(request()->count) ? "10" : request()->count;
        $offset = empty(request()->page) ? "0" : ((request()->page));

        //$id = empty($id) ? request()->id : $id;

        $userInfo = User::leftJoin("countries as CS", "CS.id", "=", "users.country_id")
            //->join("model_has_roles as MHR", "MHR.model_type", "=", "users.userable_id")
            //->leftJoin("roles as R", "MHR.role_id", "=", "R.id")

        ;
        if (isset($page) && isset($count)) {
            $userInfo = $userInfo
                //->offset(($page) * $count)
                ->offset($page)
                ->limit($count);
        }
        if (!empty($datedesde) && !empty($datehasta) && ($datedesde !== "null" && $datehasta !== "null") && ($datedesde !== "undefined" && $datehasta !== "undefined")) {
            $userInfo = $userInfo
                ->whereBetween('users.created_at', [$datedesde, $datehasta]);
        }
        /*
            if( !empty($estatus) && !empty($rol)){
                $userInfo = $userInfo
                    ->where(function (Builder $query) use ($estatus, $rol) {
                        $query->role(is_numeric($rol) ? Role::find($rol)->name : $rol)->orWhere([
                            ["users.statuss", "=", $estatus],
                        ])
                        ;
                    })
                    
                    ;
            }
            */
        if (!empty($rol) && ($rol !== "null" && $rol !== "undefined")) {
            $userInfo = $userInfo->role(is_numeric($rol) ? Role::find($rol)->name : $rol);
        }
        if (!empty($estatus) &&  ($estatus !== "null" && $estatus !== "undefined")) {
            $userInfo = $userInfo->where([
                ["users.status", "=", $estatus],
            ]);
        }
        $totalUsers = $userInfo->count();
        $userInfo = $userInfo
            ->limit($limit)
            ->offset($offset)
            ->get($this->getUserInfoFields2());
        //return $userInfo;
        foreach ($userInfo as $key => $user) {
            $user = User::find($user->id);
            if (!$user->hasRole("Administrador")) {
                $userInfo[$key]->nombre = $user->userable->nombres;
                $userInfo[$key]->apellidos = $user->userable->apellidos;
                $userInfo[$key]->celular = $user->userable->telefono;
                $userInfo[$key]->tipo_idtipos_documento = $user->userable->document_types_id;
                $userInfo[$key]->documento_entidad = $user->userable->document_number;
                $userInfo[$key]->url_foto = $user->userable->url_foto_perfil;
            }
            $userInfo[$key]->pais_idpais = !empty($user->country) ? $user->country->name : null;
            if ($user->hasRole("Conductor")) {
                $docs = DB::table("documents")
                    ->select([
                        "driver_id",
                        DB::raw("SUM(CASE WHEN TIPO = 'CEDULA' THEN url_foto ELSE '' END) AS cedula"),
                        DB::raw("SUM(CASE WHEN TIPO = 'LICENCIA' THEN url_foto ELSE '' END) AS licencia")
                    ])
                    ->where("driver_id", $user->userable_id)
                    ->groupBy("driver_id")->first();
                if (!empty($docs)) {
                    $userInfo[$key]->cedula = $docs->cedula;
                    $userInfo[$key]->url_licencia = $docs->licencia;
                    $userInfo[$key]->balance = calculateDriverBalance($user->id, DB::table("transactions"));
                }
            }
            $auxRole = User::find($user->id)->getRoleNames();
            if (count($auxRole) > 0) {
                //userable_type
                $userInfo[$key]->n_roles = count($auxRole);
                $userInfo[$key]->rol = $auxRole[count($auxRole) - 1];
                $userInfo[$key]->id_rol = DB::table("roles")->where("name", $userInfo[$key]->rol)->first()->id;
            }
            if (empty($user->url_foto)) {
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
                if (strpos($licenceData->url_foto, "http") !== false) {
                    $userInfo[$key]->url_licencia = $licenceData->url_foto;
                } else {
                    $userInfo[$key]->url_licencia = url("documentos" . "/" . ($licenceData->url_foto));
                }
            }
            $ceduleData = Document::where([
                ["tipo", "=", "CEDULA"],
                ["driver_id", "=", $user->userable_id],
            ])->first();
            if (!empty($ceduleData)) {
                if (strpos($ceduleData->url_foto, "http") !== false) {
                    $userInfo[$key]->url_cedula = $ceduleData->url_foto;
                } else {
                    $userInfo[$key]->url_cedula = url("documentos" . "/" . ($ceduleData->url_foto));
                }
            }
        }
        $response = array('error' => false, 'msg' => 'Información del Usuario', 'users' => $userInfo, 'page' => [
            "has_more" => $totalUsers > ($offset + $userInfo->count()),
        ]);
        return response()->json($response);
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
        //
        $user = $request->user();
        if (!$user->can('actualizar usuarios')) {
            return response(null, self::HTTP_UNAUTHORIZED);
        };
        DB::beginTransaction();
        try {
            //code...\
            switch ($id) {
                case 'profile': //updateProfile
                    $registroForm = (object)$request->RegistroForm;
                    $validator = Validator::make($request->RegistroForm, [
                        'idusuario'    => 'required|exists:users,id',
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                    }
                    $idusuario = $registroForm->idusuario;
                    $password = $registroForm->password;
                    $nombre = $registroForm->nombre;
                    $email = $registroForm->email;
                    $celular = $registroForm->telefono;
                    $direccion = $registroForm->ciudad;
                    $user = User::find($idusuario);
                    //if ($user->hasRole("Conductor") && round(calculateDriverBalance($idusuario, DB::table("transactions")), 2) < Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision) {
                      //  return response()->json(['error' => [], "msg" => "El usuario tiene balance insuficiente"], self::HTTP_LOCKED);
                    //}
                    $licence = LicenseUser::firstOrNew(["user_id" => $idusuario]);
                    $licence->license_id = $registroForm->id_licence;
                    $licence->save();
                    if (!$user->hasRole('Administrador')) {
                        $userable = $user->userable;
                        $userable->telefono = $celular;
                        $userable->nombres = $nombre;
                        $userable->direccion = $direccion;
                        $userable->save();
                    }
                    $user->status = $registroForm->estado;
                    if (!empty($registroForm->pais_id)) {
                        $user->country_id = $registroForm->pais_id;
                    }
                    $user->email = $email;
                    if (!empty($password)) {
                        $user->password = Hash::make($password);
                    }
                    $user->save();
                    $response = array('error' => false, 'msg' => 'Perfil actualizado', 'user' => $this->getUserMapped($user));
                    DB::commit();
                    return response()->json($response);
                    break;
                case 'vehicles':
                    $vehicles = $request->vehicles;
                    if(empty($vehicles)){
                        return response(null, self::HTTP_NO_CONTENT);
                    }
                    foreach ($vehicles as $key => $vehicle) {
                        //"DV.long as largo", //"100",
                        $mVehicle = DriverVehicle::find($vehicle["id"]);
                        $mVehicle->height = $vehicle["alto"];
                        $mVehicle->wide = $vehicle["ancho"];
                        $mVehicle->types_transport_id = $vehicle["categoria"];
                        $mVehicle->status = $vehicle["estado"];
                        $mVehicle->long = $vehicle["lado"];
                        $mVehicle->model_id = $vehicle["marcas_id"];
                        $mVehicle->plate = $vehicle["placa"];
                        $mVehicle->save();
                    }
                    DB::commit();
                    return response(null, self::HTTP_NO_CONTENT);
                    
                    
                    
                    
                    
                    break;
                case 'block': //blockUser
                    $validator = Validator::make($request->all(), [
                        'idusuario'    => 'required|exists:users,id',
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                    }
                    $userId = $request->idusuario;
                    $user = User::where("id", $userId)->update(
                        [
                            "status" => "Bloqueado",
                        ]
                    );
                    $response = array('error' => false, 'msg' => 'Usuario bloqueado');
                    DB::commit();
                    return response()->json($response, self::HTTP_NO_CONTENT);
                    break;
                case 'info': //updateUser
                    $validator = Validator::make($request->all(), [
                        'idusuario'    => 'required|exists:users,id',
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                    }
                    $idusuario = $request->idusuario;
                    $password = $request->password;
                    $nombre = $request->nombre;
                    $email = $request->email;
                    $celular = $request->celular;
                    $estado = $request->estado;
                    $user = User::find($idusuario);
                    //return $this->getUserMapped($user);
                    if (!$user->hasRole('Administrador')) {
                        $userable = $user->userable;
                        $userable->telefono = $celular;
                        $userable->nombres = $nombre;
                    }
                    $user->email = $email;
                    $user->status = $estado;
                    if (!empty($request->role)) {
                        switch ($request->role) {
                            case '1':
                                //$lastRole = $user->getRoleNames();
                                //$lastRole = count($lastRole) > 0 ? $lastRole[count($lastRole) - 1] : null;
                                //$user->removeRole($lastRole);
                                $user->syncRoles([]);
                                $user->assignRole("Conductor");
                                # code...
                                break;
                            case '2':
                                //$lastRole = $user->getRoleNames();
                                //$lastRole = count($lastRole) > 0 ? $lastRole[count($lastRole) - 1] : null;
                                //$user->removeRole($lastRole);
                                $user->syncRoles([]);
                                $user->assignRole(["Cliente"]);
                                # code...
                                break;
                            case '5':
                                //$lastRole = $user->getRoleNames();
                                //$lastRole = count($lastRole) > 0 ? $lastRole[count($lastRole) - 1] : null;
                                //$user->removeRole($lastRole);
                                $user->syncRoles([]);
                                $user->assignRole("Empresa");
                                # code...
                                break;
                            case '4':
                                //$lastRole = $user->getRoleNames();
                                //$lastRole = count($lastRole) > 0 ? $lastRole[count($lastRole) - 1] : null;
                                //$user->removeRole($lastRole);
                                $user->syncRoles([]);
                                $user->assignRole("Administrador");
                                # code...
                                break;

                            default:
                                # code...
                                break;
                        }
                    }
                    if (!empty($password)) {
                        $user->password = Hash::make($password);
                    }
                    $user->save();
                    $response = array('error' => false, 'msg' => 'Usuario actualizado', 'user' => $this->getUserMapped($user));
                    DB::commit();
                    return response()->json($response);
                    break;

                case 'deposit':
                    # code...
                    if(!$request->user()->can("listar pagos")){
                        return response()->json([
                            'msg' => 'No tiene permisos para realizar esta acción',
                            'code' => self::HTTP_UNAUTHORIZED,
                        ], self::HTTP_UNAUTHORIZED);
                    }
                    $validator = Validator::make($request->all(), [
                        "user_id" => 'required|exists:users,id',
                        "amount" => 'required|numeric',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'error' => $validator->errors(), 
                            "msg" => array_values($validator->errors()->all())[0]
                        ], self::HTTP_LOCKED);
                    }
                    $result = $this->registerUserTransfer($request->user_id, $request->amount);
                    DB::commit();
                    return response(null, self::HTTP_NO_CONTENT);


                    break;
                case 'take_out':
                    if(!$request->user()->can("listar pagos")){
                        return response()->json([
                            'msg' => 'No tiene permisos para realizar esta acción',
                            'code' => self::HTTP_UNAUTHORIZED,
                        ], self::HTTP_UNAUTHORIZED);
                    }
                    $validator = Validator::make($request->all(), [
                        "user_id" => 'required|exists:users,id',
                        "amount" => 'required|numeric',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'error' => $validator->errors(), 
                            "msg" => array_values($validator->errors()->all())[0]
                        ], self::HTTP_LOCKED);
                    }
                    $result = $this->registerUserTransfer($request->user_id, (-1 * abs($request->amount)));
                    DB::commit();

                    return response(null, self::HTTP_NO_CONTENT);
                    break;
                    default:
                    # code...
                    break;
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response([
                "error" => true, 
                "msg" => $th->getMessage(), 
                "line" => $th->getLine(),
                "track" => $th->getTrace(),
            ], self::HTTP_BAD_REQUEST);
        }
    }
    public function updateImage(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->can('actualizar usuarios')) {
            return response(null, self::HTTP_UNAUTHORIZED);
        };
        switch ($id) {
            case 'licence': //Actualiza la foto de licencia de un conductor especificado.
                # code...
                if ($request->hasFile("licencia_foto")) { //Valida si es enviado el archivo

                    $licenceData = Document::firstOrNew([
                        "tipo" => "LICENCIA",
                        "driver_id" => User::find($request->idusuario)->userable->id,
                    ]);
                    if (!empty($licenceData) && !empty($licenceData->url_foto)) {
                        $url_licencia_foto = $licenceData->url_foto;
                    }
                    $response = $this->uploadADriverFile($request->file('licencia_foto'), 'licencia_foto', 'LICENCIA');    //Función para carga de imagen
                    if (empty($response)) {
                        return false;
                        return response()->json($response, self::HTTP_OK);
                    }
                    $url_licencia_foto = $response;
                    $changeSomeImage = true;
                    $licenceData->url_foto = $url_licencia_foto;
                    $licenceData->save();
                    if ($changeSomeImage) {
                        $user->status = "In activo";
                        $user->save();
                    }
                    return response()->json([], self::HTTP_NO_CONTENT);
                }
                break;
                
            case 'cedula': //Actualiza la foto de cedula de un conductor especificado.
                # code...
                if ($request->hasFile("cedula_foto")) { //Valida si es enviado el archivo
                    $ceduleData = Document::firstOrNew([
                        "tipo" => "CEDULA",
                        "driver_id" => User::find($request->idusuario)->userable->id,
                    ]);
                    if (!empty($ceduleData) && !empty($ceduleData->url_foto)) {
                        $url_cedula_foto = $ceduleData->url_foto;
                    }
                    
                    $response = $this->uploadADriverFile($request->file('cedula_foto'), 'cedula_foto', 'CEDULA');    //Función para carga de imagen
                    if (empty($response)) {
                        return response()->json($response, self::HTTP_NOT_FOUND);
                        //return false;
                    }
                    $url_cedula_foto = $response;
                    $changeSomeImage = true;
                    
                    $ceduleData->url_foto = $url_cedula_foto;
                    $ceduleData->save();
                    if ($changeSomeImage) {
                        $user->status = "In activo";
                        $user->save();
                    }
                    return response()->json([], self::HTTP_NO_CONTENT);
                }
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
    public function getUserMapped($user)
    {
        if (!$user->hasRole('Administrador')) {
            $userable = $user->userable;
            return [
                "idusuarios" => $user->id,
                "nombre" => $userable->nombres,
                "email" => $user->email,
                "apellidos" => $userable->apellidos,
                "celular" => $userable->telefono,
                "tipo_idtipos_documento" => empty($userable->document_types_id) ? null : $userable->document_types_id,
                "documento_entidad" => empty($userable->document_number) ? null : $userable->document_number,
                "url_foto" => empty($userable->url_foto_perfil) ? null : $userable->url_foto_perfil,
                "codigounico" => $user->id,
            ];
        }
        return [
            "idusuarios" => $user->id,
            "email" => $user->email,
            "codigounico" => $user->id,
        ];
    }
    public function getStatus()
    {
        return User::select(["status as estado", "status as id_estado", DB::raw("CASE 
        WHEN
            status = 'En revision'
        THEN
            '#ee8600' 
        WHEN
            status = 'Activo'
        THEN
            '#7dce3c' 
        WHEN
            status = 'In activo'
        THEN
            '#696969' 
        WHEN
            status = 'Bloqueado'
        THEN
            '#e84b36' 
        ELSE
            '' 
    END AS 'color'")])->groupBy("status")->get();
        return [
            [
                "id_estado" => "1",
                "estado" => "En revisión",
                "color" => "#ee8600"
            ],
            [
                "id_estado" => "2",
                "estado" => "Activo",
                "color" => "#7dce3c"
            ],
            [
                "id_estado" => "3",
                "estado" => "In activo",
                "color" => "#696969"
            ],
            [
                "id_estado" => "4",
                "estado" => "Bloqueado",
                "color" => "#e84b36"
            ],
            [
                "id_estado" => "5",
                "estado" => "Deleted",
                "color" => ""
            ]
        ];
    }
    //Devuelve el conteo de conductores de un estado especificado. Por defecto devuelve los conductores activos    
    public function getCountDrivers(){
        $status = empty(request()->status) ? User::ACTIVO_STATUS : request()->status;
        $user = request()->user();

        if (!$user->can('listar usuarios')) {
            return response(null, self::HTTP_UNAUTHORIZED);
        };
        $userInfo = DB::table("users as U")
            ->leftJoin("countries as CS", "CS.id", "=", "U.country_id")
            ->where("U.status", $status)
            ->orderBy("U.id", "DESC")
            //->join("model_has_roles as MHR", "MHR.model_type", "=", "U.userable_id")
            //->leftJoin("roles as R", "MHR.role_id", "=", "R.id")
        ;
        $totalUsers = $userInfo->count();
        return response()->json([
            "error"=>false,
            "data" => [
                "count" => $totalUsers,
            ], 
        ]);
    }
    private function uploadADriverFile($file, $identif, $default = null)
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
    private function registerUserDriver($request){


        // return $request->file('cedula');
        $registroForm = empty($request->RegistroForm) ? $request : (object)$request->RegistroForm;
        $registroForm->indicativo = "";
        $driver = new Driver();
        $driver->nombres = $registroForm->nombres;
        $driver->apellidos = $registroForm->apellidos;
        $driver->telefono = $registroForm->telefono;
        $driver->direccion = $registroForm->ciudad;
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
            $user->id = K_HelpersV1::getInstance()->registerDriver(array_merge((array)$registroForm,[
                "url_foto" => null,
                "url_licencia" => $urlLicencia,
            ]));
        }
        
        $user->email = trim($registroForm->email);
        $user->password = Hash::make($request->password);
        $user->userable_id = $driver->id;
        $user->userable_type = "App\Models\Driver";
        $saved = $user->save();

        // Asignar un rol
        $user->assignRole('Conductor');

        //enviar email de registro de conductor
        Mail::to($user->email)->send( new NewDriver('Motivo', 'Mensaje') );
        if ($saved) {

            $params = [
                "license_id" => $registroForm->id_licence,
                "user_id" => $user->id,
                //"expiredAt" => ,
            ];
            //register user licence
            if (K_HelpersV1::ENABLE) {
                $params["id"] = K_HelpersV1::getInstance()->registerUserLicence($user->id, $registroForm->id_licence);
            }
            LicenseUser::create($params);
   
            //emailNotificar($datos, 'REGISTROCONDUCTOR');   
            $data = array(
                'id_user' => $user->id,
                'nameuser' => $registroForm->nombres . ' ' . $registroForm->apellidos,
                'celular' => $registroForm->indicativo . $registroForm->telefono,
                'msg' => 'Usuario ' . $registroForm->nombres . ' ' . $registroForm->apellidos . ' registrado con exito ',
            );
            
            return response()->json($data);
        }
        
        //return response()->json($data);

    }
    private function registerUserEnterprise($registroForm){
     
            //code...
            $celular = $registroForm->telefono;
            $registroForm->indicativo = "";
            if (Customer::where("telefono", $celular)->count() <= 0) {
                $company = new Company();
                $company->nombre_empresa = $registroForm->nombre_empresa;
                $company->nombre_contacto = $registroForm->nombre_contacto;
                $company->telefono = $registroForm->telefono;
                $company->direccion = $registroForm->ciudad;
                $company->save();
                
                $user = new User();
                if(K_HelpersV1::ENABLE){
                    //Optional. Usado para sincronizar el id del usuario en ambas bases de datos en el registro
                    $user->id = K_HelpersV1::getInstance()->registerEnterprise(array_merge($registroForm->all(),[
                        "nombres" => $registroForm->nombre_empresa,
                        "apellidos" => $registroForm->nombre_contacto,
                        "telefono" => $registroForm->telefono,
                        "email" => $registroForm->email,
                        "password" => $registroForm->password,
                    ]));
                }
                $user->email = $registroForm->email;
                $user->password = Hash::make($registroForm->password);
                $user->userable_id = $company->id;
                $user->userable_type = Company::class;
                $user->save();
        
        
                // Asignar un rol
                $user->assignRole('Empresa');

                //enviar el correo cuando se registre una empresa
                Mail::to($user->email)->send( new NewCompany('Motivo', 'Mensaje') );
                //return $user;
                $data = array(
                    'id_user' => $user->id,
                    'nameuser' => $registroForm->nombres . ' ' . $registroForm->apellidos,
                    'celular' => $registroForm->indicativo . $registroForm->telefono,
                    'msg' => 'Usuario ' . $registroForm->nombres . ' ' . $registroForm->apellidos . ' registrado con exito ',
                );
                
                return response()->json($data);
            }
            $er = array('text' => 'El correo o el número movil ya fue registrado.');
            return response()->json($er, self::HTTP_BAD_REQUEST);
        
       
    }
    private function registerUserClient($registroForm)
    {
        
            //code...
            $telefono = $registroForm->telefono;
            $registroForm->indicativo = "";
            if (Customer::where("telefono", $telefono)->count() <= 0) {
                /*
            pais_id',               $data['pais_id'], PDO::PARAM_INT);
            tipo_documento_id',     $data['tipo_documento_id'], PDO::PARAM_STR);
            documento_entidad',     $data['documento_entidad'], PDO::PARAM_STR);
            token',                 $token, PDO::PARAM_STR);
            codigo',                $codigo, PDO::PARAM_STR);
            rol',                   $data['rol'], PDO::PARAM_STR);
            */
                $customer = new Customer();
                $customer->nombres = $registroForm->nombre;
                $customer->apellidos = $registroForm->apellidos;
                $customer->telefono = $telefono;
                $customer->direccion = $registroForm->ciudad;
                $customer->save();
                $user = new User();
                if (K_HelpersV1::ENABLE) {
                    //Optional. Usado para sincronizar el id del usuario en ambas bases de datos en el registro
                    $params = [
                        "url_foto" => null,
                        "nombres" => $registroForm->nombre,
                        "telefono" => $registroForm->telefono,
                        "url_licencia" => "",
                    ];
                    $user->id = K_HelpersV1::getInstance()->registerCustomer(array_merge((array)$registroForm, $params));
                }

                $user->email = trim($registroForm->email);
                $user->password = md5($registroForm->password);
                $user->userable_id = $customer->id;
                $user->userable_type = Customer::class;
                $saved = $user->save();

                $user->assignRole('Cliente');
                if ($saved) {

                    $params = [
                        "license_id" => $registroForm->id_licence,
                        "user_id" => $user->id,
                        //"expiredAt" => ,
                    ];
                    //register user licence
                    if (K_HelpersV1::ENABLE) {
                        $params["id"] = K_HelpersV1::getInstance()->registerUserLicence($user->id, $registroForm->id_licence);
                    }
                    LicenseUser::create($params);
           
                    //emailNotificar($datos, 'REGISTROCONDUCTOR');   
                    $data = array(
                        'id_user' => $user->id,
                        'nameuser' => $registroForm->nombres . ' ' . $registroForm->apellidos,
                        'celular' => $registroForm->indicativo . $registroForm->telefono,
                        'msg' => 'Usuario ' . $registroForm->nombres . ' ' . $registroForm->apellidos . ' registrado con exito ',
                    );
                    
                    return response()->json($data);
                }
            }
            $er = array('text' => 'El correo o el número movil ya fue registrado.');
            return response()->json($er, self::HTTP_BAD_REQUEST);
       
    }
    private function registerUserAdmin($registroForm, $companyRepository)
    {
        $company = $companyRepository->create([
            "nombre_empresa" => $registroForm->nombres,
            "nombre_contacto" => $registroForm->apellidos,
            "telefono" => $registroForm->telefono,
            "direccion" => $registroForm->ciudad,
        ]);
        
        $user = new User();
        $user->email = $registroForm->email;
        $user->password = Hash::make($registroForm->password);
        $user->userable_id = $company->id;
        $user->userable_type = Company::class;
        $saved = $user->save();
        if ($saved) {
            // Asignar un rol
            $user->assignRole('Administrador');
            $data = array(
                'id_user' => $user->id,
                'nameuser' => $registroForm->nombres . ' ' . $registroForm->apellidos,
                'celular' => $registroForm->telefono,
                'msg' => 'Usuario ' . $registroForm->nombres . ' ' . $registroForm->apellidos . ' registrado con exito ',
            );
            
            return response()->json($data);
        }
    }

    private function getVehicleUrlImage($vehicleId, $type)
    {
        $image = Image::where([
            ["is", "=", $type],
            ["imageable_id", "=", $vehicleId],
            ["imageable_type", "=", DriverVehicle::class],
        ])->first();
        if (empty($image)) {
            return "";
        }
        return $image->url;
    }
    private function getUserInfoFields()
    {
        return [
            "U.id",
            "U.id AS idusuarios",
            "U.email",
            "CS.name AS ciudad",
            "U.created_at AS creado",
            DB::raw("CASE U.status 
                WHEN 'Activo' THEN 2
                WHEN 'In activo' THEN 1
                WHEN 'Bloqueado' THEN 3
                WHEN 'Eliminado' THEN 5
                ELSE 0
            END AS id_estado"),
            "U.status as estado",
            "U.id AS codigounico",
            "U.userable_id",
            "U.userable_type",
        ];
    }
    private function getUserInfoFields2()
    {
        return [
            "users.id",
            "users.id AS idusuarios",
            "users.email",
            "CS.name AS ciudad",
            "users.created_at AS creado",
            DB::raw("CASE users.status 
                WHEN 'Activo' THEN 2
                WHEN 'In activo' THEN 1
                WHEN 'Bloqueado' THEN 3
                WHEN 'Eliminado' THEN 5
                ELSE 0
            END AS id_estado"),
            "users.status as estado",
            "users.id AS codigounico",
            "users.userable_id",
            "users.userable_type",
        ];
    }
    private function getVehicleResponseFields()
    {
        return [
            "DV.id as idvehiculos", //"319",
            "DV.model_id as marcas_id", //"24",
            "DV.driver_id as conductores_id", //"2556",
            "DV.color_id", //"8",
            "DV.year as year_car", //"2015",
            "DV.plate as placa", //"EA0471",
            //"url_foto", //"http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_FRONTAL_photo.png",
            //"url_trasera", //"http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_TRASERA_photo.png",
            //"url_derecha", //"http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_LADO_photo.png",
            //"url_izquierda", //"",
            //"url_propiedad", //"http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_REGISTRO_photo.png",
            //"url_revisado", //"http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_REVISADO_photo.png",
            //"url_poliza", //"http://www.api.kamgus.com/profiles/conductores/2556_5272727_APP_SEGURO_photo.png",
            "DV.m3", //"300",
            "DV.height as altura", //"100",
            "DV.wide as ancho", //"100",
            "DV.long as largo", //"100",
            "DV.burden as carga", //"0",
            "TT.id as tipo_camion", //"2",
            "DV.created_at as creado", //"2022-12-15 22:28:43",
            "DV.status as estado", //"A",
            "Mo.name as nombre_marca", //"Chevrolet",
            "TT.nombre as nombre_camion", //"Pick up",
            "TT.foto as image_tipo", //"https://www.kamgus.com/images/20191213081238_photo.png",
            "DV.id as key", //"319"
        ];
    }
    private function getTransactionFields()
    {
        return [
            "id",
            "user_id",
            "service_id",
            "type",
            "amount",
            "tax",
            "accumulated_value",
            "currency",
            "transaction_id",
            "status",
            "gateway",
            "receipt_url",
            "description",
            "created_at",
            "updated_at",
        ];
    }
    private function getPayoutsUsers($resum = false)
    {
        if ($resum) {
            return Transaction::where([
                ["amount", "<", 0],
                //["status", "<", "succeeded"],
            ])->whereIn("status", Transaction::SUCCESS_STATES)
                ->whereNull("service_id")
                ->sum(Transaction::raw("total - tax"));
        }
        $payout = Transaction::where([
            ["amount", "<", 0],
            //["status", "<", "succeeded"],
        ])->whereIn("status", Transaction::SUCCESS_STATES)
            ->whereNull("service_id")
            ->get($this->getTransactionFields());
        return $payout;
    }
    private function registerUserTransfer($userId, $amount){
        $transaction = Transaction::create([
            "user_id" => $userId,
            "service_id" => null,
            "type" => "visa",
            "amount" => floatval($amount),
            "tax" => 0,
            "total" => floatval($amount),
            "currency" => "USD",
            "transaction_id" => "admin",
            "status" => "succeeded",
            "gateway" => Transaction::TRANSFERENCIA_TYPE,
            "receipt_url" => "",
            "created_at" => date("Y-m-d H:i:s"),
        ]);
        //dd($transaction);
        return $transaction->save();
    }
}
