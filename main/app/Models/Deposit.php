<?php

namespace App\Models;

use App\Helpers\Helper\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    protected $casts = [
        'payment_proof' => 'array'
    ];

    protected $fillable = [
        'user_id',
        'gateway_id',
        'trx',
        'amount',
        'rate',
        'charge',
        'total',
        'status',
        'type',
        'payment_proof',
    ];


    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
