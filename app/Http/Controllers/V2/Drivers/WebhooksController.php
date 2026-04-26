<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhooksController extends Controller
{
    //
    public function yappyStatus(Request $request){
        Log::debug("WebhooksController - yappyStatus: ". json_encode($request->all()));
      }
      public function stripeStatus(Request $request){
        Log::debug("WebhooksController - stripeStatus: ". json_encode($request->all()));
      }
      public function pagoCashStatus(Request $request){
        Log::debug("WebhooksController - pagoCashStatus: ". json_encode($request->all()));
        if(!empty($request->CodOper)){
          $totalPay = $request->TotalPay;
          $fecha = $request->Fecha;
          $hora = $request->Hora;
          $transaction = Transaction::where("transactions.transaction_id", $request->CodOper)->first();
          $transaction->accumulated_value += $totalPay;
          $transaction->receipt_url = "PCPEND_".floor($transaction->accumulated_value * 100.0 / ($transaction->amount + $transaction->tax));
          $transaction->description += $fecha." ".$hora."\n";
          if($transaction->accumulated_value >= ($transaction->amount + $transaction->tax)){
            $transaction->status = "complete";
          }
          $transaction->save();
        }
      }
}
