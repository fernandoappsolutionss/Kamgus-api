<?php
namespace App\Http\Controllers\V2\WebApp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Image;
use App\Models\License;
use App\Models\SupportCategory;
use App\Models\SupportState;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\NewSupportTicket;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    const IMAGES_PATH = 'public/support/tickets';
    private function uploadAFile($file, $identif, $default = null)
    {
        $name = str_replace("." . $file->extension(), "", $file->getClientOriginalName());
        $uploadfile = $name . '_APP_' . $identif . '_photo.png';
        $location = self::IMAGES_PATH;    //Concatena ruta con nombre nuevo
        $url_imagen_foto = secure_asset("storage/support/tickets/$uploadfile"); //prepara ruta para obtención del archivo imagen
        if ($path = Storage::putFileAs($location, $file, $uploadfile, 'public')) {
            # code...
            return $url_imagen_foto;
        }
        return $default;
    }
    public function index(){
        return "En desarrollo";
    }
    public function show($id){
        switch ($id) {
            case 'categories':
                return response()->json(["data" => SupportCategory::all()], self::HTTP_OK);
                break;
            
            default:
                # code...
                break;
        }
    }
    public function store(Request $request){
        //return response()->json([], self::HTTP_OK);
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:255',
            'last_name' => 'required|max:255',
            //'email' => 'required|max:255|exists:users,email',
            'email' => 'required|email|max:255',
            'phone' => 'required|max:255',
            'type' => 'required|max:100',
            'description' => 'required|max:255',
            'cimage' => 'nullable|image',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()]);
        }
        $email = $request->email;
        $admins = User::role('Administrador')->get();
        if(User::where("email", $email)->count() == 0){
            $customer = new Customer();
            $customer->nombres = $request->first_name;
            $customer->apellidos = $request->last_name;
            $customer->telefono = $request->phone;
            $customer->save();
            User::create([
                'email' => $email,
                'password' => "EMPTY",
                'userable_id' => $customer->id,
                'userable_type' => Customer::class,
            ]);
        }
        $user = User::firstOrNew(["email" => $email]);
        $userId = $user->id;
        //$categoryId = SupportCategory::find($request->type)->id;
        $categoryId = 1;
        $stateId = SupportState::all()[0]->id;
        $title = "Soporte tecnico";
        $description = $request->description;
        //$endedAt = ;
        $priority = "low";
        $sticket = SupportTicket::create([
            "user_id" => $userId,
            "category_id" => $categoryId,
            "state_id" => $stateId,
            "title" => $title,
            "description" => $description,
            //"ended_at" => $endedAt,
            "priority" => $priority,
        ]);
        $url = null;
        if($request->file('cimage')){

            $url = $this->uploadAFile($request->file('cimage'), 'ticket_'.$sticket->id, 'SUPPORT'); 
            $image = new Image();
            $image->url = $url;
            $image->is = "support"; 
            $image->imageable_id = $sticket->id;
            $image->imageable_type = SupportTicket::class;
            $image->save();    
            $urlArray = explode("/", $url);
            $url = storage_path("app/".self::IMAGES_PATH."/".array_pop($urlArray));
        }
        //Notificar via email sobre el mensaje
        foreach ($admins as $key => $admin) {
            //Enviar email al usuario admin. Usando una plantilla de brevo
            //$admin->notify(new NewSupportTicket($sticket->id, $sticket->title, $sticket->description, $url));
            //            User::where("email", "crhisdlm94@gmail.com")->first()
            $admin
                ->notify(new NewSupportTicket($sticket->id, 
                "From: $request->first_name $request->last_name. Email: $request->email.", 
                $sticket->description, 
                $url));
        }
        return response()->json(["msg"=>"mensaje enviado."], 200);
    }
}