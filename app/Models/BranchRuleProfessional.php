<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRuleProfessional extends Model
{
    use HasFactory;

    public function branchRule()
    {
    return $this->belongsTo(BranchRule::class);
    }

    public function professional()
    {
    return $this->belongsTo(Professional::class);
    }

    //para decirle a q table debe administrar
    protected $table = "branch_rule_professional";
}
