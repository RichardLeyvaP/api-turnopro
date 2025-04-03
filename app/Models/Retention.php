<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Retention extends Model
{
    use HasFactory;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public static function calculatePreviousMonthRetentionsWithIds(?int $branch_id = null, ?int $business_id = null, ?string $month = null,): array
    {
        try {
            if ($month) {
                $carbonMonth = Carbon::createFromFormat('Y-m', $month);
                $startDate = $carbonMonth->copy()->startOfMonth()->toDateString();
                $endDate = $carbonMonth->copy()->endOfMonth()->toDateString();
            } else {
                // Lógica original (mes anterior)
                $startDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $endDate = Carbon::now()->subMonth()->endOfMonth()->toDateString();
            }

            Log::info("Calculando retenciones desde $startDate hasta $endDate", [
                'branch_id' => $branch_id,
                'business_id' => $business_id
            ]);

            // Consulta base
            $query = self::query()
                ->select(['id', 'retention']) // Solo columnas necesarias
                ->whereDate('data', '>=', $startDate)
                ->whereDate('data', '<=', $endDate);

            // Aplicar filtros
            if ($branch_id) {
                $query->where('branch_id', $branch_id);
            } elseif ($business_id) {
                $query->whereHas('branch', function($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                });
            }

            // Obtener IDs primero
            $ids = $query->pluck('id')->toArray();

            // Calcular total
            $total = $query->sum('retention');

            return [
                'total' => (float)$total,
                'ids' => $ids
            ];

        } catch (\Exception $e) {
            Log::error("Error calculando retenciones: ".$e->getMessage(), [
                'exception' => $e,
                'params' => func_get_args()
            ]);
            
            return [
                'total' => 0.0,
                'ids' => []
            ];
        }
    }

}
