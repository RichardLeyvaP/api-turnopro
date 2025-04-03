<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use Illuminate\Http\Request;

use App\Models\MonthlyClosure;
use App\Models\Retention;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthlyClosureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getMonthlyClosures(Request $request)
    {
        try {
            // Validar los parámetros de entrada
            $validated = $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
                'business_id' => 'nullable|integer|exists:businesses,id',
                'year' => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
                'month' => 'nullable|integer|between:1,12',
            ]);
    
            // Obtener parámetros
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $businessId = $request->input('business_id');
        $branchId = $request->input('branch_id');

        // Construir consulta base
        $query = MonthlyClosure::with([
            'business:id,name',
            'branch:id,name',
            'user.professional:id,user_id,name'
        ]);

        // Filtrar por año (extraer año del campo month Y-m)
        $query->whereRaw("SUBSTRING(month, 1, 4) = ?", [$year]);

        // Filtrar por mes si está presente
        if ($month) {
            $query->whereRaw("SUBSTRING(month, 6, 2) = ?", [str_pad($month, 2, '0', STR_PAD_LEFT)]);
        }

        // Filtrar por business_id
        if ($businessId) {
            $query->where(function($q) use ($businessId) {
                $q->where('business_id', $businessId)
                  ->orWhereHas('branch', function($branchQuery) use ($businessId) {
                      $branchQuery->where('business_id', $businessId);
                  });
            });
        }

        // Filtrar por branch_id si está presente
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Ordenar por mes descendente
        $query->orderBy('month', 'desc');

        // Transformar los resultados
        $closures = $query->get()->map(function($closure) {
            return [
                'id' => $closure->id,
                'data' => $closure->data,
                'available_money' => $closure->available_money ?? 0,
                'utility' => $closure->utility ?? 0,
                'net_utility' => $closure->net_utility ?? 0,
                'retention' => $closure->retention ?? 0,
                'discounts' => $closure->discounts ?? 0,
                'differences' => $closure->differences ?? 0,
                'incomes' => $closure->incomes ?? [],
                'expenses' => $closure->expenses ?? [],
                'businessName' => $closure->business->name ?? null,
                'branchName' => $closure->branch->name ?? null,
                'branch_id' => $closure->branch_id ?? null,
                'business_id' => $closure->business_id ?? null,
                'userName' => $closure->user->name  ?? null,
                'professionalName' => $closure->user->professional->name ?? null,
                'type' => $closure->branch_id ? 'Sucursal' : 'Negocio', // Nuevo campo para identificar el tipo
                'month' => $closure->month,
            ];
        });
            return response()->json([
                'success' => true,
                'closures' => $closures
            ]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los cierres de mes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'editedItem' => 'required|array',
                'editedItem.id' => 'nullable|numeric',
                'editedItem.available_money' => 'nullable|numeric|min:0',
                'editedItem.utility' => 'nullable|numeric',
                'editedItem.net_utility' => 'nullable|numeric',
                'editedItem.retention' => 'nullable|numeric|min:0',
                'editedItem.discounts' => 'nullable|numeric|min:0',
                'editedItem.differences' => 'nullable|numeric',
                'editedItem.incomes' => 'nullable|array',
                'editedItem.expenses' => 'nullable|array',
                'editedItem.branch_id' => 'nullable|integer|exists:branches,id',
                'editedItem.business_id' => 'nullable|integer|exists:businesses,id',
                'month' => 'nullable|date_format:Y-m',
            ]);
            
            $editedItem = $validatedData['editedItem'];
            $month = $validatedData['month'] ?? now()->format('Y-m');
            
            // Limpieza de valores
            $branchId = $editedItem['branch_id'] === "" ? null : $editedItem['branch_id'];
            $businessId = $editedItem['business_id'] === "" ? null : $editedItem['business_id'];
            $recordId = $editedItem['id'] === "" ? null : $editedItem['id'];
             // Datos para actualizar/crear
            $updateData = [
                'data' => now()->toDateString(),
                'branch_id' => $editedItem['branch_id'] ?? null,
                'business_id' => $editedItem['business_id'] ?? null,
                'available_money' => $editedItem['available_money'] ?? 0,
                'utility' => $editedItem['utility'] ?? 0,
                'net_utility' => $editedItem['net_utility'] ?? 0,
                'retention' => $editedItem['retention'] ?? 0,
                'discounts' => $editedItem['discounts'] ?? 0,
                'differences' => $editedItem['differences'] ?? 0,
                'month' => $month,
                'incomes' => json_encode($editedItem['incomes']),
                'expenses' => json_encode($editedItem['expenses']),
                'user_id' => auth()->id(),
            ];
    
            // Lógica mejorada de creación/actualización
            if ($recordId) {
                // Opción 1: Buscar y actualizar manualmente
                $closure = MonthlyClosure::find($recordId);
                
                if ($closure) {
                    $closure->update($updateData);
                } else {
                    // Si no existe con ese ID, crear nuevo con el ID especificado
                    $updateData['id'] = $recordId;
                    $closure = MonthlyClosure::create($updateData);
                }
            } else {
                // Opción 2: Búsqueda por branch, business y month
                $closure = MonthlyClosure::updateOrCreate(
                    [
                        'branch_id' => $branchId,
                        'business_id' => $businessId,
                        'month' => $month
                    ],
                    $updateData
                );
            }
        
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Cierre de mes realizado exitosamente',
                'data' => $closure
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cierre mensual: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cierre de mes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store_incomes(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'editedItem' => 'required|array',
                'editedItem.available_money' => 'nullable|numeric|min:0',
                'editedItem.incomes' => 'nullable|array',
                'editedItem.id' => 'nullable',
                'editedItem.branch_id' => 'nullable|integer|exists:branches,id',
                'editedItem.business_id' => 'nullable|integer|exists:businesses,id',
                'month' => 'nullable|date_format:Y-m',
            ]);
            
            $editedItem = $validatedData['editedItem'];
            $month = $validatedData['month'] ?? now()->format('Y-m');
            
            // Limpieza de valores
            $branchId = $editedItem['branch_id'] === "" ? null : $editedItem['branch_id'];
            $businessId = $editedItem['business_id'] === "" ? null : $editedItem['business_id'];
            $recordId = $editedItem['id'] === "" ? null : $editedItem['id'];
            
            // Preparar datos para actualización/creación
            $updateData = [
                'branch_id' => $branchId,
                'business_id' => $businessId,
                'available_money' => $editedItem['available_money'] ?? 0,
                'month' => $month,
                'incomes' => json_encode($editedItem['incomes'] ?? []),
                'user_id' => auth()->id(),
            ];
            
            // Lógica mejorada de creación/actualización
            if ($recordId) {
                // Opción 1: Buscar y actualizar manualmente
                $closure = MonthlyClosure::find($recordId);
                
                if ($closure) {
                    $closure->update($updateData);
                } else {
                    // Si no existe con ese ID, crear nuevo con el ID especificado
                    $updateData['id'] = $recordId;
                    $closure = MonthlyClosure::create($updateData);
                }
            } else {
                // Opción 2: Búsqueda por branch, business y month
                $closure = MonthlyClosure::updateOrCreate(
                    [
                        'branch_id' => $branchId,
                        'business_id' => $businessId,
                        'month' => $month
                    ],
                    $updateData
                );
            }
            
        
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Ingresos Agregados correctamente',
                'data' => $closure
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cierre mensual: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cierre de mes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store_expenses(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'editedItem' => 'required|array',
                'editedItem.discounts' => 'nullable|numeric|min:0',
                'editedItem.expenses' => 'nullable|array',
                'editedItem.id' => 'nullable',
                'editedItem.branch_id' => 'nullable|integer|exists:branches,id',
                'editedItem.business_id' => 'nullable|integer|exists:businesses,id',
                'month' => 'nullable|date_format:Y-m',
            ]);
            
            $editedItem = $validatedData['editedItem'];
            $month = $validatedData['month'] ?? now()->format('Y-m');
            
            // Limpieza de valores
            $branchId = $editedItem['branch_id'] === "" ? null : $editedItem['branch_id'];
            $businessId = $editedItem['business_id'] === "" ? null : $editedItem['business_id'];
            $recordId = $editedItem['id'] === "" ? null : $editedItem['id'];
            
            // Preparar datos para actualización/creación
            $updateData = [
                'branch_id' => $branchId,
                'business_id' => $businessId,
                'discounts' => $editedItem['discounts'] ?? 0,
                'month' => $month,
                'expenses' => json_encode($editedItem['expenses'] ?? []),
                'user_id' => auth()->id(),
            ];
            
            // Lógica mejorada de creación/actualización
            if ($recordId) {
                // Opción 1: Buscar y actualizar manualmente
                $closure = MonthlyClosure::find($recordId);
                
                if ($closure) {
                    $closure->update($updateData);
                } else {
                    // Si no existe con ese ID, crear nuevo con el ID especificado
                    $updateData['id'] = $recordId;
                    $closure = MonthlyClosure::create($updateData);
                }
            } else {
                // Opción 2: Búsqueda por branch, business y month
                $closure = MonthlyClosure::updateOrCreate(
                    [
                        'branch_id' => $branchId,
                        'business_id' => $businessId,
                        'month' => $month
                    ],
                    $updateData
                );
            }
            
        
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Gastos Agregados correctamente',
                'data' => $closure
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cierre mensual: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cierre de mes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'editedItem' => 'required|array',
            ]);
            
            $editedItem = $validatedData['editedItem'];
            
            $recordId = $editedItem['id'] === "" ? null : $editedItem['id'];
            
            // Preparar datos para actualización/creación
            $updateData = [
                'data' => now()->toDateString(),
                'available_money' => $editedItem['available_money'] ?? 0,
                'utility' => $editedItem['utility'] ?? 0,
                'net_utility' => $editedItem['net_utility'] ?? 0,
                'retention' => $editedItem['retention'] ?? 0,
                'discounts' => $editedItem['discounts'] ?? 0,
                'differences' => $editedItem['differences'] ?? 0,
                'incomes' => json_encode($editedItem['incomes']),
                'expenses' => json_encode($editedItem['expenses']),
                'user_id' => auth()->id(),
            ];
            
            // Lógica mejorada de creación/actualización
            if ($recordId) {
                // Opción 1: Buscar y actualizar manualmente
                $closure = MonthlyClosure::find($recordId);
                
                if ($closure) {
                    $closure->update($updateData);
                } 
            }
            
        
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Actualización realizada correctamente',
                'data' => $closure
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cierre mensual: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cierre de mes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function calculateUtility(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
                'business_id' => 'nullable|integer|exists:businesses,id',
                'month' => 'nullable|date_format:Y-m',
            ]);
    
            
            // Obtener valores con null por defecto
            $branchId = $validated['branch_id'] ?? null;
            $businessId = $validated['business_id'] ?? null;
            $month = $validated['month'] ?? null;
            
            // Validar que no se envíen ambos parámetros
            if ($branchId && $businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede filtrar por branch_id O business_id, no ambos'
                ], 400);
            }
            
            // Llamar al método del modelo según el parámetro recibido
            $result = Finance::calculatePreviousMonthUtility($branchId, $businessId, $month);
            $retentions = Retention::calculatePreviousMonthRetentionsWithIds($branchId, $businessId, $month);
            
            return response()->json([
                'success' => true,
                'utility' => $result['utility'],
                'finance_ids' => $result['ids'],
                'retentions_total' => $retentions['total'],
                'retention_ids' => $retentions['ids'],
                'message' => 'Cálculo de utilidad realizado correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al calcular utilidad: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al calcular la utilidad',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MonthlyClosure $monthlyClosure)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
    }
}
