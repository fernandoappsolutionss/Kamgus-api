<?php

use App\Classes\K_HelpersV1;
use App\Models\DriverService;
use App\Models\Service;
use App\Models\User;

if(!function_exists('notifyToDriver')){
    function notifyToDriver($to, $title, $description, $data = []){
        //$expoNotification = new ExpoNotifications();
        //$expoNotification->notify($to, $title, $description, $data);		
		$data['content-available'] = 1;
		$payload = array(
			//'to' => 'ExponentPushToken[Jgi73JB6OxfeV0U38lqFoK]',
			/*"to": [ // hasta 100 objetos
			  "ExponentPushToken[zzzzzzzzzzzzzzzzzzzzzz]",
			  "ExponentPushToken[aaaaaaaaaaaaaaaaaaaaaa]"
			],*/
			'to' => $to,
			'sound' => 'default',
			'title' => $title,
			'body' => $description,
			'data' => $data,
			'priority' => 'high',
			'content-available' => 1,

		);
		$curl = curl_init();
		//dd(config('exponent-push-notifications.AUTHENTICATION_KEY_EXPO'));
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://exp.host/--/api/v2/push/send",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => json_encode($payload),
		  CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"Accept-Encoding: gzip, deflate",
			"Content-Type: application/json",
			"cache-control: no-cache",
			"host: exp.host",
			'Authorization: '.'Bearer '.config('exponent-push-notifications.AUTHENTICATION_KEY_EXPO'),
		  ),
		));
	
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		//exit($response);
		return $response;
    }
}
if(!function_exists('notifyToCustomer')){
	//peticion http POST a https://fcm.googleapis.com/v1/projects/kamgus/messages:send HTTP/1.1
	function notifyToCustomer($to, $title, $description, $data = []){
		$payload = array(
			//'to' => 'ExponentPushToken[Jgi73JB6OxfeV0U38lqFoK]',
			/*"to": [ // hasta 100 objetos
			  "ExponentPushToken[zzzzzzzzzzzzzzzzzzzzzz]",
			  "ExponentPushToken[aaaaaaaaaaaaaaaaaaaaaa]"
			],*/
			"message" => array(
				"token" => $to,
				"notification" => array(
					"title" => $title,
					"body" => $description,
					"sound" => "default",
				),
				"data" => $data,
			),

		);
		$curl = curl_init();
		//dd(config('larafirebase.authentication_key'));
		curl_setopt_array($curl, array(
		  //CURLOPT_URL => "https://fcm.googleapis.com/v1/projects/kamgus/messages:send",
		  CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => json_encode($payload),
		  CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"Accept-Encoding: gzip, deflate",
			"Content-Type: application/json",
			"cache-control: no-cache",
			'Authorization: '.'key='.config('larafirebase.authentication_key'),
		  ),
		));
	
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		return $response;
	}
	
}
if(!function_exists('getDistanceBetween')){
    function getDistanceBetween($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo){
            
        
        $postcode1 = $latitudeFrom.','.$longitudeFrom;
        $postcode2 = $latitudeTo.','.$longitudeTo;

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$postcode1&destinations=$postcode2&mode=driving&sensor=true&key=AIzaSyC8vsLlcGNy-7rC5Uajw6KjyCWmjs-pd-o";

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $data = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch); 
        $result = json_decode($data, true);
        if($result["status"] !== "OK"){
            return false;
        }
        return array(
            'coords'=> array(
                'coord_punto_inicio' => $postcode1,
                'coord_punto_final' => $postcode2
            ),
            'resultado' => $result["rows"][0]["elements"][0],
        );

    }
}
if(!function_exists('getPlatform')){
    function getPlatform(){
		$useragent=$_SERVER['HTTP_USER_AGENT'];
		$platform = null;
		//return $useragent;
		if(strpos(strtolower($useragent), "android") !== false){
			$platform = 'android';
		}else
		if((strpos(strtolower($useragent), "iphone") !== false) || (strpos(strtolower($useragent), "apple") !== false)){
			$platform = 'ios';
		}
		return $platform;
	}
}
if(!function_exists('hasDbConnection')){
	function hasDbConnection($name){
		try {
			\DB::connection($name);
			return true;
		} catch (\Throwable $th) {
			//throw $th;
			return false;
		}
		return false;
	}
}
if(!function_exists('getDistanceBetween')){
    function getDistanceBetween($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo){
            
        
        $postcode1 = $latitudeFrom.','.$longitudeFrom;
        $postcode2 = $latitudeTo.','.$longitudeTo;

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$postcode1&destinations=$postcode2&mode=driving&sensor=true&key=AIzaSyC8vsLlcGNy-7rC5Uajw6KjyCWmjs-pd-o";

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $data = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch); 
        $result = json_decode($data, true);
        if($result["status"] !== "OK"){
            return false;
        }
        return array(
            'coords'=> array(
                'coord_punto_inicio' => $postcode1,
                'coord_punto_final' => $postcode2
            ),
            'resultado' => $result["rows"][0]["elements"][0],
        );

    }
}
if(!function_exists('calculateDriverBalanceOld')){
	function calculateDriverBalanceOld($userId, $table){
		if(K_HelpersV1::ENABLE){
			return K_HelpersV1::getInstance()->calculateDriverBalance($userId);
		}
		$config = \DB::select('SELECT configurations.comision, balance_minimo from (
			select max(if(configurations.id=1, comision, 0)) as comision, max(if(configurations.id=4, comision, 0)) as balance_minimo
			from configurations ) as configurations');
		$comision = 0;
		//$minimunBalance = 0;
		if( count($config) > 0 ){
			$configRow = $config[0];
			$comision = $configRow->comision;
			//$minimunBalance = $configRow->balance_minimo;
		}
		$kfees = $comision / 100.0;

		/*
		$serviceCash = Service::where([
			["driver_id", "=", User::find($userId)->userable_id],
			["estado", "=", "TERMINADO"],
		])->sum("precio_real");
			*/
		$driverInfo = User::find($userId)->userable_id;
		$serviceCash = Service::where([
			["services.driver_id", "=", $driverInfo],
			["services.tipo_pago", "=", "Efectivo"],
			["services.estado", "=", "terminado"],
		])
		//->join("transactions", "transactions.service_id", "=", "services.id")
		->sum("precio_real")
		;
		$serviceCash *= $kfees;
		$serviceCard = Service::where([
			["services.driver_id", "=", $driverInfo],
			//["services.tipo_pago", "=", "Card"],
			["services.estado", "=", "terminado"],
		])
		->whereIn("services.tipo_pago", ["Card", "Yappy", "PagoCash", "transferencia"])
		//->join("transactions", "transactions.service_id", "=", "services.id")
		->sum(DB::raw("(precio_real - (precio_real * $kfees))"))
		;
		$serviceCard = $serviceCard - ($serviceCard * $kfees);
		//dd($serviceCash);
		//return $serviceCash;
		$balance = $table
			//->leftJoin("services", "transactions.service_id", "=", "services.id")
			->where([
				["transactions.user_id", "=", $userId],
				["status", "=", "succeeded"],
			])
			->whereNull("service_id")
			//->select([DB::raw("sum(if(service_id is not null, if(services.tipo_pago = 'Efectivo', amount * $kfees, amount - (amount* $kfees)), amount)) as balance")]);
			->select([DB::raw("sum(amount) as balance")]);
		//echo "<br>"."SCC: ".$serviceCard." B: ".$balance->get()[0]->balance." SE: ".$serviceCash."<br>";
		return $serviceCard + $balance->get()[0]->balance - $serviceCash;
	}
}
if(!function_exists('calculateDriverBalance')){
	function calculateDriverBalance($userId, $table){
		
		$config = \DB::select('SELECT configurations.comision, balance_minimo from (
			select max(if(configurations.id=1, comision, 0)) as comision, max(if(configurations.id=4, comision, 0)) as balance_minimo
			from configurations ) as configurations');
		$comision = 0;
		//$minimunBalance = 0;
		if( count($config) > 0 ){
			$configRow = $config[0];
			$comision = $configRow->comision;
			//$minimunBalance = $configRow->balance_minimo;
		}
		$kfees = $comision / 100.0;

		/*
		$serviceCash = Service::where([
			["driver_id", "=", User::find($userId)->userable_id],
			["estado", "=", "TERMINADO"],
		])->sum("precio_real");
			*/
		$driverInfo = User::find($userId)->userable_id;
		$ds = DriverService::where([
			["driver_id", "=", $driverInfo]
		])->where("driver_services.status", "terminado")->get();
		$serviceCash = Service::where([
			["services.driver_id", "=", $driverInfo],
			["services.tipo_pago", "=", "Efectivo"],
			["services.estado", "=", "terminado"],
		])
		
		->whereIn("services.pago", [
			"pendiente", 
		//	"transferido"
		])
		->whereIn("services.id", $ds->pluck("service_id"))
		//->join("transactions", "transactions.service_id", "=", "services.id")
		->sum(DB::raw("(precio_real * $kfees)"))
		;
		//$serviceCash *= $kfees;
		$serviceCard = Service::where([
			//["driver_services.driver_id", "=", $driverInfo],
			//["services.tipo_pago", "=", "Card"],
			["services.estado", "=", "terminado"],
		])
		->whereIn("services.id", $ds->pluck("service_id"))
		->whereIn("services.pago", [
			"pendiente", 
		//	"transferido"
		])
		->whereIn("services.tipo_pago", [
			"Card", 
			"Yappy", 
			//"PagoCash"
            "transferencia",
		])
		//->join("transactions", "transactions.service_id", "=", "services.id")
		->select(DB::raw("SUM(precio_real) - SUM(precio_real * $kfees) total"))
		->first()->total
		;
		//dd($serviceCard->get());
		$serviceCard = $serviceCard;
		//dd($serviceCash);
		//return $serviceCash;

		$balance = $table
			//->leftJoin("services", "transactions.service_id", "=", "services.id")
			->where([
				["transactions.user_id", "=", $userId],
			])
			->whereIn("status", \App\Models\Transaction::SUCCESS_STATES)
			->whereNull("service_id")
			//->select([DB::raw("sum(if(service_id is not null, if(services.tipo_pago = 'Efectivo', amount * $kfees, amount - (amount* $kfees)), amount)) as balance")]);
			->select([DB::raw("sum(transactions.amount) as balance")]);
		
			//echo "<br>"."SCC: ".$serviceCard." B: ".$balance->get()[0]->balance." SE: ".$serviceCash."<br>";
		return $serviceCard + $balance->get()[0]->balance - $serviceCash;
	}
}