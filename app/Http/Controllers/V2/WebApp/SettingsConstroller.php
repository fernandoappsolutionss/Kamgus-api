<?php
namespace App\Http\Controllers\V2\WebApp;

use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleCollection;
use App\Models\License;
use Illuminate\Http\Request;
use DB;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{

    public function index(){
        $settings = DB::table('configurations')->get();
        $settingsMapped = [];
        foreach ($settings as $key => $setting) {
            $settingsMapped[$setting->descripcion] = $setting->comision;
        }
        return response()->json([
            'data' => $settingsMapped,
            'msg' => Constant::LOAD_SETTINGS,
        ]);
    }
}