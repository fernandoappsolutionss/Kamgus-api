<?php

namespace App\Http\Controllers\V2\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mark;
use App\Models\TypeTransport;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //getTrucks
    {
        //
        $typesTransport = TypeTransport::where("estado", TypeTransport::ACTIVE_STATUS)
            ->orderBy('id', 'DESC')
            ->get($this->getTrackResponseField());
        foreach ($typesTransport as $key => $tt) {
            $typesTransport[$key]->tc = 5;
            $typesTransport[$key]->pk = .5;

        }
        $response = [
            "error" => false,
            "msg" => "Cargando camiones",
            "trucks" => $typesTransport,
        ];
        return response()->json($response, self::HTTP_OK);
    }
    public function index2() //
    {
        //
        $marks = Mark::where("status", Mark::ACTIVE_STATUS)
            ->get($this->getMarksResponseField());
        $response = [
            "error" => false,
            "msg" => "Cargando camiones",
            "trucks" => $marks,
        ];
        return response()->json($response, self::HTTP_OK);
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
    public function show($id)
    {
     
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
    private function getTrackResponseField(){
        return [
            "id as id_tipo_camion",
            "nombre as nombre_camion",
            "m3 as capacidad",
            "peso as capacidad_peso",
            "precio_minuto as precio_minuto",
            "precio_ayudante as precio_ayudante",
            "descripcion as descripcion",
            "foto as foto",
            "url_foto as url_foto",
            "tiempo as tiempo",
            "estado as estado",
            "app_icon as app_icon",
            "app_icon_selected as app_icon_selected",
            "orden as orden",
        ];


    }
    private function getMarksResponseField(){
        return [
            "id as idmarcas",
            "name as nombre_marca",
            "status as estado",
        ];
    }

}
