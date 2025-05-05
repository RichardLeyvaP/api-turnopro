<?php

namespace App\Http\Controllers;

use App\Models\Associated;
use App\Models\BoxClose;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Finance;
use Illuminate\Http\Request;

use App\Models\MonthlyClosure;
use App\Models\Professional;
use App\Models\Retention;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\SendEmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class MonthlyClosureController extends Controller
{

    private SendEmailService $sendEmailService;

    public function __construct(SendEmailService $sendEmailService)
    {

        $this->sendEmailService = $sendEmailService;
    }
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
                $query->where(function ($q) use ($businessId) {
                    $q->where('business_id', $businessId)
                        ->orWhereHas('branch', function ($branchQuery) use ($businessId) {
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
            $closures = $query->get()->map(function ($closure) {
                return [
                    'id' => $closure->id,
                    'data' => $closure->data,
                    'available_money' => $closure->available_money ?? 0,
                    'utility' => $closure->utility ?? 0,
                    'net_utility' => $closure->net_utility ?? 0,
                    'retention' => $closure->retention ?? 0,
                    'discounts' => $closure->discounts ?? 0,
                    'differences' => $closure->differences ?? 0,
                    'system_incomes' => $closure->system_incomes ?? 0,
                    'spent' => $closure->spent ?? 0,
                    'client_utility' => $closure->client_utility ?? 0,
                    'client_retention' => $closure->client_retention ?? 0,
                    'difference_incomes' => $closure->difference_incomes ?? 0,
                    'difference_utility' => $closure->difference_utility ?? 0,
                    'difference_spent' => $closure->difference_spent ?? 0,
                    'difference_retention' => $closure->difference_retention ?? 0,
                    'description' => $closure->description ?? '',
                    'incomes' => $this->parseJsonField($closure->incomes) ?? [],
                    'expenses' => $this->parseJsonField($closure->expenses) ?? [],
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

    protected function parseJsonField($value)
{
    if (is_array($value)) {
        return $value;
    }
    
    $parsed = json_decode($value, true);
    
    return is_array($parsed) ? $parsed : [];
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
                'editedItem.system_incomes' => 'nullable|numeric',
                'editedItem.spent' => 'nullable|numeric',
                'editedItem.client_retention' => 'nullable|numeric',
                'editedItem.client_utility' => 'nullable|numeric',
                'editedItem.difference_incomes' => 'nullable|numeric',
                'editedItem.difference_spent' => 'nullable|numeric',
                'editedItem.difference_utility' => 'nullable|numeric',
                'editedItem.difference_retention' => 'nullable|numeric',
                'editedItem.description' => 'nullable|string',
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
                'spent' => $editedItem['spent'] ?? 0,
                'system_incomes' => $editedItem['system_incomes'] ?? 0,
                'client_retention' => $editedItem['client_retention'] ?? 0,
                'client_utility' => $editedItem['client_utility'] ?? 0,
                'difference_utility' => $editedItem['difference_utility'] ?? 0,
                'difference_incomes' => $editedItem['difference_incomes'] ?? 0,
                'difference_spent' => $editedItem['difference_spent'] ?? 0,
                'difference_retention' => $editedItem['difference_retention'] ?? 0,
                'description' => $editedItem['description'] ?? '',
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
            $now = Carbon::now();
            $boxData = $now->format('Y-m-d H:i:s');
            $monthName = Carbon::createFromFormat('Y-m', $month)
            ->locale('es') // Establecer idioma español
            ->isoFormat('MMMM [del] YYYY'); // Formato deseado
            $carbonMonth = Carbon::createFromFormat('Y-m', $month);
            $startDate = $carbonMonth->copy()->startOfMonth()->toDateString();
            $endDate = $carbonMonth->copy()->endOfMonth()->toDateString();
            $boxClose = BoxClose::fromSub(function ($query) use ($startDate, $endDate, $branchId, $businessId) {
                $query->selectRaw('
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
                ->from('box_closes')
                ->join('boxes', 'boxes.id', '=', 'box_closes.box_id')
                ->join('branches', 'branches.id', '=', 'boxes.branch_id')
                ->where('box_closes.type', 'Diario')
                ->whereBetween('box_closes.data', [$startDate, $endDate])
                ->when($branchId, function($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                })
                ->when($businessId && !$branchId, function($query) use ($businessId) {
                    $query->where('branches.business_id', $businessId);
                })
                ->whereIn('box_closes.id', function($subQuery) use ($startDate, $endDate, $branchId, $businessId) {
                    $subQuery->select(DB::raw('MAX(box_closes.id)'))
                        ->from('box_closes')
                        ->join('boxes', 'boxes.id', '=', 'box_closes.box_id')
                        ->join('branches', 'branches.id', '=', 'boxes.branch_id')
                        ->where('box_closes.type', 'Diario')
                        ->whereBetween('box_closes.data', [$startDate, $endDate])
                        ->when($branchId, function($query) use ($branchId) {
                            $query->where('branches.id', $branchId);
                        })
                        ->when($businessId && !$branchId, function($query) use ($businessId) {
                            $query->where('branches.business_id', $businessId);
                        })
                        ->groupBy(DB::raw('DATE(box_closes.data)'));
                });
            }, 'box_closes')->first();
            DB::commit();
            $user = auth()->user();

            // Verificar si está autenticado y tiene un profesional asociado
            if ($user && $user->professional) {
                $professionalName = $user->professional->name;
                // También puedes acceder a otros campos:
                // $professionalId = $user->professional->id;
                // $professionalSpecialty = $user->professional->specialty;
            } else {
                // Manejar el caso cuando no hay usuario autenticado o no tiene profesional
                $professionalName = 'No asignado';
            }
            if ($branchId) {
                $entity = Branch::findOrFail($branchId);
                $entityName = $entity->name;                
                $businessName = $entity->business->name;
                $entityType = 'Sucursal';
            } elseif ($businessId) {
                $entity = Business::findOrFail($businessId);
                $businessName = $entity->name;
                $entityName = ''; 
                $entityType = 'Negocio';
            } else {
                // Manejar caso donde no se proporciona ninguno (opcional)
                $entityName = 'General';
                $entityType = 'Reporte';
            }
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecajamensualEjecutado', ['branchBusinessName' => $businessName, 'branchName' => $entityName, 'entityType' => $entityType, 'boxData' => $boxData, 'boxcloseData' => $boxClose, 'editedItem' =>  $editedItem, 'nameProfessional' => $professionalName, 'monthName' => $monthName]);
            $reporte = $pdf->output();
            //Aqui hacer la logicac de enviar el correo
            $emailsQuery = Professional::whereHas('charge', function($query) {
                $query->where('name', 'Administrador')
                      ->orWhere('name', 'Administrador de Sucursal');
            });
            
            if ($branchId) {
                // Filtros para profesionales de la branch específica
                $emailsQuery->whereHas('branches', function($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                });
                
                // Obtenemos emails de asociados directamente desde la relación
                $emailAssociated = Associated::whereHas('branches', function($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                })->pluck('email');
            
            } elseif ($businessId) {
                // Filtros para profesionales de todas las branches del business
                $emailsQuery->whereHas('branches', function($query) use ($businessId) {
                    $query->whereHas('business', function($q) use ($businessId) {
                        $q->where('id', $businessId);
                    });
                });
                
                // Obtenemos emails de asociados a través de la relación branches->business
                $emailAssociated = Associated::whereHas('branches', function($query) use ($businessId) {
                    $query->whereHas('business', function($q) use ($businessId) {
                        $q->where('id', $businessId);
                    });
                })->pluck('email');
            }

            // Obtener emails y combinar
            $professionalEmails = $emailsQuery->pluck('email');
            $associateEmails = $emailAssociated->toArray();

            $mergedEmails = $professionalEmails->merge($associateEmails)
                                            ->unique()
                                            ->values()
                                            ->toArray();

            Log::info('Correos para cierre de mes', ['emails' => $mergedEmails]);
            //$mergedEmails = ['yasmany891230@gmail.com', 'deylert89@gmail.com', 'evylabrada@gmail.com'];
            //$mergedEmails = ['yasmany891230@gmail.com'];
            foreach ($mergedEmails as $email) {
                try {
                    $this->sendEmailService->emailBoxClosureMonthlyEjecutado(
                        $email,
                        $reporte,
                        $businessName,
                        $entityName,
                        $entityType,
                        $boxData,
                        $boxClose,
                        $editedItem,
                        $professionalName,
                        $monthName
                    );
                } catch (\Swift_TransportException $e) {
                    Log::error("Error al enviar correo a $email: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                }
            }
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
                'editedItem.id' => 'nullable|numeric',
                'editedItem.available_money' => 'nullable|numeric|min:0',
                'editedItem.utility' => 'nullable|numeric',
                'editedItem.net_utility' => 'nullable|numeric',
                'editedItem.retention' => 'nullable|numeric|min:0',
                'editedItem.discounts' => 'nullable|numeric|min:0',
                'editedItem.differences' => 'nullable|numeric',
                'editedItem.system_incomes' => 'nullable|numeric',
                'editedItem.spent' => 'nullable|numeric',
                'editedItem.client_retention' => 'nullable|numeric',
                'editedItem.client_utility' => 'nullable|numeric',
                'editedItem.difference_incomes' => 'nullable|numeric',
                'editedItem.difference_spent' => 'nullable|numeric',
                'editedItem.difference_utility' => 'nullable|numeric',
                'editedItem.difference_retention' => 'nullable|numeric',
                'editedItem.description' => 'nullable|string',
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
                'branch_id' => $editedItem['branch_id'] ?? null,
                'business_id' => $editedItem['business_id'] ?? null,
                'available_money' => $editedItem['available_money'] ?? 0,
                'utility' => $editedItem['utility'] ?? 0,
                'net_utility' => $editedItem['net_utility'] ?? 0,
                'retention' => $editedItem['retention'] ?? 0,
                'discounts' => $editedItem['discounts'] ?? 0,
                'differences' => $editedItem['differences'] ?? 0,
                'spent' => $editedItem['spent'] ?? 0,
                'system_incomes' => $editedItem['system_incomes'] ?? 0,
                'client_retention' => $editedItem['client_retention'] ?? 0,
                'client_utility' => $editedItem['client_utility'] ?? 0,
                'difference_utility' => $editedItem['difference_utility'] ?? 0,
                'difference_incomes' => $editedItem['difference_incomes'] ?? 0,
                'difference_spent' => $editedItem['difference_spent'] ?? 0,
                'difference_retention' => $editedItem['difference_retention'] ?? 0,
                'description' => $editedItem['description'] ?? '',
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
                'editedItem.client_retention' => 'nullable|numeric|min:0',
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
                'client_retention' => $editedItem['client_retention'] ?? 0,
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
                'id' => 'nullable|integer'
            ]);

            $editedItem = $validatedData['editedItem'];
            $recordId = $editedItem['id'] === "" ? null : $editedItem['id'];

            // Preparar datos para actualización/creación
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
                'spent' => $editedItem['spent'] ?? 0,
                'system_incomes' => $editedItem['system_incomes'] ?? 0,
                'client_retention' => $editedItem['client_retention'] ?? 0,
                'client_utility' => $editedItem['client_utility'] ?? 0,
                'difference_utility' => $editedItem['difference_utility'] ?? 0,
                'difference_incomes' => $editedItem['difference_incomes'] ?? 0,
                'difference_spent' => $editedItem['difference_spent'] ?? 0,
                'difference_retention' => $editedItem['difference_retention'] ?? 0,
                'description' => $editedItem['description'] ?? '',
                'incomes' => json_encode($editedItem['incomes'] ?? []),
                'expenses' => json_encode($editedItem['expenses'] ?? []),
                'user_id' => auth()->id(),
            ];

            // Inicializar $closure como null
            $closure = null;

            if ($recordId) {
                $closure = MonthlyClosure::find($recordId);
                if ($closure) {
                    $closure->update($updateData);
                } else {
                    throw new \Exception("No se encontró el cierre mensual con ID: $recordId");
                }
            } else {
                // Crear nuevo registro si no hay ID
                $closure = MonthlyClosure::create($updateData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $recordId ? 'Actualización realizada correctamente' : 'Cierre creado correctamente',
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
            Log::error('Error al procesar cierre mensual: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el cierre mensual',
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
            $boxTotals = BoxClose::calculatePreviousMonthTotalAmount($branchId, $businessId, $month);

            return response()->json([
                'success' => true,
                'utility' => $result['utility'],
                'system_incomes' => $result['income'],
                'spent' => $result['expense'],
                'totalMount' => $boxTotals['totalMount'],
                'finance_ids' => $result['ids'],
                'retentions_total' => $retentions['total'],
                'retention_ids' => $retentions['ids'],
                'message' => 'Cálculo de utilidad realizado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al calcular utilidad: ' . $e->getMessage(), [
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
