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

    protected static function booted()
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
    }
        //para decirle a q table debe administrar
    protected $table = "branch_service";
}
