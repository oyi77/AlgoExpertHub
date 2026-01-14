<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneyTransfer extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'trx',
        'details',
        'amount',
        'charge',
    ];

    public $searchable = ['trx'];


    public function sender()
    {
        return $this->belongsTo(User::class,'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class,'receiver_id');
    }
}
