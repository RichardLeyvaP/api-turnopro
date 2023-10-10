<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchService extends Model
{
    use HasFactory;

    public function people()
        {
            return $this->belongsToMany(Person::class, 'branch_service_person');
        }

        //para decirle a q table debe administrar
    protected $table = "branch_service";
}
