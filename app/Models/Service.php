<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
class Service extends Model
{
    use HasFactory;
    use HasRelationships;
    use SoftDeletes;

    protected static function booted()
    {
        static::deleting(function ($service) {
            // Eliminación lógica en cascada para branch_service
            if ($service->isForceDeleting()) {
                // Eliminación permanente
                BranchService::where('service_id', $service->id)
                    ->forceDelete();
            } else {
                // Eliminación lógica
                BranchService::where('service_id', $service->id)
                    ->delete();
            }
        });

        /*static::restoring(function ($service) {
            // Restauración en cascada
            BranchService::withTrashed()
                ->where('service_id', $service->id)
                ->restore();
        });*/
    }
    
    public function branchServices(){
        return $this->hasMany(BranchService::class);
    }

    public function branches(){
        return $this->belongsToMany(Branch::class)->withPivot('id', 'ponderation')->withTimestamps();
    }

    /*public function orders(){
        return $this->hasManyDeep(Order::class, [BranchService::class, BranchServiceProfessional::class]);
    }*/

    public function orders()
    {
        return $this->hasManyDeep(
            Order::class, 
            [BranchService::class, BranchServiceProfessional::class]
        )
        ->withTrashedParents(); // Incluye eliminados lógicos de ambas tablas intermedias
        // ->withTrashed() // Si también quieres incluir Orders eliminados lógicamente
    }

    /*public function professionals()
    {
        return $this->hasManyThrough(BranchServiceProfessional::class, BranchService::class);
    }*/
    public function professionals()
    {
        return $this->hasManyThrough(
            BranchServiceProfessional::class, 
            BranchService::class
        )
        ->withTrashed(['branch_services', 'branch_service_professionals']);
    }
    

    protected $casts = [
        'simultaneou' => 'integer',
        'price_service' => 'double',
        'profit_percentaje' => 'double',
        'duration_service' => 'integer'
    ];
}
