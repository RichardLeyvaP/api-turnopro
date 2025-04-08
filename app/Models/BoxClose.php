<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BoxClose extends Model
{
    use HasFactory;

    public function box(){
        return $this->belongsTo(Box::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function cashierBoxClosing()
    {
        return $this->hasOne(CashierBoxClosing::class);
    }

    protected $casts = [
        'totalMount' => 'double',
        'totalService' => 'double',
        'totalProduct' => 'double',
        'totalTip' => 'double',
        'totalCash' => 'double',
        'totalDebit' => 'double',
        'totalCreditCard' => 'double',
        'totalTransfer' => 'double',
        'totalOther' => 'double', 
        'totalcardGif' => 'double' 
    ];

    // En app/Models/BoxClose.php

public static function calculatePreviousMonthTotalAmount($branchId = null, $businessId = null, $month = null)
{
    if ($month) {
        $carbonMonth = Carbon::createFromFormat('Y-m', $month);
        $startDate = $carbonMonth->copy()->startOfMonth()->toDateString();
        $endDate = $carbonMonth->copy()->endOfMonth()->toDateString();
    } else {
        // Lógica original (mes anterior)
        $startDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->subMonth()->endOfMonth()->toDateString();
    }
    
    Log::info('Calculando ingresos para rango de fechas:', [
        'start' => $startDate,
        'end' => $endDate,
        'branch_id' => $branchId,
        'business_id' => $businessId
    ]);

    // Obtener IDs de los cierres diarios más recientes por día
    $latestClosureIds = self::select(DB::raw('MAX(box_closes.id) as id'))
        ->join('boxes', 'boxes.id', '=', 'box_closes.box_id')
        ->join('branches', 'branches.id', '=', 'boxes.branch_id')
        ->where('box_closes.type', 'Diario')
        ->where('box_closes.data', '>=', $startDate)
        ->where('box_closes.data', '<=', $endDate)
        ->when($branchId, function($query) use ($branchId) {
            $query->where('branches.id', $branchId);
        })
        ->when($businessId && !$branchId, function($query) use ($businessId) {
            $query->where('branches.business_id', $businessId);
        })
        ->groupBy(DB::raw('DATE(box_closes.data)'))
        ->pluck('id');

    // Consulta para obtener todos los totales
    $boxClose = self::selectRaw('
            COALESCE(SUM(totalMount), 0) as totalMount,
            COALESCE(SUM(totalService), 0) as totalService,
            COALESCE(SUM(totalProduct), 0) as totalProduct,
            COALESCE(SUM(totalTip), 0) as totalTip,
            COALESCE(SUM(totalCash), 0) as totalCash,
            COALESCE(SUM(totalDebit), 0) as totalDebit,
            COALESCE(SUM(totalCreditCard), 0) as totalCreditCard,
            COALESCE(SUM(totalTransfer), 0) as totalTransfer,
            COALESCE(SUM(totalOther), 0) as totalOther,
            COALESCE(SUM(totalCardGif), 0) as totalCardGif
        ')
        ->whereIn('id', $latestClosureIds)
        ->first();

    return [
        'totalMount' => $boxClose->totalMount,
        'totalService' => $boxClose->totalService,
        'totalProduct' => $boxClose->totalProduct,
        'totalTip' => $boxClose->totalTip,
        'totalCash' => $boxClose->totalCash,
        'totalDebit' => $boxClose->totalDebit,
        'totalCreditCard' => $boxClose->totalCreditCard,
        'totalTransfer' => $boxClose->totalTransfer,
        'totalOther' => $boxClose->totalOther,
        'totalCardGif' => $boxClose->totalCardGif,
        'ids' => $latestClosureIds
    ];
}
}
