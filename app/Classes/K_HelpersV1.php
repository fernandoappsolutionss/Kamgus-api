<?php
namespace App\Classes;
//defined('BASEPATH') OR exit('No direct script access allowed');

use App\Models\Driver;
use DateInterval;
use DateTime;
use DateTimeZone;
use DB;
use Illuminate\Support\Facades\DB as FacadesDB;

class K_HelpersV1 
{
    const ENABLE = false;

    const ARTICLES_IDS = [
        80 => 1,
        81 => 2,
        82 => 3,
        83 => 4,
        84 => 8,
        85 => 14,
        86 => 12,
        87 => 13,
        88 => 15,
        89 => 17,
        90 => 18,
        91 => 19,
        92 => 21,
        93 => 22,
        94 => 24,
        95 => 25,
        96 => 26,
        97 => 27,
        98 => 28,
        99 => 29,
        100 => 30,
        101 => 31,
        102 => 33,
        103 => 34,
        104 => 35,
        105 => 36,
        106 => 37,
        107 => 38,
        108 => 39,
        109 => 40,
        110 => 41,
        111 => 42,
        112 => 43,
        113 => 44,
        114 => 45,
        115 => 46,
        116 => 47,
        117 => 48,
        118 => 49,
        119 => 50,
        120 => 52,
        121 => 53,
        122 => 54,
        123 => 55,
        124 => 56,
        125 => 57,
        126 => 58,
        127 => 59,
        128 => 60,
        129 => 61,
        130 => 63,
        131 => 96,
        132 => 65,
        133 => 66,
        134 => 67,
        135 => 68,
        136 => 69,
        137 => 70,
        138 => 71,
        139 => 74,
        140 => 75,
        141 => 76,
        142 => 79,
        143 => 80,
        144 => 81,
        145 => 82,
        146 => 83,
        147 => 85,
        148 => 86,
        149 => 87,
        150 => 88,
        151 => 89,
        152 => 90,
        153 => 91,
        154 => 92,
        155 => 93,
        156 => 94,
        157 => 95,
        158 => 97,
    ];
    public function __construct()
    {
        
    }    
    public static function getInstance(){
        return new K_HelpersV1();
    }
    public function isValidPass($hash, $password){
        if(!self::ENABLE){
            return false;
        }
        return $hash == md5($password);
    }
    public function createService($data, $userId){
        if(!self::ENABLE){
            return false;
        }
        $data['usuarios_id'] = $userId;
        $connection = DB::connection("mysql_old");
        $kms = $data['kms'] / 1000;		
		$duration = $data['tiempo'] / 60;
		$valor = round($data['valor']);
	
		$description = empty($data["description"]) ? null : $data["description"];
		$assistant = (empty($data["assistant"]) || $data["assistant"] === "false") ? 0 : $data["assistant"];
        //var_dump($assistant);
        //exit();
        if(!empty($data["assistant"]) && ($data["assistant"]) === "true"){
            $assistant = 1;
        }
		if($data['tipo_translado'] == "Simple"){
			$assistant = 1;
		}
        if( $data['fecha_reserva'] == 'now'){
        	$fechNueva = date('Y-m-d H:i:s');
        }else{
        	$date = new DateTime( $data['fecha_reserva'] );
            $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
			$fechNueva =  $date->format('Y-m-d H:i:s');
        }    

        $coordenas = json_decode(str_replace('\"','"', $data['coordenas']), true);
        //die($data['coordenas']);
        //die(json_encode($coordenas));
		$lastId = $connection->table("servicios")->insertGetId([
            "usuarios_id" =>  $data['usuarios_id'],
            "empleado_id" =>  $data['usuarios_id'],
            "valor" =>  $valor,
            "punto_referencia" =>  '',
            "inicio_punto" =>  $data['inicio_punto'],
            "punto_final" =>  $data['punto_final'],
            "coordenas" =>  json_encode($coordenas),
            "fecha_reserva" =>  $fechNueva,
            "tipo_translado" =>  $data['tipo_translado'],
            "estado" =>  $data['estado'],
            "creado" =>  date('Y-m-d H:i:s'),
            "kms" =>  $data['kms'],
            "tipo_pago" =>  ($data['tipo_pago'] == "Card") ? "Tarjeta Crédito" : $data['tipo_pago'],
            "tiempo" =>  $data['tiempo'],
            "id_tipo_camion" =>  $data['id_tipo_camion'],
            "tokenTarjeta" =>  $data['tokenTarjeta'],
            "Response" =>  '',
            "foto" =>  '',
            "descuento" =>  0,
            "puntos_coord" =>  '',
            "description" =>  $description,
            "assistant" =>  $assistant
        ]);
        
               
		if( $lastId ){
			$articulos = $data['articulos'];
			
			if(!empty($articulos)){
                $connection->table("servicios_objetos")->insertGetId([
                    "servicios_id" =>  $lastId,
                    "lista_objetos" =>  $articulos, 
                    "estado" =>  'A',
                ]);
            }
			// agregar en la nueva tabla
            $connection->table("precio_sugerido")->insertGetId([
                "servicio_id" =>  $lastId,
                "usuario_id" =>  $data['usuarios_id'], 
                "precio" =>  $data['precio'],
                "tiempo" =>  0,
            ]);
			// agregar en la nueva tabla
		

			$queryS = $connection->table("servicios")->where("idservicios", $lastId);
			
			//exit(json_encode($_FILES["image_description"]));
			if(!empty($_FILES["image_description"]) || !empty($data["image_description"])){

			}
            return $lastId;
		}else{
            return false;

		}    	
    }
    public function insertImageService($serviceId, $url, $userId, $punto){
        if(!self::ENABLE){
            return false;
        }
        $array = array(
            'servicios_id' => $serviceId,
            'usuarios_id' => $userId,
            'conductores_id' => "0",
            'url_foto' => $url,
            'punto' => $punto,
        );
        $connection = DB::connection("mysql_old");
        return $connection->table("fotos_servicio")->insertGetId($array);
    }

