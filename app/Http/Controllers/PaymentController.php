<?php

namespace App\Http\Controllers;

use App\Classes\StripeCustomClass;
use App\Constants\Constant;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Cashier\Exceptions\InvalidCustomer;
use Stripe\Stripe;

class PaymentController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //usar middlewares
        // $this->middleware('auth');
        // $this->middleware('log')->only('index');
        // $this->middleware('subscribed')->except('store');
    }

    public function own_payment_methods(){

        $paymentMethods = Auth::user()->paymentMethods();
        //$paymentMethods = StripeCustomClass::getInstance()->getPaymentMethods(Auth::user()->stripe_id);
        return response()->json(['own_payment_methods' => $paymentMethods]);

    }

    public function add_payment_method(){
        $user = Auth::user();
        if(empty($user->stripe_id)){
            $user->createAsStripeCustomer();
        }

       //return $user->createSetupIntent();
       return StripeCustomClass::getInstance()->createSetupIntent($user->stripe_id);

    }

    public function balance(){

        return response()->json(['balance' => Auth::user()->balance()]);

    }

    public function invoinces(){

        $user = Auth::user();

        return response()->json(['facturas' => $user->invoices()]);

    }

    public function pay_service(Request $request){

        $rules = [
            'service_id' => 'required',
            'paymentMethodId' => 'required'
        ];

        $this->validate($request, $rules);

        $service = Service::findOrFail($request->service_id);

        $stripeCharge = Auth::user()->charge(
            $service->precio_real, $request->paymentMethodId
        );

        $transaction = new Transaction();
        $transaction->user_id = Auth::user()->id;
        $transaction->service_id = $request->service_id;
        $transaction->type = $stripeCharge->charges->data[0]->payment_method_details->card->brand;
        $transaction->amount = $stripeCharge->charges->data[0]->amount;
        $transaction->currency = $stripeCharge->charges->data[0]->currency;
        $transaction->transaction_id = $stripeCharge->charges->data[0]->id;
        $transaction->status = $stripeCharge->status;
        $transaction->receipt_url = $stripeCharge->charges->data[0]->receipt_url;
        $transaction->save();

        if(array_search($stripeCharge->status, Transaction::SUCCESS_STATES) !== false){ //Evalua si trasaccion fue exitosa
            $service->pago = 'PAGADO';
            if($service->save()){
                return response()->json(['msg' => Constant::SUCCESSFUL_PAYMENT]);
            }
        }

    }
    
    //agregar nuevo método de pago
    public function add_new_payment_method(Request $request){

        $rules = [
            'name' => 'required|max:255',
            'card_number' => 'required|max:255',
            'expYear' => 'required|max:2',
            'monthExp' => 'required|max:2',
            'cvv' => 'required|max:4',
        ];

        $this->validate($request, $rules);

        
        //\Stripe\Stripe::setApiKey("***REMOVED-STRIPE-PUB***");
        \Stripe\Stripe::setApiKey(StripeCustomClass::getInstance()->getPublicKey());

        try {
            $card = \Stripe\PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->monthExp,
                    'exp_year' => $request->expYear,
                    'cvc' => $request->cvv,
                ],
                'billing_details' => [
                    'name' => $request->name,
                ],
            ]);
    
            $user = Auth::user()->addPaymentMethod($card->id);
    
            if($user){
                
                return response()->json(['msg' => Constant::ADD_CARD]);
    
            }
        } catch (InvalidCustomer $exception) {
            $message = [
                "User is not a Stripe customer yet. See the createAsStripeCustomer method." => "El usuario aun no tiene un customer_id de stripe asociado. Debe crear y agregar un customer_id de stripe primero."
            ];
            return response()->json(['error' => true, 'msg' => empty($message[$exception->getMessage()]) ? $exception->getMessage() : $message[$exception->getMessage()]], self::HTTP_BAD_REQUEST);
        } catch (\Throwable $th) {
            return response()->json(['error' => true, 'msg' => $th->getMessage()], 422);
        }

    }
    public function stripeRedirected(){
        if(request()->status == "successfully"){
            return redirect("https://app.kamgus.com/");
        }
    }
    public function destroy($id){ //remove, 
        $user = Auth::user();
        //StripeCustomClass::getInstance()->deletePaymentMethods($user->stripe_id, $id);
        StripeCustomClass::getInstance()->removePaymentMethod($id);
        return response(null, 204);
    }
}
