<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;

class TokenFCM extends Controller
{

    public function store(Request $request){
        
        $rules = [
            'token' => 'required|max:255',
            'platform' => 'required|max:255'
        ];

        $this->validate($request, $rules);

        $newFCM = new FcmToken();
        $newFCM->user_id = Auth::user()->id;
        $newFCM->token = $request->token;
        $newFCM->platform = $request->platform;
        $newFCM->save();

        return $this->successResponse($newFCM);

    }

    public function update(Request $request){

        $rules = [
            'token' => 'max:255',
            'platform' => 'max:255'
        ];

        $this->validate($request, $rules);

        $updateFCM = FcmToken::where('user_id', Auth::user()->id)->where('platform', $request->platform)->first();

        if($updateFCM){

            $updateFCM->token = $request->token;
            $updateFCM->platform = $request->platform;

            if($updateFCM->save()){

                return response()->json(['msg' => Constant::UPDATE_FCM_TOKEN]);

            }

        }else{

            return response()->json(['msg' => Constant::ERROR_UPDATE_FCM_TOKEN]);
        }

    }

}
