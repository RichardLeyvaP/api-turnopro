<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardGiftUser extends Model
{
    use HasFactory;

    public function cardGift()
    {
    return $this->belongsTo(CardGift::class);
    }

    public function user()
    {
    return $this->belongsTo(User::class);
    }
}
