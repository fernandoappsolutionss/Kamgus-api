<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    /**
     * Display a listing of the countries.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return CountryResource::collection(Country::all());
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
            case 'flags':
                $countries = Country::where("status", "A")->get([
                    "id as idpais",
                    "nicename as nombre",
                    "name",
                    "name as nom",
                    "iso as iso2",
                    "iso3",
                    "phonecode as phone_code",
                    "status as habilita",
                    DB::raw("'0' as prioridad"),
                ]);
                $response = array('error' => false, 'msg' => 'Cargando paises', 'paises'=> $countries);
                return response()->json($response);
                break;
            case 'list_v1':
                $countries =  Country::get([
                    "id as idpais",
                    "nicename as nombre",
                    "name",
                    "name as nom",
                    "iso as iso2",
                    "iso3",
                    "phonecode as phone_code",
                    "status as habilita",
                    DB::raw("'0' as prioridad"),
                ]);
                $response = array('error' => false, 'msg' => 'Cargando paises', 'paises'=> $countries);
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
