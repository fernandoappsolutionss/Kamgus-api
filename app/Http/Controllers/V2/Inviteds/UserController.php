<?php

namespace App\Http\Controllers\V2\Inviteds;

use App\Classes\K_HelpersV1;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Customer;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) 
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) //continue_making_service
    {
        //redirect to app web to continue login and making a services
        

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //uploadTokenFCMInvitado
    {
        //
        DB::beginTransaction();
        try {
            
            $token = $request->token;
            $id_usuario = $request->id_usuario;
            $platform = $request->platform;
            $fcmtoken = FcmToken::firstOrNew([
                "user_id" => $id_usuario,
                
            ]);
            $fcmtoken->token = $token;
            $fcmtoken->platform = $platform;
            
            K_HelpersV1::getInstance()->registerFcmToken($id_usuario, $token, $platform);
            if( $fcmtoken->save() ){
                DB::commit();
                $response = array('error' => false, 'msg' => 'TOKEN OK');
                return response()->json( $response , self::HTTP_OK );
            }else{
                DB::rollBack();
                $response = array('error' => true, 'msg' => 'ERROR TOKEN' );
                return response()->json( $response , self::HTTP_OK );	
            } 
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            $response = array('error' => true, 'msg' => 'ERROR TOKEN' );
            return response()->json( $response , self::HTTP_OK );	
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

        // Asignar un rol
        $user->assignRole('Cliente');

        //enviar email de registro de conductor
    }
}
