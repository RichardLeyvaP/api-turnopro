<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    public function client ()
    {
        return $this->belongsTo(Client::class);
    }

    public function branchServiceProfessional ()
    {
        return $this->belongsTo(BranchServiceProfessional::class);
    }

    public function tail() {
        
        return $this->belongsTo(Tail::class);
    }
}
