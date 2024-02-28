<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoxClose extends Model
{
    use HasFactory;

    public function box(){
        return $this->belongsTo(Box::class);
    }

    protected $casts = [
        'totalMount' => 'double',
        'totalService' => 'double',
        'totalProduct' => 'double',
        'totalTip' => 'double',
        'totalCash' => 'double',
        'totalDebit' => 'double',
        'totalCreditCard' => 'double',
        'totalTransfer' => 'double',
        'totalOther' => 'double', 
        'totalcardGif' => 'double' 
    ];
}
