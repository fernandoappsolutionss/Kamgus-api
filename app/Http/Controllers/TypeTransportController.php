<?php

namespace App\Http\Controllers;

use App\Http\Resources\Drivers\TypeTransportsCollection;
use App\Models\TypeTransport;
use Illuminate\Http\Request;

class TypeTransportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new TypeTransportsCollection(TypeTransport::orderBy('id', 'DESC')->get());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
    public function show($id)
    {
        //
        switch ($id) {
            case 'list_v1':
                $typesTransport = TypeTransport::where("estado", TypeTransport::ACTIVE_STATUS)
                    ->orderBy('id', 'DESC')
                    ->get([
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
                    ]);
                $response = array('error' => false, 'msg' => 'Cargando camiones', 'trucks'=> $typesTransport);
                return response()->json($response);
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
}
