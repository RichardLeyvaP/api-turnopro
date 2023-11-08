<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchServicePerson extends Model
{
    use HasFactory;

    //para decirle a q table debe administrar
    protected $table = "branch_service_person";
}
