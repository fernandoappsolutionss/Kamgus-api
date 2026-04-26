<?php

namespace App\Console\Commands\Admin;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Events\CustomerServiceCancelled;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use DB;
class CancelOldServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:old_services';
    const TIME_LIMIT = 20; // tiempo en minutos
    private $serviceLimit = "";
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel all services which have \'Pendiente\' status.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("CancelOldServices - ".date("Y-m-d H:i:s"));
        //return;
        //$this->info("refresh:old_services runned.");
        if($this->haveServices()){
            $this->cancelServices();
            Log::info("End CancelOldServices - ".date("Y-m-d H:i:s"));
        }
    }
    
    private function haveServices(){
        //if(services.fecha_reserva is not null, services.fecha_reserva, services.created_at),
        $services = DB::select('SELECT
            COUNT(*) AS cantidad
            FROM
                `services`
            WHERE
                estado IN(\'Reserva\', \'Pendiente\', \'PROGRAMAR\') AND id NOT IN(
                SELECT
                    driver_services.service_id
                FROM
                    driver_services
                WHERE
                    driver_services.service_id = services.id AND driver_services.status IN(
                        \'En Curso\',
                        \'Pendiente\',
                        \'Terminado\',
                        \'Agendado\'
                    )
            ) AND TIMESTAMPDIFF(
                MINUTE,
                services.created_at,
                ?) > ?', [date('Y-m-d H:i:s'), self::TIME_LIMIT]);
        return count($services) > 0 ? $services[0]->cantidad > 0 : 0;
    }
    private function cancelServices(){
        /*
        $affected = DB::update(
            "UPDATE services 
                set estado = 'Cancelado'  WHERE
                estado IN('Reserva', 'Pendiente') AND id NOT IN(
                SELECT
                    driver_services.service_id
                FROM
                    driver_services
                WHERE
                    driver_services.service_id = services.id AND driver_services.status IN(
                        'En Curso',
                        'Pendiente',
                        'Terminado',
                        'Agendado'
                    )
            ) AND TIMESTAMPDIFF(
                MINUTE,
                if(services.fecha_reserva is not null, services.fecha_reserva, services.created_at),
                '".date('Y-m-d H:i:s')."'
            ) > ?".$this->serviceLimit,
            [self::TIME_LIMIT]
        );
        */
        //if(services.fecha_reserva is not null, services.fecha_reserva, services.created_at)
        $services = DB::select("SELECT services.* FROM services 
                WHERE
                estado IN('Reserva', 'Pendiente', 'PROGRAMAR') AND id NOT IN(
                SELECT
                    driver_services.service_id
                FROM
                    driver_services
                WHERE
                    driver_services.service_id = services.id AND driver_services.status IN(
                        'En Curso',
                        'Pendiente',
                        'Terminado',
                        'Agendado'
                    )
            ) AND TIMESTAMPDIFF(
                MINUTE,
                services.created_at,
                '".date('Y-m-d H:i:s')."'
            ) > ?".$this->serviceLimit,
            [self::TIME_LIMIT]);
        foreach ($services as $key => $service) {
            $this->cancelService($service->user_id, $service->id);
        }
        
    }
    private function cancelService($userId, $servicioId){
		
        //$servicioId = $request->servicio_id;
        $estado = "Cancelado";
        $updated = Service::where("id", $servicioId)->update([
            "estado" => $estado,
        ]);
        K_HelpersV1::getInstance()->cancelCustomerService(["servicio_id" => $servicioId], $userId);
        if(!$updated){
            $response = array('error' => true, 'msg' => 'ERROR al cancelar el servicio ' );
            //return response()->json( $response , Controller::HTTP_ACCEPTED);
            return false;
        }
        $response = array('error' => false, 'msg' => 'El servicio fue cancelado' );
        //if($service->estado != "CANCELADO" && $service->estado != "ANULADO"){
        $ds = DriverService::where([
            ["service_id", "=", $servicioId],
        ])->first();
        $where = [
            ["userable_type", "=", Driver::class],
        ];
        if(!empty($ds)){
            $where[] = ["userable_id", "=", $ds->driver_id];
            //Registra el estado del servicio para el conductor
            DB::table("service_statuses")->insertGetId([
                "service_id" => $servicioId,
                "status" => 'Estado de servicio',
                "it_was_read" => 0,
                "description" => "Cliente ha cancelado el servicio activo",
                "servicestatetable_id" => $ds->driver_id,
                "servicestatetable_type" => Driver::class,
                "created_at" => date("Y-m-d H:i:s"),
            ]);
            if($ds->status == "En curso"){
                $ds->status = "Rechazado";
                $ds->save();
            }
        }
        $driver = User::where($where)->first();
        CustomerServiceCancelled::dispatch($driver, $servicioId, $userId, $estado); //Dispara evento para notificar al usuario y conductor via email y push notification
        if(Transaction::where([["service_id", "=", $servicioId]])->whereIn("status", Transaction::SUCCESS_STATES)->count() > 0){
            //Realizar reembolso
            $serviceTransaction = Transaction::where([["service_id", "=", $servicioId]])->whereIn("status", Transaction::SUCCESS_STATES)->first();
            switch ($serviceTransaction->gateway) {
                case 'stripe':
                    $gatewayTransactionId = $serviceTransaction->transaction_id;
                    $gatewayTransactionId = (strpos($serviceTransaction->transaction_id, "cs_") !== false) ? 
                        StripeCustomClass::getInstance()->getPaymentIntentFromCheckoutSession($gatewayTransactionId) : $gatewayTransactionId;
                    $response = StripeCustomClass::getInstance()->refundPaymentIntent($gatewayTransactionId, $serviceTransaction->total * 100, "Servicio cancelado");
                    $userId = $serviceTransaction->user_id;
                    $data = array(
                        'usuarios_id' => $userId,
                        'servicios_id' => $servicioId,
                        //'amount' => $service->precio_real,
                        //'conductores_id' => $driverId,
                        //'interfaz' => 'DEVOLVER',
                        //'IsSuccess' => $response->status === 'succeeded' ? 1 : 0,
                        'status' => $response["status"] == "succeeded" ? "canceled" : $response["status"],
                        //'ResponseSummary' => $response->reason,
                        'transaction_id' => $response["id"],
                        'created_at' => $response["created"],
                    );
                    
                    Transaction::where("id", $serviceTransaction->id)->update($data);
                    break;
                case 'yappy':
                    # code...
                    break;
                
                default:
                    # code...
                    break;
            }
        }

        //return response()->json( $response , self::HTTP_OK );
        return true;
	}
}