    public function confirmDriver($data, $driverId){
        $servicioId = $data["servicio_id"];
			//$usuarioId = $data["usuario_id"];
        //$driverId = $data["driver_id"];
  
        $connection = DB::connection("mysql_old");
        $serviceInfo = $connection->table("servicios")->where("idservicios", $servicioId)->first();
        //Consultar precio sugerido del conductor

        $precioSugerido =  $connection->table("precio_sugerido")->where([
            ['usuario_id', "=", $driverId],
            ['servicio_id', "=", $servicioId],
            ['by_driver', "=", '1'],
        ])->first();
        if(empty($precioSugerido)){
            return;
        }
    
        $query = $connection->table("usuarios")->where([
            ['usuarios.idusuarios', "=", $driverId],
            ['vehiculos.tipo_camion', "=", $serviceInfo->id_tipo_camion],
        ])
        ->join('vehiculos', 'vehiculos.conductores_id', '=', 'usuarios.idusuarios')->first();
        $driver =  !empty($query) ? $query: null;
        if(empty($driver)){
            return;
        }
        $data = array(
            'usuarios_id' => $driverId, 
            //"estado" => (new DateTime($serviceInfo->fecha_reserva) >= ((new DateTime('now'))->sub(new DateInterval("PT5H"))) ? "Reserva" : "Activo"),
            "valor" => $precioSugerido->precio
        );
        //dd($serviceInfo->estado);
        if($serviceInfo->estado == "Pendiente"){
            $data["estado"] = "Activo";
        }else if($serviceInfo->estado == "Reserva"){
            $data["estado"] = "Agendado";
        }
        //$where = "idservicios = ".$this->db->escape_str($servicioId)." AND status = 'active'";
        
        
        //exit(json_encode([new DateTime($serviceInfo->fecha_reserva), (new DateTime('now'))->sub(new DateInterval("PT5H"))]));
        //set driver to service		
        $connection->table("servicios")->where("idservicios", $servicioId)->update($data);

        $data = array(
            'servicios_id' => $servicioId,
            'conductores_id' => $driverId,
            'vehiculos_id' => $driver->idvehiculos,
            'startTime' => date("Y-m-d H:i:s"),
            'endTime' => (new DateTime('now'))->add($this->convertTextToDateInterval($precioSugerido->tiempo))->format("Y-m-d H:i:s"),
            'estado' => $serviceInfo->estado == "Reserva" ? "Agendado" : 'En Curso',
            'role' => 'CONDUCTOR',
            'confirmado' => 'SI',
            'ispagado' => 'Pendiente',
            'comision' => '',
            'fecha_reserva' => $serviceInfo->fecha_reserva,
            'creado' => date("Y-m-d H:i:s"),
        );
        
        $alreadyExist = $connection->table("conductor_servicios")->where([
            ['servicios_id', "=", $servicioId],
            ['conductores_id', "=", $driverId],
            ['vehiculos_id', "=", $driver->idvehiculos],
        ])->count() > 0;
        if(!$alreadyExist){
            $inserted = $connection->table("conductor_servicios")->insertGetId([// Produces: INSERT INTO mytable (title, name, date) VALUES ('{$title}', '{$name}', '{$date}')
                'servicios_id' => $servicioId,
                'conductores_id' => $driverId,
                'vehiculos_id' => $driver->idvehiculos,
            ]);
            return $inserted;
        }
        return $alreadyExist;
    }
    public function cancelService($serviceId){
        if(!self::ENABLE){
            return false;
        }
     
        $connection = DB::connection("mysql_old");
		$updated = $connection->table("servicios")->where("idservicios", $serviceId)->update([
            "estado" => "Cancelado"
        ]);
		//$this->db->query('UPDATE conductor_servicios SET estado = "Rechazado", role="USUARIO" WHERE servicios_id ='.$serviceId);

		if( $updated ){
            $updated = $connection->table("conductor_servicios")->where("servicios_id", $serviceId)->update([
                "estado" => "Rechazado"
            ]);
            return $updated;
			
		}else{
            return $updated;
		}
    }
    //Inicia un servicio previamente aceptado por el conductor
    public function startReservedService($data){
	
        if(!self::ENABLE){
            return false;
        }     
        $connection = DB::connection("mysql_old");
		
        $query = $connection->table("conductor_servicios as cs")
            ->join("servicios as s", "cs.servicios_id", "=", "s.idservicios")
            ->join("usuarios as u", "s.usuarios_id", "=", "u.idusuarios")
            ->where([
                ["cs.idconductor_servicios", "=", $data['service_id']],
                ["cs.estado", "=", "Agendado"],
            ]);
		//$query = $this->db->query('SELECT * FROM conductor_servicios cs INNER JOIN servicios s ON cs.servicios_id = s.idservicios INNER JOIN usuarios u ON s.usuarios_id = u.idusuarios');
		if( $query->count() > 0 ){
			$newData = [];
            $updated = $connection->table("servicios")->where("idservicios", $query->first()->servicios_id)->update([
                "estado" => "Activo"
            ]);
            $updated = $connection->table("conductor_servicios")->where("idconductor_servicios", $data['service_id'])->update([
                "estado" => 'En Curso'
            ]);
            /*
            foreach ($query->all() as $key => $value) {
                $newData[$key] = (object) [
                    "key" => $value['idconductor_servicios'],
                    "idconductor_servicios" => $value['idconductor_servicios'],
                    "servicios_id" => $value['servicios_id'],
                    "startTime" => $value['startTime'],
                    "endTime" => $value['endTime'],
                    "estado" => $value['estado'],
                    "confirmado" => $value['confirmado'],
                    "fecha_reserva" => $value['fecha_reserva'],                        
                    "usuarios_id"=> $value['usuarios_id'],
                    "punto_referencia"=> $value['punto_referencia'],
                    "inicio_punto"=> $value['inicio_punto'],
                    "punto_final"=> $value['punto_final'],
                    "coordenas"=> $value['coordenas'],
                    "tipo_translado"=> $value['tipo_translado'],
                    "creado"=> $value['creado'],
                    "kms"=> $value['kms'],
                    "tipo_pago"=> $value['tipo_pago'],
                    "tiempo"=> $value['tiempo'],
                    "id_tipo_camion"=> $value['id_tipo_camion'],
                    "foto"=> $value['foto'],
                    "descuento"=> $value['descuento'],
                    "puntos_coord"=> $value['puntos_coord'],
                    "piso"=> $value['piso'],
                    "ispagado"=> $value['ispagado'],
                    "celular"=> $value['celular'],
                ];
            }
            $response = array('error' => false, 'msg' => 'Cargando servicio reservado', 'data'=> $newData);
            */
            return true;
                /*$response = array('error' => false, 'msg' => 'Cargando cuentas de bancos', 'data'=> $query->result_array());
	        	$this->response( $response , REST_Controller::HTTP_OK );*/
        }else{
            return false;
        }
	}
    public function setCustomerNotification($userId, $serviceId, $title, $description){
        return $this->registerServiceStatus($userId, $serviceId, $title, $description);
    }
    public function registerCustomer($request, $userId = null){
            if(!self::ENABLE){
                return false;
            }
            $param = [
                "nombre" => $request["nombres"],
                "apellidos" => $request["apellidos"],
                "celular" => $request["telefono"],
                "email" => trim($request["email"]),
                "password" => md5($request["password"]),
                "pais_idpais" => 176,
                "tipo_idtipos_documento" => 1,
                "tipo_registro" => 'F',
                "url_foto" => $request["url_foto"],
                "token" => "",
                "estado" => 0,
                "url_licencia" => $request["url_licencia"],
                "rol" => 2,
                "codigounico" => strtoupper(substr($request["nombres"],0,3).substr($request["apellidos"],0,1).substr(uniqid(), 0, 4)),
            ];
            if(!empty($userId)){
                $param["idusuarios"] = $userId;
            }
            $connection = DB::connection("mysql_old");
            return $connection->table("usuarios")->insertGetId($param);
    
    
    }
    public function isNotRegisteredCustomer($userId){
        if(!self::ENABLE){
            return false;
        }
   
        
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->where("idusuarios", $userId)->count() <= 0;
    }
    private function convertTextToDateInterval($text){
        //12:00
        if(empty($text)){
            $text = "00:00:00";
        }
        $time = explode(":", $text);
        //exit($text);

        return new DateInterval("PT".$time[0]."H".$time[1]."M".$time[2]."S");
    }

