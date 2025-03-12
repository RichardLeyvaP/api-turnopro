<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierBoxClosing extends Model
{
    use HasFactory;

    protected $table = 'cashier_box_closings'; // Nombre de la tabla

    /**
     * Obtener la sucursal asociada al cierre de caja.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Obtener el usuario asociado al cierre de caja.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
