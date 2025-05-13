<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'kwh_meter_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Boot function to handle cascade delete
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            // Delete all related transactions
            $user->transactions()->delete();
        });
    }

    /**
     * Get all transactions for the user
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id', 'customer_id');
    }
}