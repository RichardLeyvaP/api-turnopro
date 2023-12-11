<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRule extends Model
{
    use HasFactory;

    public function professionals(){
        return $this->belongsToMany(Professional::class, 'branch_rule_professional')->withPivot('data','estado')->withTimestamps();
    }

    public function branchRuleProfessionals(){
        return $this->hasMany(BranchRuleProfessional::class);
    }

    public function rule()
    {
    return $this->belongsTo(Rule::class);
    }

    public function branch()
    {
    return $this->belongsTo(Branch::class);
    }

    protected $casts = [
        'select_professional' => 'integer'
    ];
    
    //para decirle a q table debe administrar
    protected $table = "branch_rule";
}