    //--------------- Begin Driver methods ---------------
    public function getAllReservedServices($userId){
        $connection = DB::connection("mysql_old");
        $query = $connection->select('SELECT s.*, u.*, tipo_camion.nombre_camion FROM servicios s 
		left JOIN tipo_camion on tipo_camion.id_tipo_camion = s.id_tipo_camion
		INNER JOIN usuarios u ON s.usuarios_id = u.idusuarios 
		WHERE s.estado in ("Reserva") 
		AND s.idservicios NOT IN (
			SELECT p.servicio_id FROM precio_sugerido p 
			WHERE p.usuario_id = ?) 
			AND s.id_tipo_camion IN (SELECT v.tipo_camion FROM vehiculos v WHERE v.conductores_id = ?)', [$userId, $userId]);
        if($query) {
    		if( $query->count() > 0 ){
    			$newData = [];
    			foreach ($query->get() as $key => $value) {
                    $value = (array) $value;
                    $q = $connection->table("precio_sugerido")->where([
                        'servicio_id' => $value['idservicios'],
						'by_driver' => 0,
                    ]);
					$customerPrice = $q->count() > 0 ? $q->first()->precio : $value['valor'];
    				$newData[$key] = (object) [
    					"key" => $value['idservicios'],
    					"idservicios" => $value['idservicios'],
    					"usuarios_id"=> $value['usuarios_id'],
    					"valor"=> $customerPrice,
    					"punto_referencia"=> $value['punto_referencia'],
    					"inicio_punto"=> $value['inicio_punto'],
    					"punto_final"=> $value['punto_final'],
    					"coordenas"=> $value['coordenas'],
    					"fecha_reserva"=> $value['fecha_reserva'],
    					"tipo_translado"=> $value['tipo_translado'],
    					"estado"=> $value['estado'],
    					"creado"=> $value['creado'],
    					"kms"=> $value['kms'],
    					"tipo_pago"=> $value['tipo_pago'],
    					"tiempo"=> $value['tiempo'],
    					"id_tipo_camion"=> $value['id_tipo_camion'],
    					"nombre_camion"=> $value['nombre_camion'],
    					"foto"=> $value['foto'],
    					"descuento"=> $value['descuento'],
    					"puntos_coord"=> $value['puntos_coord'],
    					"piso"=> $value['piso'],
    					"empleado_id"=> $value['empleado_id'],
    					"ispagado"=> $value['ispagado'],
    					"celular"=> $value['celular'],
    					"passenger"=> $value['nombre'],
    					"customer_name"=> $value['nombre']." ". $value['apellidos'],

    				];
					if($value['tipo_translado'] === "Simple"){
				
                        $query = $connection->table("servicios_objetos")->where([
                            'servicios_id' => $value['idservicios'],
                        ]);
						if( $query->count() > 0 ){
							$newData[$key]->articles = json_decode($query->first()->lista_objetos);
						}
					}
    			}
    			return $newData;
    		}else{
                return false;
            }
        }else{
            return false;
		}  
        
    }
    public function acceptService($data, $userId, $driverTimeV){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        //$userId = $data['usuario_id'];
		//exit("<b>".json_encode($data)."</b>");
		
		//EN - I get the distance and time that the driver takes to reach the starting point of the service
		//ES - Obtengo la distancia y el tiempo que tarda el conductor en llegar al punto de inicio del servicio
		//$driverTimeV = 0;
		//$driverTimeT = "";
		//if(!empty($data['user_lat']) && !empty($data['user_lng'])){
		//	$timeAndDistance = $this->calculateDistanceToUser($data['servicio_id'], $data['user_lat'], $data['user_lng']);
		//	//exit(json_encode(($timeAndDistance)));
		//	if(!empty($timeAndDistance["error"])){
		//		//$this->response($timeAndDistance, REST_Controller::HTTP_NOT_FOUND);
		//	}
		//	$driverTimeV = (count($timeAndDistance) > 0 && empty($timeAndDistance["error"])) ? $timeAndDistance["resultado"]["duration"]["value"] : 0;
		//	$driverTimeT = (count($timeAndDistance) > 0 && empty($timeAndDistance["error"])) ? $timeAndDistance["resultado"]["duration"]["text"] : "";
		//}
        
		//Verificar que el servicio siga disponible para aceptar ofertas
		$driverSelected = $connection->table("servicios")->where("idservicios", $data['servicio_id'])->whereIn("estado", ["Pendiente", "Reserva"]);
	
		if($driverSelected->count() > 0){		
			return $connection->table("precio_sugerido")->insertGetId([
                "servicio_id" =>  $data['servicio_id'],
                "usuario_id" =>  $userId, 
                "precio" =>  $data['precio'],
                "by_driver" =>  1,
                "tiempo" =>   date('Y-m-d H:i:s', $driverTimeV),
            ]);
		}
        return false;
		  
    }
    public function cancelCustomerServiceByDriver($data, $userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        
        $query = $connection->table("precio_sugerido")->insertGetId([
            "servicio_id" =>  $data['servicio_id'],
            "usuario_id" =>  $userId, 
            "cancelado" =>  true,
            "by_driver" =>  1,
            "precio" =>  0,
            "tiempo" =>  0,
        ]);
        return $query;
    }
    //// Servicio Cancelado
	public function cancelCustomerService($data, $userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        
        $query = $connection->table("precio_sugerido")->insertGetId([
            "servicio_id" =>  $data['servicio_id'],
            "usuario_id" =>  $userId, 
            "cancelado" =>  true,
            "by_driver" =>  1,
            "precio" =>  0,
            "tiempo" =>  0,
        ]);
		if( $query ){
            $servicio = $connection->table("servicios")->where([
                ["idservicios",  "=", $data['servicio_id']], 
				["estado",  "=", "Cancelado"],
            ]);
    		$response = array('error' => false, 'msg' => 'El servicio fue cancelado' );
			if($servicio->count() == 0){
				/*
				$this->notifyServiceStatus($data['servicio_id'], 2, 'El servicio fue cancelado', "Un conductor rechazo el servicio.", [
					"key" => "CANCELLED_DRIVER"
				]);
				*/
				//$this->notifyStatusByEmail($data['servicio_id'], 2, 'El servicio fue cancelado', "Un conductor rechazo el servicio.");
			}	
            return $query;
		}else{
		   return false;
		}  
	}
    //Set Servicio Rechazado por el Usuario
	/**
	 * Si el servicio es agendado ? ponerlo nuevamente en reserva para los conductores; notificales al usuario y conductores.
	 */
	public function setServRechazado($data, $url = null){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
		
		$userId = $data['idconductor_servicios'];
		$serviceId = $data["idservice"];
		$last = empty($data["last"]) ? false : true;

        $connection->table("conductor_servicios")->where([["idconductor_servicios", "=", $userId], ["servicios_id", "=", $serviceId]])->update([
            "observacion" => $data['observacion'],
            "estado" => "Rechazado",
        ]);
        $serviceInfo = $connection->table("servicios")->where('idservicios', $serviceId)->first();
        $estado = "Cancelado";
        if($serviceInfo->estado == "Activo"){
            //$estado = "Pendiente";
        }
        if($serviceInfo->estado == "Agendado"){
            $estado = "Reserva";
        }
        $query = $connection->table("servicios")->where('idservicios', $serviceId)->update([
            "estado" => $estado,
        ]);
        
        $refunded = $connection->table("transacciones")->where('servicios_id', $serviceId);
        if( $refunded->count() <= 0 ){
            //Realiza el reembolso (Refund) del servicio cuando el metodo de pago fue por tarjeta
        }
        return $serviceInfo;

	}
    //Set Servicio Recibido por el Usuario
	public function setServRecibido($data, $userId, $urlImages = []){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
		//Refresh Service Status
        $serviceId = $data['service_id'];
		$point = $data['punto'];
		if($point === "B"){
			//$query = $this->db->query('UPDATE conductor_servicios SET nombre_recep = "'.$data['nombre_recep'].'", identidad_recep = "'.$data['identidad_recep'].'", estado = "Terminado" WHERE idconductor_servicios = "'.$userId.'"');
            $query = $connection->table("conductor_servicios")->where([["conductores_id", "=", $userId], ["servicios_id", "=", $serviceId]])->update([
                "nombre_recep" => $data['nombre_recep'],
                "identidad_recep" => $data['identidad_recep'],
                "estado" => "Terminado",
            ]);
			if($query){
                $connection->table("servicios")->where("idservicios", $data['service_id'])->update([
                   // "nombre_recep" => $data['nombre_recep'],
                   // "identidad_recep" => $data['identidad_recep'],
                    "estado" => "Terminado",
                ]);
				return true;
			}
			return false;
		}
        return true;

	}
    public function paymentIntent($request, $userId, $result){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $driverId  = $request->driver_id;
        $serviceId  = $request->service_id;
        $paymentMethodId  = $request->payment_method;

        $query = $connection->table("servicios")->where([
            ["idservicios", "=", $serviceId]
        ])->whereIn("estado", ["Anulado", "Terminado", "Cancelado", "Repetir"]);
        if($query->count() <= 0){
            $response = array('error' => true, 'msg' => 'Servicio no encontrado' );
            return $response;
        }
        $driverPrice = $connection->table("precio_sugerido")->where([
            ["servicio_id", "=", $serviceId],
            ["usuario_id", "=", $driverId],
            ["by_driver", "=", 1],
        ])->first()->precio;
        $amount = $driverPrice;// Aqui va el monto total del servicio
        $customerInfo = $connection->table("customers")->where("idusuarios", $userId)->first();
        if( !empty($customerInfo) ){
            try {
                //code...
                
                if($result["error"]){
                    return $result;
                }
               
                return $connection->table("servicios")->where("idservicios", $serviceId)->update([ //se le asigna el customer id al usuario
                    "valor" => $amount,
                    "tokenTarjeta" => $result["payment_intent"]->id,
                    "Response" => $result["payment_intent"]->status,
                ]);
                
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                $result = [
                    "error" => true,
                    "amount" => $amount * 100,
                    "msg" => $e->getError()->message
                ];
                return $result;

                //throw $th;
            }
		}else{
			$response = array('error' => true, 'msg' => 'Servicio no encontrado' );
            return $response;
		}
    }

    public function registerDriver($request){
        if(!self::ENABLE){
            return false;
        }
        $param = [
            "nombre" => $request["nombres"],
            "apellidos" => $request["apellidos"],
            "celular" => $request["telefono"],
            "email" => trim($request["email"]),
            "password" => md5($request["password"]),
            "pais_idpais" => 176,
            "tipo_idtipos_documento" => 1,
            "tipo_registro" => 'F',
            "url_foto" => $request["url_foto"],
            "token" => "",
            "estado" => 0,
            "url_licencia" => $request["url_licencia"],
            "rol" => 1,
            "codigounico" => strtoupper(substr($request["nombres"],0,3).substr($request["apellidos"],0,1).substr(uniqid(), 0, 4)),
        ];
        
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->insertGetId($param);


    }

    public function setDriverVehicle($request, $userId){
        if(!self::ENABLE){
            return false;
        }
        $placa = $request["placa"];
        $tipoCamion = $request["tipo_camion"];
        $marcasId = $request["marcas_id"];
        $altura = $request["altura"];
        $ancho = $request["ancho"];
        $largo = $request["largo"];
        //$userId = $request->conductores_id;
        $url_frontal_foto = '';
        $url_lado_foto = '';
        $url_trasero_foto = '';
        $m3 = $altura * $ancho * $largo;
        
        $connection = DB::connection("mysql_old");
        return $connection->table("vehiculos")->insertGetId([
            "tipo_camion" => $tipoCamion == 6 ? 8 : $tipoCamion, 
            "marcas_id" => $marcasId, 
            "conductores_id" => $userId, 
            "placa" => $placa, 
            "altura" => $altura, 
            "ancho" => $ancho, 
            "largo" => $largo, 
            "url_poliza" => '', 
            "url_revisado" => '', 
            "url_propiedad" => '', 
            "url_foto" => '', 
            "url_derecha" => '', 
            "url_izquierda" => '', 
            "url_trasera" => '', 
            "color_id" => 1, 
            "year_car" => 2006, 
            "m3" => $m3,
            "carga" => 0, 
        ]);
    }
    public function setDriverVehicleImages($vehicleId, $urlSeguroFoto, $urlRevisadoFoto, $urlRegistroFoto, $urlFrontalFoto, $urlLadoFoto, $urlTraseroFoto){
        if(!self::ENABLE){
            return false;
        }
        
        $connection = DB::connection("mysql_old");
        $columns = [
            
        ];
        if(!empty($urlSeguroFoto)){
            $columns["url_poliza"] = $urlSeguroFoto;
        }
        if(!empty($urlRevisadoFoto)){
            $columns["url_revisado"] = $urlRevisadoFoto;
        }
        if(!empty($urlRegistroFoto)){
            $columns["url_propiedad"] = $urlRegistroFoto;
        }
        if(!empty($urlFrontalFoto)){
            $columns["url_foto"] = $urlFrontalFoto;
        }
        if(!empty($urlLadoFoto)){
            $columns["url_derecha"] = $urlLadoFoto;
        }
        if(!empty($urlTraseroFoto)){
            $columns["url_trasera"] = $urlTraseroFoto;
        }
        if(empty($columns)){
            return;
        }
        return $connection->table("vehiculos")->where("idvehiculos", $vehicleId)->update($columns);       
        
    }
    public function editDriverVehicle($request, $vehicleId, $userId){
        if(!self::ENABLE){
            return false;
        }
        $placa = $request["placa"];
        $tipoCamion = $request["tipo_camion"];
        $marcasId = $request["marcas_id"];
        $altura = $request["altura"];
        $ancho = $request["ancho"];
        $largo = $request["largo"];
        //$userId = $request->conductores_id;
        $url_frontal_foto = '';
        $url_lado_foto = '';
        $url_trasero_foto = '';
        $m3 = $altura * $ancho * $largo;
        
        $connection = DB::connection("mysql_old");
        return $connection->table("vehiculos")->where("idvehiculos", $vehicleId)->update([
            "tipo_camion" => $tipoCamion, 
            "marcas_id" => $marcasId, 
            "conductores_id" => $userId, 
            "placa" => $placa, 
            "altura" => $altura, 
            "ancho" => $ancho, 
            "largo" => $largo, 
            "url_poliza" => '', 
            "url_revisado" => '', 
            "url_propiedad" => '', 
            "url_foto" => '', 
            "url_derecha" => '', 
            "url_trasera" => '', 
            "m3" => $m3,
        ]);
    }
    
    
    public function updateDriverVehicle($request, $vehicleId, $userId){
        if(!self::ENABLE){
            return false;
        }
        
        $connection = DB::connection("mysql_old");
        $vehicle = $connection->table("vehiculos")->where([
            ["idvehiculos", "=", $vehicleId],
            ["conductores_id", "=", $userId],
        ])->update([
            "estado" => $request["estado"],
        ]);
        return $vehicle;
        
    }
    public function deleteDriverVehicle($vehicleId, $userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
		//$query = $this->db->query('UPDATE vehiculos SET estado = "'.$data['estado'].'" WHERE conductores_id = "'.$data['conductores_id'].'" AND idvehiculos = "'.$data['idvehiculos'].'"');
        return $connection->table("vehiculos")->where([
            ["idvehiculos", "=", $vehicleId],
            ["conductores_id", "=", $userId],
        ])->delete();
    }
    
    public function setDriverBanks($request, $driverId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $driver = $connection->table("usuarios")->where("idusuarios", $driverId)->first();
        $cc = $connection->table("cuenta_conductor");
        if($cc->where([
            ["idusuario", "=", $driverId],
        ])->count() > 0){
            return $connection->table("cuenta_conductor")->where([
                ["idusuario", "=", $driverId],
            ])->update([
                "banco" => $request["banco"],
                "numero_cuenta" => $request["numero_cuenta"],
                "nombres" => $driver->nombre." ". $driver->apellidos,
                "doc_identidad" => $request["doc_identidad"],
            ]);
        }
        return $connection->table("cuenta_conductor")->insertGetId([
            "idusuario" => $driverId,
            "banco" => $request["banco"],
            "numero_cuenta" => $request["numero_cuenta"],
            "nombres" => $driver->nombre." ". $driver->apellidos,
            "doc_identidad" => $request["doc_identidad"],
        ]);
    }
    public function getDriverBanks($request, $driverId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $data = $connection->table("cuenta_conductor")->where("idusuario", $driverId)->get();
        return $data;
    }

    public function createCustomer($userId, $userInfo, $customerId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $customer = $connection->table("customers")->where("idusuarios", $userId)->first();
        if(empty($customer)){
            $connection->table("customers")->insertGetId([
                "UniqueIdentifier" => -1,
                "Email" => $userInfo->email,
                "FirstName" => $userInfo->nombre,
                "LastName" => $userInfo->apellidos,
                "Phone" => $userInfo->celular,            
                "EntityType" => 2,
                "idusuarios" => $userId,        
            ]);
            $customer = $connection->table("customers")->where("idusuarios", $userId)->first();            
        }
        if(empty($customer->StripeCustomerId)){
            $customer = $customerId;
        }
        return $customerId;
        
    }

    public function setDepositStripe($userId, $amount, $paymentIntent){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("balances_conductores")->insertGetId(
         [
            "idusuarios" => $userId,
            "id_role" => 1,
            "valor" => $amount,
            "transaction_id" => $paymentIntent['id'],
            "response" => $paymentIntent['status'],
            "gateway_type" => 'Stripe',
            "operation" => '1',
            "fecha" => date("Y-m-d H:i:s"),
            "created_at" => date("Y-m-d H:i:s"),
         ]);
        
    }
    public function enableDriver($userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->where("usuarios.idusuarios", $userId)->update([
            "estado" => "2",
        ]);
    }
    public function disableDriver($userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->where("usuarios.idusuarios", $userId)->update([
            "estado" => "3",
        ]);
    }
    public function setDepositPagoCash($userId, $value, $taxes, $ticket){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $data = array(
            'idusuarios' =>$userId,
			'id_role' => 1,
			'valor' => $value - $taxes,
			'tax' => $taxes,
			'gateway_type' => "PAGOCASH",
			'created_at' => date("Y-m-d H:i:s"),
		);
        $balanceId =  $connection->table("balances_conductores")->insertGetId($data);
        return $connection->table("balances_conductores")->where("id", $balanceId)->update([
            "transaction_id" => $ticket,
            "response" => 'PCPEND_0',
            "created_at" => date("Y-m-d H:i:s"),
        ]);
		
    }
    public function setDepositYappy($userId, $value, $taxes, $ticket){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $data = array(
            'idusuarios' =>$userId,
			'id_role' => 1,
			'valor' => $value - ($value * $taxes / 100.0),
			'tax' => ($value * $taxes / 100.0),
			'gateway_type' => "Yappy",
			'created_at' => date("Y-m-d H:i:s"),
		);
        $balanceId =  $connection->table("balances_conductores")->insertGetId($data);
        return $balanceId;
    }
    public function requestPartialTransaction($userId, $value, $transactionId = null){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $params = [
            "idusuarios" => $userId,
            "valor" => $value,
            "operation" => "2",
            "transaction_id" => $transactionId,
            "response" => "preordered",
            "fecha" => date("Y-m-d H:i:s"),
            "created_at" => date("Y-m-d H:i:s"),
            "id_role" => 1,
            "gateway_type" => 0,
            //"" => ,
        ];
        return $connection->table("balances_conductores")->insertGetId($params);
    }
    public function getBalanceTransactionId($bId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $balance = $connection->table("balances_conductores")->where("id", $bId)->first();
        if(empty($balance)){
            return null;
        }
        return $balance->transaction_id;
    }
    public function refreshBalance($bId, $status){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $balance = $connection->table("balances_conductores")->where("id", $bId)->update([
            "response" => $status,
        ]);

        return $balance;
    }
    public function getBalance($bId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $balance = $connection->table("balances_conductores")->where("id", $bId)->first();

        return $balance;
    }
    private function registerServiceStatus($driverId, $serviceId, $title, $description){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
		$months = [    
			"Jan" =>	"enero",
			"Feb" => 	"febrero",
			"Mar" =>	"marzo",
			"Apr" =>	"abril",
			"May" =>	"mayo",
			"Jun" =>	"junio",
			"Jul" =>	"julio",
			"Aug" =>	"agosto",
			"Sep" =>	"septiembre",
			"Oct" =>	"octubre",
			"Nov" =>	"noviembre",
			"Dec" =>	"diciembre",
		];
		$data = array(
			'conductor_id' => $driverId,
			'servicios_id' => $serviceId,
			'estado' => $title,
			'motivo' => $description,
			'fecha' => date("d")." de ".$months[date("M")]." de ".date("Y")." ".date("H:i"),
			'it_was_read' => 0,
		);
		
        return $connection->table("servicio_estados")->insertGetId($data);
	}
    public function setDriverNotification($userId, $serviceId, $title, $description){
        return $this->registerServiceStatus($userId, $serviceId, $title, $description);
    }
    public function updateUserNotification($id){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("servicio_estados")->where("idse", $id)->update([
            'it_was_read' => '1',
        ]);
    }
    public function resetDriverPassword($email, $password){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->where("email", $email)->update([
            'password' => md5($password),
        ]);
    }
    public function calculateDriverBalance($userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        $config = $connection->select('SELECT configuracion.comision, balance_minimo from (
			select max(if(idconfiguracion=1, comision, 0)) as comision, max(if(idconfiguracion=4, comision, 0)) as balance_minimo
			from configuracion ) as configuracion');
		$comision = 0;
		//$minimunBalance = 0;
		if( count($config) > 0 ){
			$configRow = $config[0];
			$comision = $configRow->comision;
			//$minimunBalance = $configRow->balance_minimo;
		}
		$kfees = $comision / 100.0;
		//$userId = $data["idUser"];
		/*
		["error" => false,
		"data" => [
			"balance" => 100
		] ]
		*/
		//$connection->select("select sum(valor) balance from balances_conductores group by idusuarios");
		$query = $connection->table('balances_conductores')
            ->selectRaw('if(operation = "1", valor, -1 * valor) as balance')->where([
                ["idusuarios","=", $userId],
                ["response","=", "succeeded"],
                
            ])->groupBy(["idusuarios"])->sum(DB::raw('if(operation = "1", valor, -1 * valor)'));
		//SELECT YEAR(creado) y, WEEKOFYEAR(creado) w, min(creado) as first, SUM(valor) total, GROUP_CONCAT(idservicios) ids 
		//FROM `servicios` where estado = 'Terminado' and servicios.tipo_pago = "Tarjeta Crédito" group by YEAR(creado), WEEKOFYEAR(creado)
		$tServiceCard = $connection->select("SELECT SUM(valor) gross, SUM(valor * $kfees) comision, SUM(valor) - SUM(valor * $kfees) total, GROUP_CONCAT(idservicios) ids 
		FROM `servicios` where usuarios_id=$userId and estado = 'Terminado' and servicios.ispagado in ('Pendiente') 
		and (servicios.tipo_pago = \"Tarjeta Crédito\" OR servicios.tipo_pago = \"Yappy\" OR servicios.tipo_pago = \"pago_cash\" OR servicios.tipo_pago = \"transferencia\")")[0]->total;
		$feeServiceCash = $connection->select("SELECT SUM(valor * $kfees) total, GROUP_CONCAT(idservicios) ids 
		FROM `servicios` where usuarios_id=$userId and estado = 'Terminado' and servicios.ispagado in ('Pendiente') and servicios.tipo_pago = \"Efectivo\"")[0]->total;
//        SELECT sum(valor) FROM `conductor_servicios` inner join servicios on servicios.idservicios = servicios_id where conductores_id=94 and servicios.estado = "terminado" and servicios.tipo_pago="efectivo";
/**
 * SELECT
	s.id,
    (s.precio_real) AS valor,
    s.tipo_pago AS tipo_pago,
    s.tipo_pago AS TP,
    (DS.endTime) AS endTime,
    'CONDUCTOR' AS role,
    'Pendiente' AS ispagado
FROM
    driver_services AS DS
INNER JOIN services AS s
ON
    DS.service_id = s.id
WHERE
    DS.driver_id =(
    SELECT
        userable_id
    FROM
        users
    WHERE
        id = 2556
   
 LIMIT 1
) AND s.tipo_pago = "Efectivo"  AND s.estado = 'Terminado'
ORDER BY s.id;

SELECT s.idservicios, (s.valor) AS valor, s.tipo_pago AS tipo_pago, cs.estado AS estadoF, (cs.endTime) AS endTime, cs.estado AS cestado, cs.ispagado AS ispagado, cs.role FROM conductor_servicios AS cs JOIN servicios AS s ON cs.servicios_id = s.idservicios WHERE cs.conductores_id = 94 AND s.estado IN('Terminado') AND cs.estado IN('Terminado') AND s.tipo_pago in ("card", 'Tarjeta Crédito', 'Yappy', 'pago_cash', 'Transferencia') ORDER BY `s`.`idservicios` ASC;

SELECT s.idservicios, (s.valor) AS valor, s.tipo_pago AS tipo_pago, cs.estado AS estadoF, (cs.endTime) AS endTime, cs.estado AS cestado, cs.ispagado AS ispagado, cs.role FROM conductor_servicios AS cs JOIN servicios AS s ON cs.servicios_id = s.idservicios WHERE cs.conductores_id = 2556 AND s.estado IN('Terminado') AND s.tipo_pago = 'Efectivo' ORDER BY `s`.`idservicios` ASC
SELECT s.id, (s.precio_real) AS valor, s.tipo_pago AS tipo_pago, s.tipo_pago AS TP, (DS.endTime) AS endTime, 'CONDUCTOR' AS role, 'Pendiente' AS ispagado FROM driver_services AS DS INNER JOIN services AS s ON DS.service_id = s.id WHERE DS.driver_id =( SELECT userable_id FROM users WHERE id = 94 LIMIT 1 ) AND s.tipo_pago in ("card", 'Tarjeta Crédito', 'Yappy', 'pago_cash', 'Transferencia') AND s.estado = 'Terminado' and DS.status = "Terminado" ORDER BY s.id;

--------
SELECT count(*), driver_services.service_id, driver_services.driver_id FROM `driver_services` GROUP BY service_id, driver_id having count(*) > 1;
 */
		//if( $query > 0 ){
            //return json_encode([$tServiceCard, $query, $feeServiceCash]);
            //echo "SC: ".$tServiceCard." B: ". $query. " SE: ".$feeServiceCash;
			return $tServiceCard + $query - $feeServiceCash;
		//}
        //echo "SC: ".$tServiceCard." SE: ".$feeServiceCash;
		//return $tServiceCard - $feeServiceCash;        
    }
    public function getDriverTransactions($userId){
        if(!self::ENABLE){
            return false;
        }     
        $connection = DB::connection("mysql_old");

		$comision = $connection->table("configuracion")->where([
            ["idconfiguracion", "=", 1]
        ])->first()->comision;
		$kfees = $comision/100.0;	
	
		//SELECT YEAR(creado) y, WEEKOFYEAR(creado) w, min(creado) as first, SUM(valor) total, GROUP_CONCAT(idservicios) ids 
		//FROM `servicios` where estado = 'Terminado' and servicios.tipo_pago = "Tarjeta Crédito" group by YEAR(creado), WEEKOFYEAR(creado)
		$tServiceCard = $connection->select('SELECT
			sha1(concat(idservicios, "_", s.tipo_pago)) as hcode,
			idservicios as id,
			(s.valor * '.$kfees.') AS comision,
			(s.valor) - (s.valor * '.$kfees.') AS total,
			(s.valor) AS valor,
			0.0 as tax,
			s.tipo_pago AS tipo_pago,
			cs.estado AS estadoF,
			(cs.endTime) AS endTime,
			cs.estado AS cestado,
			cs.ispagado AS ispagado,
			s.creado AS created_at,
			s.tipo_pago AS gateway,
			cs.role
		FROM
			conductor_servicios AS cs
		JOIN servicios AS s
		ON
			cs.servicios_id = s.idservicios
		WHERE
			cs.conductores_id = ? AND cs.ispagado IN(\'Pendiente\') AND cs.estado IN(\'Terminado\') AND s.tipo_pago = \'Efectivo\'
		UNION
		SELECT
			sha1(concat(idservicios, "_", s.tipo_pago)) as hcode,
			idservicios as id,
			(s.valor * '.$kfees.') AS comision,
			(s.valor) - (s.valor * '.$kfees.') AS total,
			(s.valor) AS valor,
			0.0 as tax,
			s.tipo_pago AS tipo_pago,
			cs.estado AS estadoF,
			(cs.endTime) AS endTime,
			cs.estado AS cestado,
			cs.ispagado AS ispagado,
			s.creado AS created_at,
			if(s.tipo_pago = \'Yappy\', s.tipo_pago, \'Stripe\') AS gateway,
			cs.role
		FROM
			conductor_servicios AS cs
		JOIN servicios AS s
		ON
			cs.servicios_id = s.idservicios
		WHERE
			cs.conductores_id = ? AND cs.ispagado IN(\'Pendiente\') AND cs.estado IN(\'Terminado\') 
			AND (s.tipo_pago = \'Tarjeta Crédito\' or s.tipo_pago = \'Yappy\' or s.tipo_pago = \'pago_cash\' or s.tipo_pago = \'transferencia\' )
		UNION
		SELECT
			sha1(concat(id, "_", if(operation = "2", \'Retiro\', \'Deposito\'))) as hcode,
			id,
			0.0 as comision,
			(bc.valor) AS total,
			(bc.valor) AS valor,
			tax as tax,
			if(operation = "2", \'Retiro\', \'Deposito\') AS tipo_pago,
			bc.response AS estadoF,
			if(bc.fecha is null, if(bc.updated_at is null,  \'\', bc.updated_at), bc.fecha) AS endTime,
			response AS cestado,
			IF(
				bc.response = "preordered",
				"Pendiente",
				bc.response
			) AS ispagado,
			bc.created_at AS created_at,
			bc.gateway_type AS gateway,
			UPPER(roles.rol) AS role
		FROM
			balances_conductores AS bc
		LEFT JOIN roles ON roles.id_rol = bc.id_role
		WHERE
			bc.idusuarios = ?
			and bc.response = \'succeeded\'
			order By created_at DESC', [$userId, $userId, $userId]);
		return $tServiceCard;
    }
    public function deleteDriverUser($userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->where("idusuarios", $userId)->delete();
    }
    //--------------- END Driver methods ---------------
    /*
		Calcula la distancia entre el conductor y el punto de partida del servicio.
	*/
	private function calculateDistanceToUser($servicioId, $userLat, $userLng){
        if(!self::ENABLE){
            return false;
        }     
        $connection = DB::connection("mysql_old");
        $service = $connection->table("servicios")->where("idservicios", $servicioId)->first();
		$coordinates = json_decode($service->coordenas);
		
		if(is_object($coordinates)){ //When is services generated by invitados.kamgus.com
			$auxCoordinates = $coordinates;
			$coordinates = [];
			$coordinates[0] = $auxCoordinates;
		}
		if(empty($coordinates) || empty($coordinates[0]->coord_punto_inicio)){
			return ["error", "=", true, "msg" => "Service not found"];	
			//return ["error" => true, "msg" => "Service not found"];
			
		}
		$coordinates = explode(",", $coordinates[0]->coord_punto_inicio);
		$dMatrix = getDistanceBetween($userLat, $userLng, $coordinates[0], $coordinates[1]);
		
		if($dMatrix === false || $dMatrix["resultado"] == "ZERO_RESULTS" || $dMatrix["resultado"]["status"] !== "OK"){
			return ["error" => true, "msg" => "Tiempo estimado no disponible"] ;	
			
		}
		return $dMatrix;
	}


    /** ----------------------------------------------------------- BEGIN INVITED OPERATIONS ----------------------------------------------------------- */
    public function createServiceInvited($data, $userId){
        if(!self::ENABLE){
            return false;
        }
        $data['usuarios_id'] = $userId;
        $connection = DB::connection("mysql_old");
        $kms = $data['kms'] / 1000;		
		$duration = $data['tiempo'] / 60;
		$valor = round($data['valor']);
	
		$description = empty($data["description"]) ? null : $data["description"];
		$assistant = (empty($data["assistant"]) || $data["assistant"] === "false") ? 0 : $data["assistant"];
        $traslado = $data['tipo_translado'];
		if($traslado == "articulo" || $traslado == "Simple"){
			$traslado = 'Simple';
			$assistant = 1;
		}else if ($traslado == "vehiculo"){
			$traslado = 'Mudanza';	
		}
        if( $data['fecha_reserva'] == 'now'){
        	$fechNueva = date('Y-m-d H:i:s');
        }else{
        	$date = new DateTime( $data['fecha_reserva'] );
            $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
			$fechNueva =  $date->format('Y-m-d H:i:s');
        }    

        $coordenas = json_decode(str_replace('\"','"', $data['coordenas']), true);
        //die($data['coordenas']);
        //die(json_encode($coordenas));
		$lastId = $connection->table("servicios")->insertGetId([
            "usuarios_id" =>  $data['usuarios_id'],
            "empleado_id" =>  $data['usuarios_id'],
            "valor" =>  $valor,
            "punto_referencia" =>  '',
            "inicio_punto" =>  $data['punto_inicial'],
            "punto_final" =>  $data['punto_final'],
            "coordenas" =>  json_encode($coordenas),
            "fecha_reserva" =>  $fechNueva,
            "tipo_translado" =>  $traslado,
            "estado" =>  $data['estado'] == 'ACTIVO' ? 'Pendiente' : 'Reserva',
            "creado" =>  date('Y-m-d H:i:s'),
            "kms" =>  $data['kms'],
            "tipo_pago" =>  ($data['tipo_pago'] == 'credito') ? 'Tarjeta Crédito' : $data['tipo_pago'],
            "tiempo" =>  $data['tiempo'],
            "id_tipo_camion" =>  $data['id_tipo_camion'] == 6 ? 8 : $data['id_tipo_camion'],
            "tokenTarjeta" =>  $data['tokenTarjeta'],
            "Response" =>  '',
            "foto" =>  '',
            "descuento" =>  0,
            "puntos_coord" =>  '',
            "description" =>  $description,
            "assistant" =>  $assistant ? 1 : 0
        ]);
        
               
		if( $lastId ){
            if ($traslado == 'Simple') {
                # code...
                $articulos = $data['articulos'];
                $nArticulos = json_decode($articulos);
                //dd($request->articulos);
                foreach ($nArticulos as $key => $value) {
                    $nArticulos[$key]->id = K_HelpersV1::ARTICLES_IDS[$nArticulos[$key]->id];
                }
                $articulos = json_encode($nArticulos);
                $connection->table("servicios_objetos")->insertGetId([
                    "servicios_id" =>  $lastId,
                    "lista_objetos" =>  $articulos, 
                    "estado" =>  'A',
                ]);
            }
			// agregar en la nueva tabla
            $connection->table("precio_sugerido")->insertGetId([
                "servicio_id" =>  $lastId,
                "usuario_id" =>  $data['usuarios_id'], 
                //"precio" =>  $data['precio'],
                "precio" =>  $data['valor'],
                "tiempo" =>  0,
            ]);
			// agregar en la nueva tabla
		

			$queryS = $connection->table("servicios")->where("idservicios", $lastId);
			
			//exit(json_encode($_FILES["image_description"]));
			if(!empty($_FILES["image_description"]) || !empty($data["image_description"])){

			}
            return $lastId;
		}else{
            return false;

		}    	
    }
    public function registerStripeTransaction($data, $userId){
        if(!self::ENABLE){
            return false;
        }     
        $connection = DB::connection("mysql_old");
        $updated = $connection->table("servicios")->where("idservicios", $data["service_id"])->update([
            'tipo_pago' =>  "Tarjeta Crédito",
            'Response' => $data["status"],
            'tokenTarjeta' => $data["transaction_id"],
        ]);
        return $updated; 
        
    }

    public function registerYappyTransaction($data, $userId){
        if(!self::ENABLE){
            return false;
        }     
        $connection = DB::connection("mysql_old");
        $params = [
            'Response' => $data["status"],
            //'tokenTarjeta' => $data["id"],
        ];
        if(!empty($data["id"])){
            $params = [
                'Response' => $data["status"],
                'tokenTarjeta' => $data["transaction_id"],
            ];
        }
        $updated = $connection->table("servicios")->where("idservicios", $data["service_id"])->update($params);
        return $updated; 
    }
    public function updateStripeTransaction($data, $userId){
        if(!self::ENABLE){
            return false;
        }     
        $connection = DB::connection("mysql_old");
        $params = [
            'Response' => $data["status"],
            //'tokenTarjeta' => $data["id"],
        ];
        if(!empty($data["id"])){
            $params = [
                'Response' => $data["status"],
                'tokenTarjeta' => $data["transaction_id"],
            ];
        }
        $updated = $connection->table("servicios")->where("idservicios", $data["service_id"])->update($params);
        return $updated; 
    }

    public function registerFcmToken($userId, $token, $platform, $role = 2){
        if(!self::ENABLE){
            return false;
        }     
        $connection = DB::connection("mysql_old");
        $updated = $connection->table("fcm_tokens")->where("token", $token)->update([
            "id_usuario" => $userId,
            "platform" => $platform,
            "role_id" => $role,
        ]);
        return $updated;
    }
    /** ----------------------------------------------------------- END INVITED OPERATIONS ----------------------------------------------------------- */

    /** ----------------------------------------------------------- BEGIN ADMIN OPERATIONS ----------------------------------------------------------- */
    public function test(){

    }
    public function migrateUsers(){
        if(!self::ENABLE){
            return false;
        }     
        //SELECT count(celular) conteo, celular, GROUP_CONCAT(email) as emails FROM `usuarios` GROUP BY celular having conteo > 1 order by conteo DESC

        $connection = DB::connection("mysql_old");
        //Consultar usuarios
        //Iterar cada usuario
            //Que email no este vacio
            //verificar que no exista el numero de telefono ya registrado. 
                //
            //Si no esta registrado ? registrar usuario con el mismo Id : nada
            //registrar fotos de licencia, cedula y perfil en images


    }
    public function migrateServices(){
        
    }
    public function registerEnterprise($request){
        if(!self::ENABLE){
            return false;
        }
        $param = [
            "nombre" => $request["nombres"],
            "apellidos" => $request["apellidos"],
            "celular" => $request["telefono"],
            "email" => trim($request["email"]),
            "password" => md5($request["password"]),
            "pais_idpais" => 176,
            "tipo_idtipos_documento" => 1,
            "tipo_registro" => 'F',
            //"url_foto" => $request["url_foto"],
            "token" => "",
            "estado" => 0,
            //"url_licencia" => $request["url_licencia"],
            "rol" => 5,
            "codigounico" => strtoupper(substr($request["nombres"],0,3).substr($request["apellidos"],0,1).substr(uniqid(), 0, 4)),
        ];
        
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->insertGetId($param);


    }
    public function updateDriverServicePaymentByIds($userId, $serviceIds = []){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("conductor_servicios")
            ->where("conductores_id", $userId)
            ->whereIn("servicios_id", $serviceIds)
            ->update(["ispagado" => 'Pagado']);
    }
    public function updateDriverServicePayment($userId, $interval = ""){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        if($interval === 'semanal'){
            return $connection->table("conductor_servicios")
                ->where("conductores_id", $userId)
                ->whereRaw("WEEK(endTime) BETWEEN (WEEK(CURRENT_DATE()) - 1) AND WEEK(CURRENT_DATE())")
                ->whereIn("ispagado", ['Pendiente'])
                ->update(["ispagado" => 'Pagado']);
        }
        return $connection->table("conductor_servicios")
            ->where("conductores_id", $userId)
            ->whereRaw("WEEK(endTime) < (WEEK(CURRENT_DATE()) - 1)")
            ->whereIn("ispagado", ['Pendiente'])
            ->update(["ispagado" => 'Pagado']);
    }
    public function  getopcionesNV3($idLevel2){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("articulos_acarreo_nivel_2")->where("id_articulo_acarreo", $idLevel2)->get();
    }
    
    //
    public function registerUserLicence($userId, $licenceId){
        $uuid = uniqid();
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("licence_user")->insertGetId([
            "id_user" => $userId,
            "id_licence" => $licenceId,
            "licence" => $uuid,
            "status" => 1,
        ]);
    }
    public function deleteUser($userId){
        if(!self::ENABLE){
            return false;
        }
        $connection = DB::connection("mysql_old");
        return $connection->table("usuarios")->where("idusuarios", $userId)->delete();
    }

    /** ----------------------------------------------------------- END ADMIN OPERATIONS ----------------------------------------------------------- */
}
