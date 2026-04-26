<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenseController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
    }


    /**
     * method to consult the license of the company
     * @return \Illuminate\Http\Response
     */
    function my_license(){

        $user = Auth::user();

        return response()->json([
            'msg' => 'Licencia cargada correctamente', 
            'licencia' => $user->licenses->first()
        ]);
        
    }

    
    /**
     * method to load the modules of the first license of the authenticated user
     * @return \Illuminate\Http\Response
     */
    function license_modules(){

        $user = Auth::user();
        $licencia = $user->licenses->first();

        if($licencia){
            return response()->json(
                ['msg' => 'modulos cargados correctamente',
                 'modules' => $licencia->modules
                ]
            );
        }else {
            return response()->json(
                [
                    'msg' => 'no tienes licencia',
                    'modules' => []
                ]
            );
        }


    }
 

}
