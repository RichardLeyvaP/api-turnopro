<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
class Service extends Model
{
    use HasFactory;
    use HasRelationships;

    public function branchServices(){
        return $this->hasMany(BranchService::class);
    }

    public function branches(){
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function orders(){
        return $this->hasManyDeep(Order::class, [BranchService::class, BranchServiceProfessional::class]);
    }

    public function professionals()
    {
        return $this->hasManyThrough(BranchServiceProfessional::class, BranchService::class);
    }

    protected $casts = [
        'simultaneou' => 'integer',
        'price_service' => 'double',
        'profit_percentaje' => 'double',
        'duration_service' => 'integer'
    ];
}
