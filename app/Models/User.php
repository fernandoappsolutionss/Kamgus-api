<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Cashier\Billable;
use function Illuminate\Events\queueable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Billable;
    const EN_REVISION_STATUS = 'En revision';
    const ACTIVO_STATUS = 'Activo';
    const IN_ACTIVO_STATUS = 'In activo';
    const BLOQUEADO_STATUS = 'Bloqueado';
    const ELIMINADO_STATUS = 'Eliminado';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'userable_id',
        'userable_type',
        'country_id',
        'stripe_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function userable()
    {
        return $this->morphTo();
    }

    public function bonuses(){

        return $this->hasMany(UserBonuses::class)->with(['user', 'refered']);

    }

    public function fcmtokens(){

        return $this->hasMany(FcmToken::class);

    }

    public function licenses(){

        return $this->belongsToMany(License::class);

    }

    protected static function booted()
    {
        static::updated(queueable(function ($customer) {
            if ($customer->hasStripeId()) {
                $customer->syncStripeCustomerDetails();
            }
        }));
    }

    public function transactions(){

        return $this->hasMany(Transaction::class);

    }

    public function country(){

        return $this->belongsTo(Country::class);
        
    }

    public function scopeUserFilter($query, $initial_date, $final_date){

        return $query->whereBetween('created_at', [$initial_date, $final_date]);

    }

    public function scopeRoleFilter($query, $role){

        if($role){
            return $this::whereHas('roles', function($query) use ($role){
                $query->where('roles.id', $role->id);
            });
        }

    }

    public function scopeStatus($query, $status){

        if($status){
            return $query->where('status', $status);
        }

    }

}
