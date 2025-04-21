<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Box;
use App\Models\Branch;
use App\Models\Finance;
use App\Models\Notification;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\WorkerPurchase;
use App\Services\TraceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdvanceController extends Controller
{

    private TraceService $traceService;

    public function __construct(TraceService $traceService)
    {
        $this->traceService = $traceService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function branchPendentAdvances(Request $request)
    {
        $userName = $request->user()->name;

        try {
            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id'
            ]);

            // Registrar inicio de la operación
            Log::info("Usuario {$userName} consultó adelantos del día", [
                'branch_id' => $validated['branch_id']
            ]);

            // Calcular fechas del día actual
            //$endDate = now()->endOfMonth();
            $startDate = now()->toDateString();

            // Construir consulta base
            $advances = Advance::where('branch_id', $validated['branch_id'])
                ->with(['branch', 'professional'])
                ->whereDate('paid', 0)
                //->whereDate('data', '<=', $endDate)
                //->where('status', 'Pendiente')
                ->orderByRaw("FIELD(status, 'Pendiente', 'Aprobado', 'Pagado')") // Orden específico
                ->orderBy('created_at', 'asc') // Luego por fecha más antigua
                ->where('type', 'Adelanto')
                ->get()->map(function ($advance) {
                    return [
                        'id' => $advance->id,
                        'data' => $advance->data,
                        'type' => $advance->type,
                        'amount' => $advance->amount,
                        'status' => $advance->status,
                        'created_at' => $advance->created_at,
                        'branch' => $advance->branch,
                        'paid' => $advance->paid,
                        'receipt' => $advance->receipt,
                        'professionalName' => $advance->professional->name ?? null, // Nombre del profesional
                        'image' => $advance->professional->image_url ?? null,      // Imagen del profesional
                    ];
                });

            return response()->json([
                'success' => true,
                'advances' => $advances
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Sucursal no encontrada", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sucursal no encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error al obtener adelantos diarios", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los adelantos del día: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAdvances(Request $request)
    {
        try {
            $userName = $request->user()->name;

            // Validar parámetros básicos
            $validated = $request->validate([
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date|after_or_equal:startDate',
                'branch_id' => 'required|exists:branches,id',
                'professional_id' => 'nullable|exists:professionals,id'
            ]);

            // Registrar inicio de la operación
            Log::info("Usuario {$userName} consultó adelantos", [
                'request_params' => $validated
            ]);

            // Determinar fechas según lo recibido
            if (empty($validated['startDate']) || empty($validated['endDate'])) {
                // Caso por defecto: últimos 3 meses
                $endDate = now()->endOfMonth();
                $startDate = now()->subMonths(3)->startOfMonth();
            } else {
                // Caso con fechas específicas
                $startDate = $validated['startDate'];
                $endDate = $validated['endDate'];
            }

            // Construir consulta base
            $query = Advance::where('branch_id', $validated['branch_id'])
                ->whereDate('data', '>=', $startDate)
                ->whereDate('data', '<=', $endDate)
                ->orderBy('created_at', 'desc')
                ->where('type', 'Adelanto');

            // Filtrar por profesional si se especificó
            if (!empty($validated['professional_id'])) {
                $query->where('professional_id', $validated['professional_id']);
            }

            // Obtener todos los resultados
            $advances = $query->get()->map(function ($advance) {
                return [
                    'id' => $advance->id,
                    'data' => $advance->data,
                    'type' => $advance->type,
                    'amount' => $advance->amount,
                    'status' => $advance->status,
                    'created_at' => $advance->created_at,
                    'branch' => $advance->branch,
                    'paid' => $advance->paid,
                    'receipt' => $advance->receipt ?? null,
                    'professionalName' => $advance->professional->name ?? null, // Nombre del profesional
                    'image' => $advance->professional->image_url ?? null,      // Imagen del profesional
                ];
            });;

            return response()->json([
                'success' => true,
                'advances' => $advances
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Error de validación", [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Recurso no encontrado", [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sucursal o profesional no encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error al obtener adelantos", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los adelantos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userName = Auth::user()->name;
        DB::beginTransaction();

        try {
            // Registrar datos recibidos
            Log::info('Intento de creación de advance', [
                'request_data' => $request->all(),
                'user' => $userName
            ]);

            $validated = $request->validate([
                //'data' => 'nullable|date',
                'professional_id' => 'required|exists:professionals,id',
                'branch_id' => 'required|exists:branches,id',
                //'type' => 'nullable|string',
                'amount' => 'nullable|numeric',
                //'status' => 'nullable|string'
            ]);

            $branch = Branch::where('id', $validated['branch_id'])->first();
            $professional = Professional::where('id', $validated['professional_id'])->first();

            $advance = new Advance();
            $advance->data = $validated['data'] ?? now()->toDateString(); // Asigna fecha actual si no viene
            $advance->professional_id = $validated['professional_id'];
            $advance->branch_id = $validated['branch_id'];
            $advance->type = $validated['type'] ?? 'Adelanto';
            $advance->amount = $validated['amount'] ?? null;
            $advance->status = $validated['status'] ?? 'Pendiente';
            $advance->save();

            $notification = new Notification();
            $notification->professional_id = $validated['professional_id'];
            $notification->tittle = 'Solicitud de Adelanto';
            $notification->description = 'Profesional ' . $professional->name . ' solicita un adelanto de: $' . $validated['amount'] . ', en la sucursal: ' . $branch->name;
            $notification->type = 'Administrador';
            $branch->notifications()->save($notification);

            $notification = new Notification();
            $notification->professional_id = $validated['professional_id'];
            $notification->tittle = 'Solicitud de Adelanto';
            $notification->description = 'Profesional ' . $professional->name . ' solicita un adelanto de: $' . $validated['amount'];
            $notification->type = 'Cajera';
            $branch->notifications()->save($notification);

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $advance,
                'message' => 'Advance creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de error detallado
            Log::error('Error al crear advance', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el advance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Advance $advance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $userName = $user->name;
        $userId = $user->id;
        DB::beginTransaction();

        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'status' => 'required|string',  // Solo validamos el type como requerido
                'id' => 'required|exists:advances,id',
                'data' => 'nullable|date',
            ]);

            // Obtener el advance existente
            $advance = Advance::with(['branch', 'professional'])->findOrFail($validated['id']);

            // Registrar inicio de operación
            Log::info('Usuario intenta cambiar tipo de advance', [
                'user' => $userName,
                'advance_id' => $validated['id'],
                'current_status' => $advance->status,
                'new_status' => $validated['status'],
                'amount' => $advance->amount,
                'branch_id' => $advance->branch_id,
                'professional_id' => $advance->professional_id
            ]);

            // Verificar si ya está pagado
            if ($advance->status === 'Pagado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este adelanto ya fue pagado anteriormente',
                    'data' => $advance
                ], 400);
            }

            // Validar pago si el nuevo tipo es "Pagado"
            if ($validated['status'] === 'Pagado') {
                // Verificar caja del día actual específicamente
                $box = Box::where('branch_id', $advance->branch_id)
                    ->whereDate('data', now()->toDateString()) // Filtro por día actual
                    ->first();

                if (!$box) {
                    $totalAvailable = 0;
                }else {
                $totalAvailable = $box->existence - $box->cashFound;
                }


                if ($totalAvailable < $advance->amount) {
                    $notification = new Notification();
                    $notification->professional_id = $advance->professional_id;
                    $notification->tittle = 'Adelanto Aprobado';
                    $notification->description = 'Adelanto de $' . $advance->amount . 'aprobado para pago en 24hrs correctamente';
                    $notification->type = 'Barbero';
                    $advance->branch->notifications()->save($notification);

                    $trace = [
                        'branch' => $advance->branch->name,
                        'cashier' => $userName,
                        'client' => '',
                        'amount' => $advance->amount,
                        'operation' => 'Aprobada solicitud de adelanto',
                        'description' => '',
                        'details' => 'Solicitud de adelanto de ' . $advance->professional->name . ' aprobada para pago en 24hrs correctamente',
                    ];

                    // Guardar traza usando el servicio de trazas
                    $this->traceService->store($trace);
                    $advance->status = 'Aprobado';
                } else {
                    $box->existence -= $advance->amount;
                    $box->save();


                    $advance->paid = 1;
                    $advance->status = $validated['status'];
                    $advance->user_id = $userId;
                    // Registrar que se verificó la caja
                    Log::info('Validación de caja exitosa', [
                        'box_id' => $box->id,
                        'existence' => $box->existence,
                        'amount_required' => $advance->amount
                    ]);

                    $professionalPayment = new ProfessionalPayment();
                    $professionalPayment->branch_id = $advance->branch_id;
                    $professionalPayment->professional_id = $advance->professional_id;
                    $professionalPayment->date = !empty($validated['data'])
                        ? Carbon::parse($validated['data'])->setTime(now()->hour, now()->minute, now()->second)
                        : now();
                    $professionalPayment->amount = $advance->amount;
                    $professionalPayment->type = 'Solicitud Adelanto';
                    $professionalPayment->save();

                    // Registrar en finances
                    $lastFinance = Finance::orderBy('control', 'desc')->first();
                    $control = $lastFinance ? $lastFinance->control + 1 : 1;

                    $finance = new Finance();
                    $finance->control = $control;
                    $finance->operation = 'Gasto';
                    $finance->amount = $advance->amount;
                    $finance->comment = 'Gasto por pago de adelanto a ' . $advance->professional->name;
                    $finance->branch_id = $advance->branch_id;
                    $finance->type = 'Sucursal';
                    $finance->expense_id = 4; // ID específico para gastos de adelantos
                    $finance->data = !empty($validated['data'])
                        ? $validated['data'] : now()->toDateString();
                    $finance->file = '';
                    $finance->save();

                    Log::info('Registro de finanzas creado', [
                        'finance_id' => $finance->id,
                        'control_number' => $control,
                        'amount' => $advance->amount
                    ]);

                    $trace = [
                        'branch' => $advance->branch->name,
                        'cashier' => $userName,
                        'client' => '',
                        'amount' => $advance->amount,
                        'operation' => 'Pago solicitud de adelanto',
                        'description' => '',
                        'details' => 'Solicitud de adelanto de ' . $advance->professional->name . ' pagada correctamente',
                    ];

                    // Guardar traza usando el servicio de trazas
                    $this->traceService->store($trace);
                }
                $advance->save();
            }
            /*if ($validated['status'] == 'Aprobado') {
                $notification = new Notification();
                $notification->professional_id = $advance->professional_id;
                $notification->tittle = 'Adelanto Aprobado';
                $notification->description = 'Adelanto de $' . $advance->amount. 'aprobado para pago en 24hrs correctamente';
                $notification->type = 'Barbero';
                $advance->branch->notifications()->save($notification);

                $trace = [
                    'branch' => $advance->branch->name,
                    'cashier' => $userName,
                    'client' => '',
                    'amount' => $advance->amount,
                    'operation' => 'Aprobada solicitud de adelanto',
                    'description' => '',
                    'details' => 'Solicitud de adelanto de '. $advance->professional->name. ' aprobada para pago en 24hrs correctamente',
                ];
                
                // Guardar traza usando el servicio de trazas
                $this->traceService->store($trace);                
            }*/

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $advance,
                'message' => 'Tipo de advance actualizado exitosamente'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Advance no encontrado', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Advance no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar advance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el advance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update_admin(Request $request)
    {
        $user = Auth::user();
        $userName = $user->name;
        $userId = $user->id;
        DB::beginTransaction();

        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'status' => 'required|string',  // Solo validamos el type como requerido
                'id' => 'required|exists:advances,id',
                'data' => 'nullable|date',
            ]);

            // Obtener el advance existente
            $advance = Advance::with(['branch', 'professional'])->findOrFail($validated['id']);

            // Registrar inicio de operación
            Log::info('Usuario intenta cambiar tipo de advance', [
                'user' => $userName,
                'advance_id' => $validated['id'],
                'current_status' => $advance->status,
                'new_status' => $validated['status'],
                'amount' => $advance->amount,
                'branch_id' => $advance->branch_id,
                'professional_id' => $advance->professional_id
            ]);

            // Verificar si ya está pagado
            if ($advance->status === 'Pagado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este adelanto ya fue pagado anteriormente',
                    'data' => $advance
                ], 400);
            }

            if ($request->hasFile('receipt')) {
                $advance->receipt = $request->file('receipt')->storeAs('advances', $validated['id'] . '.' . $request->file('receipt')->extension(), 'public');
            }
            // Actualizar solo el tipo
            $advance->status = $validated['status'];
            $advance->userId = $userId;
            $advance->save();

            // Validar pago si el nuevo tipo es "Pagado"
            if ($validated['status'] === 'Pagado') {
                $professionalPayment = new ProfessionalPayment();
                $professionalPayment->branch_id = $advance->branch_id;
                $professionalPayment->professional_id = $advance->professional_id;
                $professionalPayment->date = now();
                $professionalPayment->amount = $advance->amount;
                $professionalPayment->type = 'Solicitud Adelanto';
                $professionalPayment->save();

                // Registrar en finances
                $lastFinance = Finance::orderBy('control', 'desc')->first();
                $control = $lastFinance ? $lastFinance->control + 1 : 1;

                $finance = new Finance();
                $finance->control = $control;
                $finance->operation = 'Gasto';
                $finance->amount = $advance->amount;
                $finance->comment = 'Gasto por pago de adelanto a ' . $advance->professional->name;
                $finance->branch_id = $advance->branch_id;
                $finance->type = 'Sucursal';
                $finance->expense_id = 4; // ID específico para gastos de adelantos
                $finance->data = now()->toDateString();
                $finance->file = '';
                $finance->save();

                Log::info('Registro de finanzas creado', [
                    'finance_id' => $finance->id,
                    'control_number' => $control,
                    'amount' => $advance->amount
                ]);

                $advance->paid = 1;
                $advance->save();
            }
            if ($validated['status'] == 'Aprobado') {

                $notification = new Notification();
                $notification->professional_id = $advance->professional_id;
                $notification->tittle = 'Solicitud de Adelanto Aprobada';
                $notification->description = 'Solicitud de Adelanto de $' . $advance->amount . 'aprobada para pago en 24hrs correctamente';
                $notification->type = 'Barbero';
                $advance->branch->notifications()->save($notification);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $advance,
                'message' => 'Tipo de advance actualizado exitosamente'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Advance no encontrado', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Advance no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar advance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el advance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Advance $advance)
    {
        //
    }

    public function getCombinedData(Request $request)
    {
        try {
            $userName = $request->user()->name;
    
            // Validar parámetros
            $validated = $request->validate([
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date|after_or_equal:startDate',
                'branch_id' => 'required|integer|exists:branches,id',
                'professional_id' => 'required|integer|exists:professionals,id',
            ]);
    
            Log::info("Usuario {$userName} consultó datos financieros combinados", [
                'request_params' => $validated,
                'user_id' => $request->user()->id
            ]);
    
            // Fechas para advances (últimos 3 meses si no se especifica)
            $advancesStartDate = $validated['startDate'] ?? now()->subMonths(3)->startOfDay();
            $advancesEndDate = $validated['endDate'] ?? now()->endOfDay();
    
            // Fechas para products (último mes si no se especifica)
            $productsStartDate = $validated['startDate'] ?? now()->subMonth()->startOfDay();
            $productsEndDate = $validated['endDate'] ?? now()->endOfDay();
    
            // Obtener adelantos (advances)
            $advances = Advance::where('branch_id', $validated['branch_id'])
                ->where('professional_id', $validated['professional_id'])
                ->whereDate('data', '>=', $advancesStartDate)
                ->whereDate('data', '<=', $advancesEndDate)
                ->where('type', 'Adelanto')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($advance) {
                    return [
                        'id' => $advance->id,
                        'data' => $advance->data,
                        'amount' => $advance->amount,
                        'status' => $advance->status,
                        'paid' => $advance->paid,
                        'receipt' => $advance->receipt ?? null,
                        'discount_date' => $advance->discount_date,
                        'type' => 'advance',
                        'created_at' => $advance->created_at
                    ];
                });
    
            // Obtener compras de trabajadores (products)
            $products = WorkerPurchase::with(['product:id,name,image_product'])
                ->where('branch_id', $validated['branch_id'])
                ->where('professional_id', $validated['professional_id'])
                ->whereDate('data', '>=', $productsStartDate)
                ->whereDate('data', '<=', $productsEndDate)
                ->get()
                ->map(function ($purchase) {
                    $statusText = match($purchase->status) {
                        0 => 'Pendiente',
                        1 => 'Aprobado',
                        2 => 'Denegado',
                        default => 'Desconocido'
                    };
                    return [
                        'id' => $purchase->id,
                        'data' => $purchase->data,
                        'productName' => $purchase->product->name,
                        'productImage' => $purchase->product->image_product,
                        'cant' => $purchase->cant,
                        'total' => $purchase->total,
                        'status' => $statusText,
                        'discount_date' => $purchase->discount_date,
                        'type' => 'product',
                        'created_at' => $purchase->created_at
                    ];
                });
    
            // Calcular totales
            $totalAdvance = $advances->where('paid', 1)
                ->whereNull('discount_date')
                ->sum('amount');
    
            $totalProduct = $products->where('status', 'Aprobado')
                ->whereNull('discount_date')
                ->sum('total');
    
            // Combinar y ordenar datos
            $combinedData = $advances->merge($products)
                ->sortByDesc('created_at')
                ->values();
    
            return response()->json([
                'success' => true,
                'data' => $combinedData,
                'totals' => [
                    'totalAdvance' => $totalAdvance,
                    'totalProduct' => $totalProduct,
                    'grandTotal' => $totalAdvance + $totalProduct
                ],
            ]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ... (mantener el mismo manejo de errores)
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // ... (mantener el mismo manejo de errores)
        } catch (\Exception $e) {
            // ... (mantener el mismo manejo de errores)
        }
    }
}
