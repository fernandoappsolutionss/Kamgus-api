<?php

namespace App\Listeners;

use App\Events\CustomerServiceCancelled;
use App\Mail\StatusService;
use App\Models\FcmToken;
use App\Models\Service;
use App\Models\User;
use App\Notifications\K_SendPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendCustomerServiceCancelledNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\CustomerServiceCancelled  $event
     * @return void
     */
    public function handle(CustomerServiceCancelled $event)
    {
        // Access the order using $event->order...
        $this->sendServiceStatus($event->driver, $event->servicioId, $event->customerId, $event->estado);
    }
    private function sendServiceStatus($driver, $servicioId, $customerId, $estado){
        /**
         * Extraer fcm tokens del conductor y notificar al conductor el estado del servicio
         */
        $userId = $customerId;
        //Notificar al cliente
        Mail::to(User::find(Service::find($servicioId)->user_id)->email)->send(new StatusService('Estado de servicio',"Su servicio ha sido cancelado", $estado));
        $tokens = FcmToken::where("user_id", $userId)->get()->pluck("token");
        if(!empty($tokens)){
            //notifica por firebase el estado del servicio
            User::find($userId)->notify(new K_SendPushNotification('Estado de servicio',"Su servicio ha sido cancelado", $tokens->toArray(), [
                //"key" => "SERVICE_REJECT",
                //"n_date" => date("Y-m-d H:i:s")
                //"url" => "/driver-confirmation",
            ]));
        }
        //Notificar al conductor
        if(empty($driver)){
            return;
        }
        $tokens = FcmToken::where("user_id", $driver->id)->get();
        foreach ($tokens as $key => $fcm) {
            $token = $fcm->token;
            notifyToDriver($token,  "Estado del servicio", "Cliente ha cancelado el servicio activo", [
                //"url" => "ServActScreen",
                "key" => "CANCELLED_DRIVER",
            ]);
        }
        //Envia por email el estado del servicio
        Mail::to($driver->email)->send(new StatusService('Estado de servicio',"Cliente ha cancelado el servicio activo", $estado));
    }
}
