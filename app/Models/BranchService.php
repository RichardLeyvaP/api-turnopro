<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchService extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function branchServiceProfessional()
    {
         return $this->belongsToMany(Professional::class, 'branch_service_professional');
    }

    public function professionals()
    {
         return $this->belongsToMany(Professional::class, 'branch_service_professional')->withPivot('percent', 'type_service','id', 'meta')->withTimestamps();
    }

    public function professionals1()
    {
        return $this->hasMany(BranchServiceProfessional::class, 'branch_service_id');
    }

    public function branchServiceProfessionals()
    {
         return $this->hasMany(BranchServiceProfessional::class)->withTrashed();
    }

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /*protected static function booted()
    {
        static::deleting(function ($branchService) {
            // Eliminación lógica en cascada
            if ($branchService->isForceDeleting()) {
                // Si es eliminación permanente
                BranchServiceProfessional::where('branch_service_id', $branchService->id)
                    ->forceDelete();
            } else {
                // Eliminación lógica
                BranchServiceProfessional::where('branch_service_id', $branchService->id)
                    ->delete();
            }
        });

        /*static::restoring(function ($branchService) {
            // Restaurar en cascada
            BranchServiceProfessional::withTrashed()
                ->where('branch_service_id', $branchService->id)
                ->restore();
        });*/
    //}*/
    protected static function booted()
    {
        static::deleting(function ($branchService) {
            if ($branchService->isForceDeleting()) {
                // Eliminación permanente
                $branchService->professionals1->each->forceDelete();
            } else {
                // Eliminación lógica + meta = 0
                foreach ($branchService->professionals1 as $bsp) {
                    // $bsp es una instancia de BranchServiceProfessional
                    $bsp->meta = 0;
                    $bsp->save(); // Esto ahora sí es válido
                }
                // Ahora eliminamos lógicamente los registros
                $branchService->professionals1->each->delete();
            }
        });

        /*static::restoring(function ($branchService) {
            $branchService->professionals->each->restore();
        });*/
    }
        //para decirle a q table debe administrar
    protected $table = "branch_service";
}
