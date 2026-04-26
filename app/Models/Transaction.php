<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    const METRO_TYPE = 'metro';
    const TRANSFERENCIA_TYPE = 'transferencia';
    const STRIPE_TYPE = 'stripe';
    const YAPPY_TYPE = 'yappy';
    const PAGO_CASH_TYPE = 'pago_cash';


    //Success_States
    const SUCCESS_STATES = ["succeeded", "complete"];
    protected $fillable = [
        'user_id',
        'service_id',
        'type',
        'currency',
        'amount',
        'tax',
        'total',
        'gateway',
        'transaction_id',
        'status',
        'receipt_url',
        'description',
        'created_at',

    ];
    public function user(){

        return $this->belongsTo(User::class);

    }

    public function service(){

        return $this->belongsTo(Service::class);

    }
}
