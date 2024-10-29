<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tail() 
    {
        return $this->hasOne(Tail::class);
     }

     
 
    public static function softDeleteExpiredReservations($branch_id, $professional_id)
    {
        $now = Carbon::now();
        Log::info('Entra a verificar las BH no confirmada');
        Log::info($now);
        return self::where('branch_id', $branch_id)
            ->where('confirmation', 1)
            ->where('from_home', 1)
            ->whereDate('data', $now->toDateString())
            ->whereHas('car.clientProfessional', function ($query) use ($professional_id) {
                $query->where('professional_id', $professional_id);
            })
            ->whereRaw('TIMESTAMPDIFF(MINUTE, reservations.start_time, ?) > 21', [$now])
            ->update([
                'cause' => 'No Anunció su llegada',
                'deleted_at' => $now  // Soft delete con timestamp
            ]);
    }
}

