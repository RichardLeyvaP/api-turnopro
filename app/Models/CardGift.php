<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardGift extends Model
{
    use HasFactory;

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function cardGiftUser()
    {
        return $this->hasMany(CardGiftUser::class);
    }

    protected $casts = [
        'value' => 'double',
    ];
}
