<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    use HasFactory;

    protected $casts = [
        'proof' => 'array'
    ];

    protected $fillable = [
        'user_id',
        'withdraw_method_id',
        'trx',
        'withdraw_amount',
        'withdraw_charge',
        'total',
        'proof',
        'reject_reason',
        'status',
    ];


    public function withdrawMethod()
    {
        return $this->belongsTo(WithdrawGateway::class, 'withdraw_method_id')->withDefault();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
