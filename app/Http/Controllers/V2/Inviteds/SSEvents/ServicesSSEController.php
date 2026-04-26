<?php

namespace App\Http\Controllers\V2\Inviteds\SSEvents;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleService;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\FcmToken;
use App\Models\Image;
use App\Models\Route;
use App\Models\Service;
use App\Models\SubCategory;
use App\Models\Transaction;
use App\Models\TypeTransport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Notifications\SendExpoPushNotification;
use DateTime;
use Illuminate\Support\Facades\Storage;
use DB;
class ServicesSSEController extends Controller
{
    const TITLE = "SSE_SUGGESTED_PRICE";
    const DELAY = 1;
    public function __construct()
    {
        header("X-Accel-Buffering: no");
        header("Content-Type: text/event-stream");
        header("Cache-Control: no-cache");
    }
    public function suggestedPrices(){
        $lastPrice = 0;
        $serviceId = request()->sid;
        while (1) {
            // 1 is always true, so repeat the while loop forever (aka event-loop)
            
            $suggestedPrices = DriverService::where([
                ['service_id', "=", $serviceId],
                //['status', "=", "Pendiente"],
            ])->whereIn("status", ["Pendiente", "Reserva"])->count();
            // Send a simple message at random intervals.
            echo "event: ".self::TITLE."\n",
                    'data: '.json_encode([
                        "hasMore" => $suggestedPrices > $lastPrice,
                    ]), "\n\n";
            
            // flush the output buffer and send echoed messages to the browser
            $lastPrice = $suggestedPrices > $lastPrice ? $suggestedPrices : $lastPrice;
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
            // break the loop if the client aborted the connection (closed the page)
            if ( connection_aborted() ) break;
        
            // sleep for 1 second before running the loop again
            sleep(self::DELAY);
        }
    }
}