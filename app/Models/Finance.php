<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Finance extends Model
{
    use HasFactory;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function revenue()
    {
        return $this->belongsTo(Revenue::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
    protected $casts = [
        'amount' => 'double',
        'control' => 'integer'
    ];

    public static function calculatePreviousMonthUtility(?int $branch_id = null, ?int $business_id = null, ?string $month = null,): array
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
        
        Log::info('Calculando utilidad para rango de fechas:', [
            'start' => $startDate,
            'end' => $endDate,
            'branch_id' => $branch_id,
            'business_id' => $business_id
        ]);

        // Crear consulta base
        $query = self::query()
                ->whereDate('data', '>=', $startDate)
                ->whereDate('data', '<=', $endDate);
        
        // Aplicar filtros según los parámetros
        if ($branch_id) {
            Log::info("Filtrando por branch_id: $branch_id");
            $query->where('branch_id', $branch_id);
        } elseif ($business_id) {
            Log::info("Filtrando por business_id: $business_id");
            $query->where(function($q) use ($business_id) {
                $q->where('business_id', $business_id)
                  ->orWhereHas('branch', function($q) use ($business_id) {
                      $q->where('business_id', $business_id);
                  });
            });
        } else {
            Log::info('Sin filtros - cálculo global');
        }

        // Clonar la consulta para obtener los IDs
        $idsQuery = clone $query;
        $ids = $idsQuery->pluck('id')->toArray();
        
        // Calcular la utilidad: suma de ingresos - suma de gastos
        $result = $query->select(
            DB::raw('SUM(CASE WHEN operation = "Ingreso" THEN amount ELSE 0 END) as income'),
            DB::raw('SUM(CASE WHEN operation = "Gasto" THEN amount ELSE 0 END) as expense')
        )->first();
        
        $income = $result->income ?? 0;
        $expense = $result->expense ?? 0;
        $utility = $income - $expense;
        
        Log::info('Resultados del cálculo:', [
            'income' => $income,
            'expense' => $expense,
            'utility' => $utility
        ]);

        return [
            'utility' => $utility,
            'income' => $income,
            'expense' => $expense,
            'ids' => $ids
        ];
        
    } catch (\Exception $e) {
        Log::error('Error en calculatePreviousMonthUtility: ' . $e->getMessage(), [
            'exception' => $e,
            'params' => ['branch_id' => $branch_id, 'business_id' => $business_id]
        ]);
        throw $e; // Re-lanzar la excepción para que el controlador la capture
    }
}
}
