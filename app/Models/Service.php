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
            if ($service->isForceDeleting()) {
                // Eliminación permanente: cargar y forzar eliminación
                $service->branchServices->each->forceDelete();
            } else {
                // Eliminación lógica: cargar y eliminar suavemente
                $service->branchServices->each->delete();
            }
        });

        /*static::restoring(function ($service) {
            $service->branchServices->each->restore();
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
