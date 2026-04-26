<?php
namespace App\Http\Controllers\V2\WebApp;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use DB;
class LicensesController extends Controller
{
    /**
     * Display a listing of the licenses.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //  
        $licenses = License::get(["id", "name", "created_at as createdAt", "status", DB::raw("if(price >0, price, 'FREE') as price"), "id as id_licence", "name as title"]);
        foreach ($licenses as $key => $license) {
            //Mapear atributos de la licencia. extraerlos de la tabla attribute_licence
            //$licenses[$key]->attributes = 
            $attributes2 = [];
            foreach (License::find($license->id)->attributes->all() as $attr) {
                # code...
                $r = [
                    "id" => $attr->id,
                    "item" => $attr->item,
                    "active" => $attr->pivot->status == "true",
                ];
                $attributes2[] = $r;
               
            }
            $licenses[$key]->attributes = json_encode($attributes2);
        }
        return response()->json([
            "error" => false,
            "msg" => "Licencias registradas",
            "licences" => $licenses,
            "licenses" => $licenses,
        ]);

    }

    /**
     * Store a newly created license in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified license.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified license in storage.
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
     * Remove the specified license from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
