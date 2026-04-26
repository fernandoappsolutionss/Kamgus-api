<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
     /**
     * update_any_profile (customer, driver, company).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateanyprofile(Request $request){

        //buscar el usuario autenticado
        $user = User::findOrFail(Auth::user()->id);

        //método para actualizar un cliente especifico
        if($user->roles[0]->name == 'Cliente' || $user->roles[0]->name == 'Administrador'){

            $rules = [
                'nombres'   => 'max:255',
                'apellidos' => 'max:255',
                'pais'      => 'max:10',
                'telefono'  => [
                    'unique:customers,telefono,' . $user->userable->id, 
                    'unique:drivers,telefono,', 
                    'unique:companies,telefono,',
                    'max:255'
                ],
                'direccion' => 'max:255',
                'email'     => 'email|unique:users,email,' . $user->id,
                'password'  => 'min:8|confirmed',
            ];
    
            $this->validate($request, $rules);

            //buscar el resto de información en su perfil
            $customer = Customer::findOrFail(Auth::user()->userable->id);

            //llenar los campos del usuario con el método fill
            $user->fill([
                'email' => $request->email,
            ]);

            //llenar los campos del usuario con el método fill
            $customer->fill($request->only(
                'nombres', 
                'apellidos', 
                'telefono', 
                'direccion')
            );
    
            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            if($request->has('pais')){
                $user->country_id = $request->pais;
            }
    
            if ($user->isClean() && $customer->isClean()) {
    
                return response()->json([
                    'msg' => 'No Hubo ningun cambio', 
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY
                ]);
    
            }
            
            $user->save();
            $customer->save();

            return response()->json([
                'msg' =>  $user->roles[0]->name == 'Cliente' ? Constant::UPDATE_CUSTOMER : Constant::UPDATE_ADMIN, 
                'data' => $user->load('userable', 'country')
            ]);

        }

        //método para actualizar una empresa
        if($user->roles[0]->name == 'Empresa'){

            $rules = [
                'nombre_empresa'  => 'max:255',
                'nombre_contacto' => 'max:255',
                'pais'            => 'max:10',
                'telefono'        => [
                    'unique:customers,telefono,', 
                    'unique:drivers,telefono,', 
                    'unique:companies,telefono,' . $user->userable->id,
                    'max:255'
                ],
                'direccion'       => 'max:255',
                'email'           => 'email|unique:users,email,' . $user->id,
                'password'        => 'min:8|confirmed',
            ];
    
            $this->validate($request, $rules);

            //buscar el resto de información en su perfil
            $company = Company::findOrFail(Auth::user()->userable->id);

            //llenar los campos del usuario con el método fill
            $user->fill(['email' => $request->email]);

            //llenar los campos del usuario con el método fill
            $company->fill($request->only(
                'nombre_empresa', 
                'nombre_contacto', 
                'telefono', 
                'direccion'
            ));
    
            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            if($request->has('pais')){
                $user->country_id = $request->pais;
            }
    
            if ($user->isClean() && $company->isClean()) {
    
                return response()->json([
                    'msg' => 'No Hubo ningun cambio', 
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY
                ]);
    
            }
    
            $user->save();
            $company->save();

            return response()->json([
                'msg'  => Constant::UPDATE_COMPANY, 
                'data' =>  $user->load('userable', 'country')
            ]);


        }

        //método para actualizar un conductor
        if($user->roles[0]->name == 'Conductor'){

            $rules = [
                'nombres'           => 'max:255',
                'apellidos'         => 'max:255',
                'pais'              => 'max:10',
                'telefono'  => [
                    'unique:customers,telefono,', 
                    'unique:drivers,telefono,'   . $user->userable->id, 
                    'unique:companies,telefono,',
                    'max:255'
                ],
                'tipo_documento_id' => 'max:255',
                'numero_documento'  => 'max:255',
                'email'             => 'email|unique:users,email,' . $user->id,
                'password'          => 'min:8|confirmed',
            ];
    
            $this->validate($request, $rules);

            //buscar el resto de información en su perfil
            $driver = Driver::findOrFail($user->userable->id);

            //llenar los campos del usuario con el método fill
            $user->fill($request->only('email'));

            //llenar los campos del usuario con el método fill
            $driver->fill([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'document_types_id' => $request->tipo_documento_id,
                'document_number' => $request->numero_documento
            ]);
    
            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            if($request->has('pais')){
                $user->country_id = $request->pais;
            }
    
            if ($user->isClean() && $driver->isClean()) {
    
                return response()->json([
                    'msg' => 'No Hubo ningun cambio', 
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY
                ]);
    
            }
    
            $user->save();
            $driver->save();

            return response()->json([
                'msg' => Constant::UPDATE_DRIVER, 
                'data' => $user->load('userable', 'country')
            ]);

        }
        
    }

    function updateanyprofileimg(Request $request){

        $user = Auth::user();

        if($user->userable->url_foto_perfil == null){
            //recibir imagen
            if($request->file('image')){
                $file = $request->file('image');
                $path = public_path() . '/profile/images'; 
                $name = time() . $file->getClientOriginalName();
                $file->move($path, $name);
            }

            $user->userable->url_foto_perfil = $name;
            $user->userable->save();

            return response()->json([
                'msg' => Constant::UPDATE_PROFILE_IMAGE, 
                'img' => $user->userable->url_foto_perfil
            ]);

        }else{

            $path = public_path() . '/profile/images/'. $user->userable->url_foto_perfil;
            unlink($path);

            $user->userable->url_foto_perfil = '';
            $user->userable->save();

            // recibir imagen
            if($request->file('image')){
                $file = $request->file('image');
                $path = public_path() . '/profile/images'; 
                $name = time() . $file->getClientOriginalName();
                $file->move($path, $name);
            }

            $user->userable->url_foto_perfil = $name;
            $user->userable->save();

            return response()->json([
                'msg' => Constant::UPDATE_PROFILE_IMAGE, 
                'img' => $user->userable->url_foto_perfil
            ]);

        }
        
        

    }

}
