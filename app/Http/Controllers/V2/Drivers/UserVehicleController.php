<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Classes\K_HelpersV1;
use App\Mail\PrimerVehiculoMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Models\Driver;
use App\Models\DriverVehicle;
use App\Models\Image;
use App\Models\Mark;
use App\Models\Model;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DB;

class UserVehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //getDriverVehicle
    {
        //
        $user = request()->user();
        //$user = User::find(2112);
        //$user = User::find(2556);
        //$user = User::find(2674);
        //$user = User::find(2869);
        //$user = User::find(2871); 
        //$user = User::find(2872);
        $driverId = $user->userable_id;

        //dd($driverId);
        $vehicles = DB::table("driver_vehicles as DV")
            ->where("DV.driver_id", $driverId)
            ->leftJoin("models as Mo", "Mo.id", "=", "DV.model_id")
            //->leftJoin("marks as Ma", "Ma.id", "=", "Mo.mark_id")
            ->leftJoin("types_transports as TT", "TT.id", "=", "DV.types_transport_id")
            ->get($this->getVehicleResponseFields());
        if (count($vehicles) <= 0) {
            $response = array('error' => true, 'msg' => 'Error cargando vehiculos');
            return response()->json($response, self::HTTP_OK);
        }
        foreach ($vehicles as $key => $vehicle) {
            $vehicles[$key]->url_izquierda = "";
            $vehicles[$key]->url_foto = $this->getVehicleUrlImage($vehicle->key, 'photo_url_vehicle');
            $vehicles[$key]->url_trasera = $this->getVehicleUrlImage($vehicle->key, 'back_url_vehicle');
            $vehicles[$key]->url_derecha = $this->getVehicleUrlImage($vehicle->key, 'right_url_vehicle');
            $vehicles[$key]->url_propiedad = $this->getVehicleUrlImage($vehicle->key, 'property_url_vehicle');
            $vehicles[$key]->url_revisado = $this->getVehicleUrlImage($vehicle->key, 'revised_url_vehicle');
            $vehicles[$key]->url_poliza = $this->getVehicleUrlImage($vehicle->key, 'policy_url_vehicle');
            
        }
        $response = array('error' => false, 'msg' => 'Cargando vehiculos', 'data' => $vehicles);
        return response()->json($response, self::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //setDriverVehicle
    {
        //
        $rules = [
            'placa'   => 'required',
            'tipo_camion'   => 'required|exists:types_transports,id',
            'marcas_id'   => 'required',
            'altura'   => 'required',
            'ancho' => 'required',
            'largo'      => 'required',
            'seguro' => 'required|image',
            'revisado' => 'required|image',
            'registro' => 'required|image',
            'frontal' => 'nullable|image',
            'lado' => 'nullable|image',
            'trasero' => 'nullable|image',
        ];

        $this->validate($request, $rules);
        try {
            //code...
            DB::beginTransaction();
            $placa = $request->placa;
            $tipoCamion = $request->tipo_camion;
            $marcasId = $request->marcas_id;
            $altura = $request->altura;
            $ancho = $request->ancho;
            $largo = $request->largo;

            $user = $request->user();
            $driver = Driver::find($user->userable_id);
            $url_frontal_foto = '';
            $url_lado_foto = '';
            $url_trasera_foto = '';

            $m3 = $altura * $ancho * $largo;
            $vehicleId = K_HelpersV1::getInstance()->setDriverVehicle($request->all(), $user->id);
            $vehicleParams = [
                'driver_id' => $driver->id,
                'model_id' => Model::where("mark_id", $marcasId)->first()->id,
                'height' => $altura,
                'wide' => $ancho,
                'long' => $largo,
                'plate' => $placa,
                'm3' => $m3,
                "color_id" => 1,
                "types_transport_id" => $tipoCamion,
                "year" => 2006,
                "burden" => 0,
                //'' => $marcasId,
                //$url_seguro_foto
                //$url_revisado_foto
                //$url_registro_foto
                //$url_frontal_foto
                //$url_lado_foto
                //$url_trasera_foto
            ];
            if (!empty($vehicleId)) {
                $vehicleParams["id"] = $vehicleId;
            }
            
            $vehicle = DriverVehicle::create($vehicleParams);
            //dd(substr(sha1($vehicle->id), 0, 20));
            if ($request->file("seguro")) {
                $response = $this->uploadAFile($request->file("seguro"), 'SEGURO', null, "vehiculos", substr(sha1($vehicle->id), 0, 20));
                $url_seguro_foto = $response;
                $this->registerVehicleImage($url_seguro_foto, "property_url_vehicle", $vehicle->id);
            }
            if ($request->file("revisado")) {
                $response = $this->uploadAFile($request->file("revisado"), 'REVISADO', null, "vehiculos", substr(sha1($vehicle->id), 0, 20));
                $url_revisado_foto = $response;
                $this->registerVehicleImage($url_revisado_foto, "revised_url_vehicle", $vehicle->id);
            }
            if ($request->file("registro")) {
                $response = $this->uploadAFile($request->file("registro"), 'REGISTRO', null, "vehiculos", substr(sha1($vehicle->id), 0, 20));
                $url_registro_foto = $response;
                $this->registerVehicleImage($url_registro_foto, "policy_url_vehicle", $vehicle->id);
            }
            if ($request->file("frontal")) {
                $response = $this->uploadAFile($request->file("frontal"), 'FRONTAL', null, "vehiculos", substr(sha1($vehicle->id), 0, 20));
                $url_frontal_foto = $response;
                $url_frontal_foto = $this->registerVehicleImage($url_frontal_foto, "photo_url_vehicle", $vehicle->id);
            }
            if ($request->file("lado")) {
                $response = $this->uploadAFile($request->file("lado"), 'LADO', null, "vehiculos", substr(sha1($vehicle->id), 0, 20));
                $url_lado_foto = $response;
                $url_lado_foto = $this->registerVehicleImage($url_lado_foto, "right_url_vehicle", $vehicle->id);
            }
            if ($request->file("trasero")) {
                $response = $this->uploadAFile($request->file("trasero"), 'TRASERA', null, "vehiculos", substr(sha1($vehicle->id), 0, 20));
                $url_trasera_foto = $response;
                $url_trasera_foto = $this->registerVehicleImage($url_trasera_foto, "back_url_vehicle", $vehicle->id);
            }
            $this->notifyIfItsTheFirstVehicle($driver->id, $url_frontal_foto);
            K_HelpersV1::getInstance()->setDriverVehicleImages($vehicle->id,  $url_seguro_foto, $url_revisado_foto, $url_registro_foto, $url_frontal_foto, $url_lado_foto, $url_trasera_foto);
            $response = array('error' => false, 'msg' => 'Vehículo Registrado');
            DB::commit();
     
            return response()->json($response);
        } catch (\Illuminate\Database\QueryException $th) {
            DB::rollback();
            $response = array('error' => true, 'msg' => 'Error registrando datos de vehiculo', "detail" => $th->getMessage(), "line" => $th->getTrace());
            return response()->json($response);
            //return $e->getMessage();
        } catch (\Throwable $th) {
            DB::rollback();
            $response = array('error' => true, 'msg' => 'Error actualizando datos de banco', "detail" => $th->getMessage());
            return response()->json($response);
            //return $e->getMessage();
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) // updDriverVehicle
    {
        $user = request()->user();
        $driverId = $user->userable_id;
        switch ($id) {
            case 'edit_vehicle':

                break;
            case 'status':
                //dd(Carbon::now()->addHours(2));
                $vehicleId = $request->vehicle_id;
                $vehicle = DriverVehicle::where([["id", "=", $vehicleId], ["driver_id", "=", $driverId]])->first();
                if (!empty($vehicle) && $vehicle->status == "R") {
                    $response = array('error' => true, 'msg' => 'El vehiculo esta pendiente de revisión por el administrador');
                    response()->json($response, self::HTTP_NON_AUTHORITATIVE_INFORMATION);
                }
                $vehicle->status = $request->estado;
                if ($vehicle->save()) {
                    K_HelpersV1::getInstance()->updateDriverVehicle($request->all(), $vehicleId, $user->id);
                    $response = array('error' => false, 'msg' => $vehicle->status !== 'A' ? 'Vehiculo desactivado' : 'Vehiculo activado');
                    return response()->json($response, self::HTTP_OK);
                }
                $response = array('error' => true, 'msg' => 'Error activando el vehiculo');
                return response()->json($response, self::HTTP_OK);
                break;
            default:
                # code...
                break;
        }
    }
    public function update2(Request $request)
    { // editDriverVehicle
        $user = request()->user();
        $driverId = $user->userable_id;
        $vehicleId = $request->vehicle_id;

        $rules = [
            'vehicle_id'   => 'required',
            'placa'   => 'required',
            'tipo_camion'   => 'required',
            'marcas_id'   => 'required',
            'altura'   => 'required',
            'ancho' => 'required',
            'largo'      => 'required',
            'seguro' => 'required',
            'revisado' => 'required',
            'registro' => 'required',
        ];

        $this->validate($request, $rules);
        DB::beginTransaction();
        try {
            $placa = $request->placa;
            $tipoCamion = $request->tipo_camion;
            $marcasId = $request->marcas_id;
            $altura = $request->altura;
            $ancho = $request->ancho;
            $largo = $request->largo;
            $user = $request->user();
            $driver = Driver::find($user->userable_id);
            $url_seguro_foto = null;
            $url_revisado_foto = null;
            $url_registro_foto = null;
        
            $url_frontal_foto = '';
            $url_lado_foto = '';
            $url_trasera_foto = '';

            $m3 = $altura * $ancho * $largo;
            $vehicle = DriverVehicle::where([["id", "=", $vehicleId], ["driver_id", "=", $driverId]])->update([
                'driver_id' => $driver->id,
                'model_id' => Model::where("mark_id", $marcasId)->first()->id,
                'height' => $altura,
                'wide' => $ancho,
                'long' => $largo,
                'plate' => $placa,
                'm3' => $m3,
                "color_id" => 1,
                "types_transport_id" => $tipoCamion,
                "year" => 2006,
                "burden" => 0,
                "status" => "R",

                //'' => $marcasId,
                //$url_seguro_foto
                //$url_revisado_foto
                //$url_registro_foto
                //$url_frontal_foto
                //$url_lado_foto
                //$url_trasera_foto
            ]);
            if ($request->file("seguro")) {
                $response = $this->uploadAFile($request->file("seguro"), 'SEGURO', null, "vehiculos", substr(sha1($vehicleId), 0, 20));
                $url_seguro_foto = $response;
                $this->registerVehicleImage($url_seguro_foto, "property_url_vehicle", $vehicleId);
                //return $url_seguro_foto;
            }
            if ($request->file("revisado")) {
                $response = $this->uploadAFile($request->file("revisado"), 'REVISADO', null, "vehiculos", substr(sha1($vehicleId), 0, 20));
                $url_revisado_foto = $response;
                $this->registerVehicleImage($url_revisado_foto, "revised_url_vehicle", $vehicleId);
            }
            if ($request->file("registro")) {
                $response = $this->uploadAFile($request->file("registro"), 'REGISTRO', null, "vehiculos", substr(sha1($vehicleId), 0, 20));
                $url_registro_foto = $response;
                $this->registerVehicleImage($url_registro_foto, "policy_url_vehicle", $vehicleId);
            }
            if ($request->file("frontal")) {
                $response = $this->uploadAFile($request->file("frontal"), 'FRONTAL', null, "vehiculos", substr(sha1($vehicleId), 0, 20));
                $url_frontal_foto = $response;
                $this->registerVehicleImage($url_frontal_foto, "photo_url_vehicle", $vehicleId);
            }
            if ($request->file("lado")) {
                $response = $this->uploadAFile($request->file("lado"), 'LADO', null, "vehiculos", substr(sha1($vehicleId), 0, 20));
                $url_lado_foto = $response;
                $this->registerVehicleImage($url_lado_foto, "right_url_vehicle", $vehicleId);
            }
            if ($request->file("trasero")) {
                $response = $this->uploadAFile($request->file("trasero"), 'TRASERA', null, "vehiculos", substr(sha1($vehicleId), 0, 20));
                $url_trasera_foto = $response;
                $this->registerVehicleImage($url_trasera_foto, "back_url_vehicle", $vehicleId);
            }

            K_HelpersV1::getInstance()->updateDriverVehicle(array_merge($request->all(), ["estado" => "R"]), 
            $vehicleId, 
            $user->id);
            K_HelpersV1::getInstance()->setDriverVehicleImages($vehicleId, $url_seguro_foto, $url_revisado_foto, $url_registro_foto, $url_frontal_foto, $url_lado_foto, $url_trasera_foto);
            DB::commit();
            $response = array('error' => false, 'msg' => 'Vehículo Editado');
            return response()->json($response);
        } catch (Exception $e) {
            DB::rollback();
            $response = array('error' => true, 'msg' => 'ERROR al editar vehículo');
            return response()->json($response);
            //return $e->getMessage();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) //delDriverVehicle
    {
        //
        $user = request()->user();
        $vehicleId = $id;
        $driverId = $user->userable_id;
        try {
            DB::beginTransaction();

            $vehicle = DriverVehicle::where([
                ["id", "=", $vehicleId], 
                ["driver_id", "=", $driverId]
            ])->delete();
            K_HelpersV1::getInstance()->deleteDriverVehicle($vehicleId, $user->id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $response = array('error' => true, 'msg' => 'ERROR al editar vehículo');
            return response()->json($response);
            //return $e->getMessage();
        }
        return response(null, self::HTTP_NO_CONTENT);
    }
    private function notifyIfItsTheFirstVehicle($driverId, $vehicleUrl){
        if(!$this->alreadyHasVehicle($driverId)){
            $user = User::where([
                ["userable_id", "=", $driverId],
                ["userable_type", "=", Driver::class],
            ])->first();
            $driverName = $user->driver->nombres ?? null;
            Mail::to($user->email)
                ->send(new PrimerVehiculoMail(vehicleUrl: $vehicleUrl, driverName: $driverName));
            return;
        }
    }
    private function alreadyHasVehicle($driverId){
        return DriverVehicle::where("driver_id", $driverId)->count() > 0;
    }
    private function uploadAFile($file, $identif, $default = null, $directory = "conductores", $customName = "")
    {
        $name = strlen($customName) > 0 ? $customName : str_replace("." . $file->extension(), "", $file->getClientOriginalName());
        $uploadfile = $name . '_APP_' . $identif . '_photo.png';
        $location = 'public/profiles/'.$directory;    //Concatena ruta con nombre nuevo
        $url_imagen_foto = secure_asset("storage/profiles/$directory/$uploadfile"); //prepara ruta para obtención del archivo imagen
        if ($path = Storage::putFileAs($location, $file, $uploadfile, 'public')) {
            # code...
            return $url_imagen_foto;
        }
        return $default;
    }
    private function uploadFoto($files, $name, $identif)
    {
        $response = array('error' => true, 'msg' => 'Error al cargar imagen ' . $name);
        if (isset($files)) { //Valida si es enviado el archivo
            $imagen = $files[$name]['tmp_name'];   //Toma  ubicacion y el nombre de la imagen 
            $uploadfile = $files[$name]['name'] . '_APP_' . $identif . '_photo.png'; //crea el nombre para el nuevo archivo
            $location = $GLOBALS['urlFile'] . 'profiles/conductores/' . $uploadfile;    //Concatena ruta con nombre nuevo
            $url_imagen_foto = $GLOBALS['urlActual'] . 'profiles/conductores/' . $uploadfile; //prepara ruta para obtención del archivo imagen
            if (is_uploaded_file($imagen) && move_uploaded_file($imagen, $location)) {    //Verifica si es cargago el archivo y lo mueve al directorio destino
                chmod($location, 0644); //Le asigna permisos de lectura
                $response = array('error' => false, 'url' => $url_imagen_foto);
            } else {
                $response = array('error' => true, 'msg' => 'Error al cargar imagen ' . $name);
            }
        }
        return $response;
    }
    private function registerVehicleImage($url, $type, $vehicleId)
    {
        Image::where([
            ["is", "=", $type],
            ["imageable_id", "=", $vehicleId],
            ["imageable_type", "=", DriverVehicle::class],
        ])->delete();
        $image = new Image();
        $image->url = $url;
        $image->is = $type;
        $image->imageable_id = $vehicleId;
        $image->imageable_type = DriverVehicle::class;
        $image->save();
        return $image;
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
    private function getVehicleResponseFields()
    {
        return [
            "DV.id as idvehiculos", //"319",
            //"DV.model_id as marcas_id", //"24",
            "Mo.mark_id as marcas_id", //"24",
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
}
