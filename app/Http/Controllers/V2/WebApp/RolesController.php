<?php
namespace App\Http\Controllers\V2\WebApp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleCollection;
use App\Models\License;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = request()->user();
        if(!$user->hasRole("Administrador") || !$user->can('listar configuraciones')){
            return response()->json(['error' => [], "msg" => "No tiene permisos para realizar esta acción"], self::HTTP_LOCKED);
        }
        return new RoleCollection(Role::all());
    }
    public function show($id)
    {
        $user = request()->user();
        if(!$user->hasRole("Administrador") || !$user->can('listar configuraciones')){
            return response()->json(['error' => [], "msg" => "No tiene permisos para realizar esta acción"], self::HTTP_LOCKED);
        }
        switch ($id) {
            case 'permissions':
                $collection = Role::get();
                foreach ($collection as $key => $value) {
                    $collection[$key]->permissions = $value->permissions()->get(["name", "id"]);
                }
                return response()->json([
                    'data' => $collection,
                    'msg' => Constant::LOAD_ROLES,
                ]);
                break;
            case 'list':
                $text = request()->text;
                return response()->json([
                    'data' => Permission::where("name", "like", "%".$text."%")->orderBy("id", "DESC")->get(["name", "id"]),
                    'msg' => Constant::LOAD_ROLES,
                ]);
                break;
            default:
                # code...
                break;
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name'    => 'required|max:255|unique:roles,name',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
        }
        $name = $request->name;
        $role = Role::create(['name' => $name, 'guard_name' => 'api']);
        return response()->json([
            "error" => false,
            "msg" => "Role registrado exitosamente",
        ]);
    }

    public function update(Request $request, $id){
        $user = $request->user();
        if(!$user->hasRole("Administrador") || !$user->can('agregar configuracion')){
            return response()->json(['error' => [], "msg" => "No tiene permisos para realizar esta acción"], self::HTTP_LOCKED);
        }
        switch ($id) {
            case 'role':                
                $validator = Validator::make($request->all(), [
                    'role_id'    => 'required|max:255|exists:roles,id',
                    'name'    => 'required|max:255|unique:roles,name',
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                }
                $name = $request->name;
                $roleId = $request->role_id;

                $role = Role::where("id", $roleId)->update(['name' => $name, 'guard_name' => 'api']);
                return response(null, self::HTTP_NO_CONTENT);
                break;
            case 'permission':
                $validator = Validator::make($request->all(), [
                    'role_id'    => 'required|max:255|exists:roles,id',
                    //'permission_id'    => 'required|max:255|exists:permissions,id',
                    'name'    => 'required|max:255',
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                }
                $name = $request->name;
                $roleId = $request->role_id;
                $permissionId = $request->permission_id;
                if(empty($permissionId)){
                    $p = Permission::where([
                        ['name', "=", $name], 
                        ['guard_name', "=", 'api'],
                    ])->first();
                    if(!empty($p)){
                        $permissionId = $p->id;
                    }else{
                        $permissionId = Permission::create(['name' => $name, 'guard_name' => 'api'])->id;
                    }
                }
                $permission = Permission::find($permissionId);
                $role = Role::find($roleId);
                $role->givePermissionTo([
                    $permission->name,
                ]);
                return response(null, self::HTTP_NO_CONTENT);
                break;
            case 'remove_role_permission':
                $validator = Validator::make($request->all(), [
                    'role_id'    => 'required|max:255|exists:roles,id',
                    'permission_id'    => 'required|max:255|exists:permissions,id',
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
                }
                $roleId = $request->role_id;
                $permissionId = $request->permission_id;
                $role = Role::find($roleId);
                $permission = Permission::find($permissionId);
                $role->revokePermissionTo($permission->name);
                return response(null, self::HTTP_NO_CONTENT);
                break;
            default:
                # code...
                break;
        }
        
    }

    public function destroy($id){
        $user = request()->user();
        if(!$user->hasRole("Administrador") || !$user->can('eliminar configuraciones')){
            return response()->json(['error' => [], "msg" => "No tiene permisos para realizar esta acción"], self::HTTP_LOCKED);
        }
        $validator = Validator::make(['role_id' => $id], [
            'role_id'    => 'required|max:255|exists:roles,id',
            //'permission_id'    => 'required|max:255|exists:permissions,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), "msg" => array_values($validator->errors()->all())[0]], self::HTTP_LOCKED);
        }
        $roleId = $id;
        $role = Role::find($roleId);
        $role->delete();
        return response(null, self::HTTP_NO_CONTENT);
    }
}