<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Associated;
use App\Models\Box;
use App\Models\BoxClose;
use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\BranchRuleProfessional;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\CashierBoxClosing;
use App\Models\CashierSale;
use App\Models\CloseBox;
use App\Models\Finance;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Retention;
use App\Models\WorkerPurchase;
use App\Services\CashierBoxClosingService;
use App\Services\MetaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SendEmailService;
use App\Services\TraceService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportException;

class BoxCloseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private SendEmailService $sendEmailService;
    private TraceService $traceService;
    private MetaService $metaService;
    private CashierBoxClosingService $cashierBoxClosingService;
    public function __construct(SendEmailService $sendEmailService, TraceService $traceService, MetaService $metaService, CashierBoxClosingService $cashierBoxClosingService)
    {

        $this->sendEmailService = $sendEmailService;
        $this->traceService = $traceService;
        $this->metaService = $metaService;
        $this->cashierBoxClosingService = $cashierBoxClosingService;
    }

    public function index()
    {
        //
    }

    public function boxClosesDiary(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'data' => 'nullable|date',
                'endDate' => 'nullable|date',
            ]);

            $boxes = Box::with(['boxClose'])
                ->where('branch_id', $request->branch_id)
                ->when($request->filled('data') && $request->filled('endDate'), function ($query) use ($request) {
                    $query->whereBetween('data', [
                        Carbon::parse($request->data)->startOfDay(),
                        Carbon::parse($request->endDate)->endOfDay()
                    ]);
                }, function ($query) {
                    $query->whereBetween('data', [
                        Carbon::now()->startOfMonth(),
                        Carbon::now()->endOfMonth()
                    ]);
                })
                ->get()
                ->flatMap(function ($box) {
                    // Procesar todos los boxClose en lugar de solo el primero
                    return $box->boxClose->map(function ($boxClose) use ($box) {
                        // Obtener cierres de caja del cajero que coincidan en user_id, fecha y tipo
                        $cashierBoxClosing = DB::table('cashier_box_closings')
                            ->leftJoin('users', 'cashier_box_closings.user_id', '=', 'users.id')
                            ->leftJoin('professionals', 'professionals.user_id', '=', 'users.id')
                            ->where('cashier_box_closings.box_close_id', $boxClose->id)
                            ->select(
                                'cashier_box_closings.*',
                                'professionals.name as professional_name'
                            )
                            ->first();

                        // Obtener bonos detallados para este día
                        $totalBonus = ProfessionalPayment::where('branch_id', $box->branch_id)
                            ->whereDate('date', $box->data)
                            ->whereIn('type', ['Bono servicios', 'Bono convivencias'])
                            ->sum('amount');

                        $baseData = [
                            'box_id' => $box->id,
                            'branch_id' => $box->branch_id,
                            'data' => $box->data,
                            'cashFound' => $box->cashFound ?? 0,
                            'existence' => $box->existence ?? 0,
                            'extraction' => $box->extraction ?? 0,
                        ];

                        $closeData = [
                            'id' => $boxClose->id,
                            'totalMount' => $boxClose->totalMount ?? 0,
                            'totalService' => $boxClose->totalService ?? 0,
                            'totalProduct' => $boxClose->totalProduct ?? 0,
                            'totalTip' => $boxClose->totalTip ?? 0,
                            'totalCash' => $boxClose->totalCash ?? 0,
                            'totalDebit' => $boxClose->totalDebit ?? 0,
                            'totalCreditCard' => $boxClose->totalCreditCard ?? 0,
                            'totalTransfer' => $boxClose->totalTransfer ?? 0,
                            'totalOther' => $boxClose->totalOther ?? 0,
                            'totalCardGif' => $boxClose->totalCardGif ?? 0,
                            'totalBonus' => $totalBonus ?? 0,
                            'close_type' => $boxClose->type,
                            'user_id' => $boxClose->user_id ?? null,
                            'type' => $boxClose->type,
                            'time' => $boxClose->created_at->format('H:i'),
                            'advancement' => $cashierBoxClosing->advancement ?? 0,
                        ];

                        $mergedData = array_merge($baseData, $closeData);

                        if ($cashierBoxClosing) {
                            $cashierData = [
                                'cashier_close_id' => $cashierBoxClosing->id,
                                'cashier_user_id' => $cashierBoxClosing->user_id ?? null,
                                'cashier_total' => $cashierBoxClosing->total ?? 0,
                                'cashier_existence' => $cashierBoxClosing->existence ?? 0,
                                'cashier_extraction' => $cashierBoxClosing->extraction ?? 0,
                                'cashier_cashFound' => $cashierBoxClosing->cashFound ?? 0,
                                'cashier_totalService' => $cashierBoxClosing->totalService ?? 0,
                                'cashier_totalProduct' => $cashierBoxClosing->totalProduct ?? 0,
                                'cashier_totaCash' => $cashierBoxClosing->totalCash ?? 0,
                                'cashier_totalCreditCard' => $cashierBoxClosing->totalCreditCard ?? 0,
                                'cashier_totalDebit' => $cashierBoxClosing->totalDebit ?? 0,
                                'cashier_totalTransfer' => $cashierBoxClosing->totalTransfer ?? 0,
                                'cashier_totalOther' => $cashierBoxClosing->totalOther ?? 0,
                                'cashier_totalCardGif' => $cashierBoxClosing->totalCardGif ?? 0,
                                'cashier_totalBonus' => $cashierBoxClosing->totalBonus ?? 0,
                                'cashier_differencePay' => $cashierBoxClosing->differencePay ?? 0,
                                'cashier_difference' => $cashierBoxClosing->difference ?? 0,
                                'description' => $cashierBoxClosing->description ?? 0,
                                'cashier_totalBonus' => $cashierBoxClosing->totalBonus ?? 0,
                                'details' => $cashierBoxClosing->details,
                                'professional_name' => $cashierBoxClosing->professional_name ?? 'No asignado',
                                'cashier_advancement' => $cashierBoxClosing->advancement ?? 0,
                            ];

                            $mergedData = array_merge($mergedData, $cashierData);
                        }

                        return $mergedData;
                    });
                });

            return response()->json(['boxcloses' => $boxes], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {

        DB::beginTransaction();
        try {
            Log::info("Cierre de caja Del Sistema");
            $request->validate([
                'editedCloseBox' => 'required|array',
                'cashierData' => 'required|array',
                'car_ids'  => 'nullable|array',
                'cashiersale_ids'  => 'nullable|array',
                'workerpurchase_ids'  => 'nullable|array',
                'branch_id' => 'required|integer',
                'nameProfessional' => 'required|string',
            ]);

            // Obtener los datos del request
            $editedCloseBox = $request->input('editedCloseBox');
            $cashierData = $request->input('cashierData');
            $car_ids = $request->input('car_ids');
            $cashiersale_ids = $request->input('cashiersale_ids');
            $workerpurchase_ids = $request->input('workerpurchase_ids');
            $branchId = $request->input('branch_id');
            $nameProfessional = $request->input('nameProfessional');

            // Log para depuración
            Log::info('Datos recibidos para cerrar caja:', [
                'editedCloseBox' => $editedCloseBox,
                'cashierData' => $cashierData,
                'car_ids' => $car_ids,
                'cashiersale_ids' => $cashiersale_ids,
                'workerpurchase_ids' => $workerpurchase_ids,
                'branch_id' => $branchId,
                'nameProfessional' => $nameProfessional,
            ]);

            $userId = $request->user()->id;
            $idService = null;
            $totalBonus = $editedCloseBox['totalBonus'];
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $request->branch_id)->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $request->branch_id)->with('business')->first();
            $boxClose = new BoxClose();

            //Recalcular Bonos
            $totalAmount = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', Carbon::now())->where(function ($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->sum('amount');
            $bonus = $this->metaService->store($branch);
            $bonusCollection = collect($bonus);

            // Calcular la suma de 'amount'
            $totalBonus = $bonusCollection->sum('amount');

            if ($totalBonus) {
                // Calcular la suma de los montos
                //$totalAmount = $subquery->sum('amount');
                $difference = $totalBonus - $totalAmount;
                // Ajustar la existencia de $box según la diferencia
                // Si la diferencia es positiva, se resta de box->existence
                // Si es negativa, se suma a box->existence
                $box->existence -= $difference;
                $box->save(); // Guardar los cambios en $box
            }
            //end Recalcular Bonos
            if (!isset($editedCloseBox['workerpurchase'])) {
                $editedCloseBox['workerpurchase'] = 0; // Asigna 0 si no existe
            }
            $boxClose->box_id = $box->id;
            $boxClose->totalMount = $editedCloseBox['totalMount'];
            $boxClose->totalService = $editedCloseBox['totalService'];
            $boxClose->totalProduct = $editedCloseBox['totalProduct'];
            $boxClose->totalTip = $editedCloseBox['totalTip'];
            $boxClose->totalCash = $editedCloseBox['totalCash'];
            $boxClose->totalCreditCard = $editedCloseBox['totalCreditCard'];
            $boxClose->totalDebit = $editedCloseBox['totalDebit'];
            $boxClose->totalTransfer = $editedCloseBox['totalTransfer'];
            $boxClose->totalOther = $editedCloseBox['totalOther'];
            $boxClose->totalCardGif = $editedCloseBox['totalCardGif'];
            $boxClose->user_id = $userId;
            $boxClose->data = Carbon::now();
            $boxClose->save();

            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => '',
                'amount' => $editedCloseBox['totalMount'],
                'operation' => 'Cierre de Caja Del sistema',
                'details' => 'Cierre de Caja efectuado correctamente',
                'description' => ''
            ];
            $this->traceService->store($trace);
            $cashierData['branch_id'] = $branchId;
            $cashierData['user_id'] = $userId;
            $cashierData['data'] = Carbon::now();
            $cashierData['type'] = 'Diario';
            $cashierData['box_close_id'] = $boxClose->id;
            $cashierData['advancement'] = $editedCloseBox['advancement'];
            $cashierData['differenceAccounts'] = $editedCloseBox['workerpurchase'];
            //$cashierData['totalCash'] = ($cashierData['existence'] + $editedCloseBox['advancement'] + $totalBonus) - ($cashierData['extraction'] ?? 0);
            $boxCloseCashier = $this->cashierBoxClosingService->upsertCashierBoxClosing($cashierData);
            if (!empty($car_ids)) {
                Car::whereIn('id', $car_ids)
                    ->update(['user_id' => $userId]);
            }
            if (!empty($cashiersale_ids)) {
                CashierSale::whereIn('id', $cashiersale_ids)
                    ->update(['user_id' => $userId]);
            }
            if(!empty($workerpurchase_ids)){
                WorkerPurchase::whereIn('id', $workerpurchase_ids)
                ->update(['user_id' => $userId]);
            }
            DB::commit();
            //$professionals = $professionals->toArray();
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $editedCloseBox, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus, 'cashierData' => $cashierData, 'nameProfessional' => $nameProfessional]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })->whereHas('branches', function ($query) use ($branch) {
                $query->where('branches.id', $branch->id);
            })->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);
            foreach ($mergedEmails as $email) {
                try {
                    $this->sendEmailService->emailBoxClosure($email, $reporte, $branch->business['name'], $branch['name'], $box, $editedCloseBox, $totalBonus, $cashierData, $nameProfessional);
                } catch (\Swift_TransportException $e) {
                    Log::error("Error al enviar correo a $email: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                }
            }
            return response()->json(['msg' => 'Cierre de caja realizado correctamente', 'boxClosePartial' => $boxCloseCashier], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function store_cashier(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info("Cierre de caja parcial");
            $request->validate([
                'editedCloseBox' => 'required|array',
                'cashierData' => 'required|array',
                'car_ids'  => 'nullable|array',
                'cashiersale_ids'  => 'nullable|array',
                'workerpurchase_ids'  => 'nullable|array',
                'branch_id' => 'required|integer',
                'nameProfessional' => 'required|string',
            ]);

            // Obtener los datos del request
            $editedCloseBox = $request->input('editedCloseBox');
            $cashierData = $request->input('cashierData');
            $car_ids = $request->input('car_ids');
            $cashiersale_ids = $request->input('cashiersale_ids');
            $workerpurchase_ids = $request->input('workerpurchase_ids');
            $branchId = $request->input('branch_id');
            $nameProfessional = $request->input('nameProfessional');

            // Log para depuración
            Log::info('Datos recibidos para cerrar caja:', [
                'editedCloseBox' => $editedCloseBox,
                'cashierData' => $cashierData,
                'car_ids' => $car_ids,
                'cashiersale_ids' => $cashiersale_ids,
                'workerpurchase_ids' => $workerpurchase_ids,
                'branch_id' => $branchId,
                'nameProfessional' => $nameProfessional,
            ]);

            $userId = $request->user()->id;
            $idService = null;
            $totalBonus = $editedCloseBox['totalBonus'];
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $request->branch_id)->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $request->branch_id)->with('business')->first();
            $boxClose = new BoxClose();
            if (!isset($editedCloseBox['workerpurchase'])) {
                $editedCloseBox['workerpurchase'] = 0; // Asigna 0 si no existe
            }
            $boxClose->box_id = $box->id;
            $boxClose->totalMount = $editedCloseBox['totalMount'];
            $boxClose->totalService = $editedCloseBox['totalService'];
            $boxClose->totalProduct = $editedCloseBox['totalProduct'];
            $boxClose->totalTip = $editedCloseBox['totalTip'];
            $boxClose->totalCash = $editedCloseBox['totalCash'];
            $boxClose->totalCreditCard = $editedCloseBox['totalCreditCard'];
            $boxClose->totalDebit = $editedCloseBox['totalDebit'];
            $boxClose->totalTransfer = $editedCloseBox['totalTransfer'];
            $boxClose->totalOther = $editedCloseBox['totalOther'];
            $boxClose->totalCardGif = $editedCloseBox['totalCardGif'];
            $boxClose->user_id = $userId;
            $boxClose->type = 'Parcial';
            $boxClose->data = Carbon::now();
            $boxClose->save();

            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => '',
                'amount' => $boxClose['totalMount'],
                'operation' => 'Cierre de Caja Parcial',
                'details' => 'Cierre de Caja efectuado correctamente',
                'description' => ''
            ];
            $this->traceService->store($trace);
            $cashierData['branch_id'] = $branchId;
            $cashierData['user_id'] = $userId;
            $cashierData['data'] = Carbon::now();
            $cashierData['type'] = 'Parcial';
            $cashierData['box_close_id'] = $boxClose->id;
            $cashierData['differenceAccounts'] = $editedCloseBox['workerpurchase'];
            //$cashierData['totalCash'] = ($cashierData['existence'] - ($cashierData['extraction'] ?? 0));
            $boxCloseCashier = $this->cashierBoxClosingService->upsertCashierBoxClosing($cashierData);
            // Actualizar los registros en la tabla cars
            if (!empty($car_ids)) {
                Car::whereIn('id', $car_ids)
                    ->update(['user_id' => $userId]);
            }
            if (!empty($cashiersale_ids)) {
                CashierSale::whereIn('id', $cashiersale_ids)
                    ->update(['user_id' => $userId]);
            }
            if(!empty($workerpurchase_ids)){
                WorkerPurchase::whereIn('id', $workerpurchase_ids)
                ->update(['user_id' => $userId]);
            }
            DB::commit();
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecajaparcial', ['data' => $editedCloseBox, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus, 'cashierData' => $cashierData, 'nameProfessional' => $nameProfessional]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })->whereHas('branches', function ($query) use ($branch) {
                $query->where('branches.id', $branch->id);
            })->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);
               foreach ($mergedEmails as $email) {
                try {
                    $this->sendEmailService->emailBoxClosureParcial($email, $reporte, $branch->business['name'], $branch['name'], $box, $editedCloseBox, $totalBonus, $cashierData, $nameProfessional);
                } catch (\Swift_TransportException $e) {
                    Log::error("Error al enviar correo a $email: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                }
            }
            return response()->json(['msg' => 'Cierre de caja realizado correctamente', 'boxClosePartial' => $boxCloseCashier], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store_cashier');
            Log::error($th);

            //DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function store_cashier_confirm(Request $request)
    {

        try {
            Log::info("Cierre de caja parcial confirmación");

            $request->validate([
                'id' => 'nullable|numeric',  // Cambiado a nullable
                'description' => 'required|string',
            ]);
            // Obtener el usuario autenticado
            $userId = Auth::id();

            // Buscar el registro a actualizar
            if (empty($request->id)) {
                // Si no viene ID, buscar el último registro del usuario para el día actual
                $cashier = CashierBoxClosing::where('user_id', $userId)
                    ->whereDate('created_at', Carbon::today())
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$cashier) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontró ningún cierre de caja para actualizar'
                    ], 404);
                }
            } else {
                // Si viene ID, buscar por ese ID
                $cashier = CashierBoxClosing::find($request->id);

                if (!$cashier) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El cierre de caja especificado no existe'
                    ], 404);
                }

                // Verificar que el registro pertenezca al usuario actual (opcional)
                if ($cashier->user_id != $userId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para actualizar este registro'
                    ], 403);
                }
            }

            // Actualizar la descripción
            $cashier->description = $request->description;
            $cashier->save();

            return response()->json(['msg' => 'Cierre de caja confirmado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store_cashier_confirm');
            Log::error($th);

            //DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function box_close_new(Request $request)
    {

        DB::beginTransaction();
        try {
            Log::info("Cierre de caja Forzado");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $idService = null;
            $totalBonus = 0;
            Log::info($data);
            $box = Box::whereDate('data', $data['data'])->where('branch_id', $data['branch_id'])->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $data['branch_id'])->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }
            $totalAmount = ProfessionalPayment::where('branch_id', $data['branch_id'])->whereDate('date', $data['data'])->where(function ($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->sum('amount');
            $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->select('id', 'name', 'image_url', 'retention')->get();
            foreach ($professionals as $professional) {
                $bonus[] = $this->metaService->store_box_close($branch, $data['data'], $professional->id);
            }
            $bonusCollection = collect($bonus);

            // Aplanar el array para acceder directamente a los valores de 'amount'
            $flattenedBonus = $bonusCollection->flatMap(function ($item) {
                return collect($item); // Asegurarse de que cada elemento sea una colección
            });

            // Calcular la suma de 'amount'
            $totalBonus = $flattenedBonus->sum('amount');
            if ($totalBonus) {
                // Calcular la suma de los montos
                //$totalAmount = $subquery->sum('amount');
                $difference = $totalBonus - $totalAmount;
                // Ajustar la existencia de $box según la diferencia
                // Si la diferencia es positiva, se resta de box->existence
                // Si es negativa, se suma a box->existence
                $box->existence -= $difference;
                $box->save(); // Guardar los cambios en $box
            }
            $payments = Payment::where('branch_id', $data['branch_id'])->whereDate('created_at', $data['data'])->get();
            $cars_id = $payments->pluck('car_id');
            $cars = Car::whereIn('id', $cars_id)->get();
            $orders = Order::whereIn('car_id', $cars_id)->get();
            $cashiers = CashierSale::where('branch_id', $data['branch_id'])->whereDate('data', $data['data'])->get();
            $services = $orders->where('is_product', 0)->sum('price');
            $products = $orders->where('is_product', 1)->sum('price') + $cashiers->sum('price');
            $totalMount = $services + $products + $cars->sum('tip');
            $boxClose->box_id = $box->id;
            $boxClose->totalMount = $totalMount;
            $boxClose->totalService = $services;
            $boxClose->totalProduct = $products;
            $boxClose->totalTip = $cars->sum('tip');
            $boxClose->totalCash = $payments->sum('cash');
            $boxClose->totalCreditCard = $payments->sum('creditCard');
            $boxClose->totalDebit = $payments->sum('debit');
            $boxClose->totalTransfer = $payments->sum('transfer');
            $boxClose->totalOther = $payments->sum('other');
            $boxClose->totalCardGif = $payments->sum('cardGif');
            $boxClose->data = $data['data'];
            $boxClose->save();

            DB::commit();
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            $this->sendEmailService->emailBoxClosure('yasmany891230@gmail.com', $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $cars->sum('tip'), $products, $services, $payments->sum('cash'), $payments->sum('creditCard'), $payments->sum('debit'), $payments->sum('transfer'), $payments->sum('other'), $totalMount, $payments->sum('cardGif'), $totalBonus);
            return response()->json(['msg' => 'Cierre de caja realizado correctamente', 'bonus' => $bonus], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function store1(Request $request)
    {

        DB::beginTransaction();
        try {
            Log::info("Editar store1 BoxCloseController");
            $data = $request->validate([
                //'box_id' => 'required|numeric',
                'data' => 'required|date',
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $idService = null;
            $totalBonus = 0;
            Log::info($data);
            $box = Box::whereDate('data', $data['data'])->where('branch_id', $data['branch_id'])->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $data['branch_id'])->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }
            $totalAmount = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', $data['data'])->where('professional_id', $data['professional_id'])->where(function ($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->sum('amount');
            $bonus = $this->metaService->store1($branch, $data['data'], $data['professional_id']);
            $bonusCollection = collect($bonus);

            // Calcular la suma de 'amount'
            $totalBonus = $bonusCollection->sum('amount');
            if ($totalBonus) {
                // Calcular la suma de los montos
                //$totalAmount = $subquery->sum('amount');
                $difference = $totalBonus - $totalAmount;
                // Ajustar la existencia de $box según la diferencia
                // Si la diferencia es positiva, se resta de box->existence
                // Si es negativa, se suma a box->existence
                $box->existence -= $difference;
                $box->save(); // Guardar los cambios en $box
            }
            DB::commit();
            return response()->json(['msg' => 'Pago de bonos realizado correctamente', 'bonus' => $bonus], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store1');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BoxClose $closeBox)
    {
        //
    }

    public function bonus(Request $request)
    {
        Log::info("Mostrar los bonos");
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            $bonus = $this->metaService->bonus($data['branch_id']);
            $bonus = collect($bonus)->map(function ($bono) {
                $professionalPayment = ProfessionalPayment::where('branch_id', $bono['branch_id'])
                    ->where('professional_id', $bono['professional_id'])
                    ->whereDate('date', Carbon::now())
                    ->where('type', $bono['bonus'])
                    ->first();

                // Comparar el amount del bono con el del pago (si existe)
                $bono['pay'] = ($professionalPayment && $professionalPayment->amount == $bono['amount']) ? 1 : 0;

                return $bono;
            })->toArray();
            return response()->json(['bonus' => $bonus], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function bonu_payment(Request $request)
    {
        Log::info("Pagar un bono");
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric|exists:branches,id',
                'professional_id' => 'required|numeric|exists:professionals,id',
                'order_id' => 'nullable',
                'name' => 'required|max:250',
                'amount' => 'required',
                'type' => 'required',
                'cant' => 'required',
                'retention' => 'required',
            ]);

            Log::info('Datos del Bono');
            Log::info($data);
            $totalAmount = 0;

            //$finance = Finance::where('branch_id', $branch->id)->where('expense_id', 5)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
            $finance = Finance::orderBy('control', 'desc')->first();
            if ($finance !== null) {
                $control = $finance->control + 1;
            } else {
                $control = 1;
            }
            //return 1;
            $professional = Professional::find($data['professional_id']);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $data['branch_id'])->first();
            if (strval($data['type']) === "Bono convivencias") {
                $subquery = ProfessionalPayment::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('date', Carbon::now())->where('type', $data['type'])->first();
                if ($subquery != null) {
                    // Calcular la suma de los montos
                    $totalAmount = $subquery->amount;
                    // Eliminar los registros
                    $subquery->delete();
                }
                if ($totalAmount != 0) {
                    // Calcular la diferencia entre el monto actual y el nuevo monto
                    $difference = $data['amount'] - $totalAmount;
                         // Ajustar la existencia de $box según la diferencia
                    if ($box != null) {
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $difference;
                        $box->save(); // Guardar los cambios en $box
                    }
                } else {
                    if ($box != null) {
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $data['amount'];
                        $box->save(); // Guardar los cambios en $box
                    }
                }
                //if ($professionalPayment == null) {
                $professionalPayment = new ProfessionalPayment();
                //}
                $professionalPayment->branch_id = $data['branch_id'];
                $professionalPayment->professional_id = $professional->id;
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $data['amount'];
                $professionalPayment->type = $data['type'];
                $professionalPayment->cant = $data['cant'];
                $professionalPayment->save();
                $finance = Finance::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->where('comment', 'Gasto por pago de bono de convivencias a ' . $professional->name)->first();
                if ($finance != null) {
                    // Actualizar el monto de finance
                    $finance->amount = $data['amount'];
                    $finance->save();
                } else {
                    $finance = new Finance();
                    $finance->control = $control++;
                    $finance->operation = 'Gasto';
                    $finance->amount = $data['amount'];
                    $finance->comment = 'Gasto por pago de bono de convivencias a ' . $professional->name;
                    $finance->branch_id = $data['branch_id'];
                    $finance->type = 'Sucursal';
                    $finance->expense_id = 5;
                    $finance->data = Carbon::now();
                    $finance->file = '';
                    $finance->save();

                }
                // Ejemplo de una acción, como actualizar un campo
                Order::whereIn('id', $data['order_id'])->update(['meta' => 1, 'percent_win' => 0]);
            }
            if (strval($data['type']) === "Bono servicios") {
                $subquery = ProfessionalPayment::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('date', Carbon::now()->toDateString())->where('type', $data['type'])->first();
                if ($subquery != null) {
                    // Calcular la suma de los montos
                    $totalAmount = $subquery->amount;
                    // Eliminar los registros
                    $subquery->delete();
                }

                if ($totalAmount) {
                    // Calcular la diferencia entre el monto actual y el nuevo monto
                    $difference = $data['amount'] - $totalAmount;
                    // Ajustar la existencia de $box según la diferencia
                    if ($box != null) {
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $difference;
                        $box->save(); // Guardar los cambios en $box
                    }
                } else {
                    if ($box != null) {
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $data['amount'];
                        $box->save(); // Guardar los cambios en $box
                    }
                }
                $professionalPaymentService = new ProfessionalPayment();
                //}
                $professionalPaymentService->branch_id = $data['branch_id'];
                $professionalPaymentService->professional_id = $professional->id;
                $professionalPaymentService->date = Carbon::now();
                $professionalPaymentService->amount = $data['amount'];
                $professionalPaymentService->type = $data['type'];
                $professionalPaymentService->cant = $data['cant'];
                $professionalPaymentService->save();

                $finance = Finance::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->where('comment', 'Gasto por pago de bono de servicios a ' . $professional->name)->first();
                if ($finance != null) {
                    // Actualizar el monto de finance
                    $finance->amount = $data['amount'];
                    $finance->save();
                } else {
                    $finance = new Finance();
                    $finance->control = $control++;
                    $finance->operation = 'Gasto';
                    $finance->amount = $data['amount'];
                    $finance->comment = 'Gasto por pago de bono de servicios a ' . $professional->name;
                    $finance->branch_id = $data['branch_id'];
                    $finance->type = 'Sucursal';
                    $finance->expense_id = 5;
                    $finance->data = Carbon::now();
                    $finance->file = '';
                    $finance->save();
                }
            }
            DB::commit();
            return response()->json(['msg' => 'Pago realizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->bonu_payment');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function box_close_month(Request $request)
    {
        $codigo = $request->query('codigo');  // Captura el parámetro "codigo" de la URL

        // Log para verificar el valor de código

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            return response()->json(['msg' => 'Código inválido'], 403);
        }
        try {
            // Obtener la fecha actual
            $now = Carbon::now();

            $mesAnterior = $now->copy()->subMonth()->month;
            $añoAnterior = $now->copy()->subMonth()->year;
            //$boxCloseData = [];
            $professionalsData = [];

            $ingreso = 0;
            $gasto = 0;
            $branches = Branch::all();
            foreach ($branches as $branch) {
                Finance::where('branch_id', $branch->id)
                    ->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)
                    ->where('operation', 'Gasto')
                    ->where('comment', 'like', '%Gasto por pago de bono de productos%')
                    ->delete();
                //Retention
                Retention::where('branch_id', $branch->id)
                    ->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->where('type', 'Products')->delete();

                ProfessionalPayment::where('branch_id', $branch->id)->whereYear('date', $añoAnterior)->whereMonth('date', $mesAnterior)->where('type', 'Bono productos')->delete();

                $boxCloseData = [];
                $winProduct = 0;
                $professionalsData = [];
                $ingreso = 0;
                $gasto = 0;
                $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->whereHas('charge', function ($query) {
                    $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                })->select('id', 'name', 'surname', 'retention')->get();
                foreach ($professionals as $professional) {
                    $cars = Car::whereHas('reservation', function ($query) use ($branch, $añoAnterior, $mesAnterior) {
                        $query->where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior);
                    })
                        ->with(['clientProfessional.client', 'reservation'])
                        ->whereHas('clientProfessional', function ($query) use ($professional) {
                            $query->where('professional_id', $professional->id);
                        })
                        ->where('pay', 1)
                        ->get();
                    $carIdsPay = $cars->pluck('id');
                    $products = Order::whereIn('car_id', $carIdsPay)
                        ->where('is_product', 1)
                        ->groupBy('product_store_id')
                        ->selectRaw('product_store_id, SUM(cant) as total_cant, SUM(percent_win) as total_percent_win')
                        ->get();
                    $venta = $products->sum('total_cant');
                    $percent_win = $products->sum('total_percent_win');
                    if ($venta <= 24) {
                        $winProduct = $percent_win * 0.15;
                    } else if ($venta > 24 && $venta <= 49) {
                        $winProduct = $percent_win * 0.25;
                    } else {
                        $winProduct = $percent_win * 0.50;
                    }
                    // Agregar los datos del profesional al arreglo solo si $winProduct es mayor que 0
                    if ($winProduct > 0) {
                        $retention = $professional->retention;
                        if ($retention) {
                            $resultRetention = round($winProduct * $retention / 100, 2);
                            $winProduct = $winProduct - $resultRetention;
                            $retention = new Retention();
                            $retention->branch_id = $branch->id;
                            $retention->professional_id = $professional->id;
                            $retention->data = Carbon::now();
                            $retention->retention = $resultRetention;
                            $retention->type = 'Products';
                            $retention->save();
                        }
                        $professionalData = [
                            'name' => $professional->name,
                            'winProduct' => $winProduct,
                        ];

                        $finance = Finance::orderBy('control', 'desc')->first();
                        if ($finance !== null) {
                            $control = $finance->control + 1;
                        } else {
                            $control = 1;
                        }
                        $professionalPayment = new ProfessionalPayment();
                        $professionalPayment->branch_id = $branch->id;
                        $professionalPayment->professional_id = $professional->id;
                        $professionalPayment->date = Carbon::now();
                        $professionalPayment->amount = $winProduct;
                        $professionalPayment->type = 'Bono productos';
                        $professionalPayment->cant = $venta;
                        $professionalPayment->save();


                        $finance = new Finance();
                        $finance->control = $control++;
                        $finance->operation = 'Gasto';
                        $finance->amount = $winProduct;
                        $finance->comment = 'Gasto por pago de bono de productos a ' . $professional->name;
                        $finance->branch_id = $branch->id;
                        $finance->type = 'Sucursal';
                        $finance->expense_id = 5;
                        $finance->data = Carbon::now();
                        $finance->file = '';
                        $finance->save();
                        //}

                        // Agregar los datos del profesional al arreglo general
                        $professionalsData[] = $professionalData;
                    }
                }
                if (!empty($professionalsData)) {
                    $boxData = $now->format('Y-m-d');
                    $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecajamensual', ['branchBusinessName' => $branch->business['name'], 'branchName' => $branch->name, 'boxData' => $boxData, 'professionalBonus' => $professionalsData]);
                    $reporte = $pdf->output();
                    //Aqui hacer la logicac de enviar el correo
                    $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                        $query->where('name', 'Administrador')
                            ->orWhere('name', 'Administrador de Sucursal');
                    })->whereHas('branches', function ($query) use ($branch) {
                        $query->where('branches.id', $branch->id);
                    })
                        ->pluck('email');
                    $emailassociated = [];
                    $emailArray = [];
                    $mergedEmails = [];
                    $emailassociated = $branch->associates()->pluck('email');
                    $emailArray = $emailassociated->toArray();
                    $mergedEmails = $emails->merge($emailArray);
                    foreach ($mergedEmails as $email) {
                        try {
                            $this->sendEmailService->emailBoxClosureMonthly(
                                $email,
                                $reporte,
                                $branch->business['name'],
                                $branch->name,
                                $now->format('Y-m-d'),
                                0,
                                0,
                                0,
                                0,//$boxClose->totalTip,
                                0,//$boxClose->totalProduct,
                                0,//$boxClose->totalService,
                                0,//$boxClose->totalCash,
                                0,//$boxClose->totalCreditCard,
                                0,//$boxClose->totalDebit,
                                0,//$boxClose->totalTransfer,
                                0,//$boxClose->totalOther,
                                0,//$boxClose->totalMount,
                                0,//$boxClose->totalCardGif,
                                0,//round($ingreso, 2),
                                0,//round($gasto, 2),
                                0,//round($ingreso - $gasto, 2),
                                $professionalsData
                            );
                        } catch (\Swift_TransportException $e) {
                            Log::error("Error al enviar correo a $email: " . $e->getMessage());
                        } catch (\Exception $e) {
                            Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                        }
                    }
                }
            }
            return response()->json(['msg' => 'Cierre de caja mensual efectuado correctamente'], 200);
        } catch (TransportException $e) {
            Log::info($e);
            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BoxClose $closeBox)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BoxClose $closeBox)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BoxClose $closeBox)
    {
        //
    }

    public function box_close_automatic(Request $request)
    {
        $codigo = $request->query('codigo'); // Captura el parámetro "codigo" de la URL

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            return response()->json(['msg' => 'Código inválido'], 403);
        }

        try {
            // Obtener la fecha del día anterior
            $yesterday = Carbon::yesterday()->toDateString();

            $totalBonuses = [];
            $branches = Branch::where('id', '!=', 20)->get(); // Obtener todas las sucursales

            foreach ($branches as $branch) {
                DB::beginTransaction();
                try {
                    
                    $existingBox = Box::whereDate('data', $yesterday)
                        ->where('branch_id', $branch->id)
                        ->first();

                    // Si existe el box, verificamos si tiene BoxClose diario
                    if ($existingBox) {
                        $existingBoxClose = BoxClose::where('box_id', $existingBox->id)
                                                ->where('type', 'Diario')
                                                ->first();

                        if ($existingBoxClose) {
                            DB::commit(); // Confirmamos la transacción vacía
                            Log::info("Sucursal {$branch->name} ya tiene BoxClose diario para {$yesterday}");
                            continue; // Saltamos al siguiente ciclo
                        }
                    }

                    // Si llegamos aquí, proseguimos con la lógica normal
                    $box = $existingBox ?: new Box();
                    
                    if (!$existingBox) {
                        $box->existence = 0;
                        $box->data = $yesterday;
                        $box->branch_id = $branch->id;
                        $box->save();
                    }

                    $boxClose = BoxClose::where('box_id', $box->id)->where('type', 'Diario')->first();
                    if (!$boxClose) {
                        $boxClose = new BoxClose(); // Crear uno nuevo si no existe
                    }

                    $totalAmount = ProfessionalPayment::where('branch_id', $branch->id)
                        ->whereDate('date', $yesterday)
                        ->where(function ($query) {
                            $query->where('type', 'Bono convivencias')
                                ->orWhere('type', 'Bono servicios');
                        })
                        ->sum('amount');

                    $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                        $query->where('branch_id', $branch->id);
                    })->whereHas('charge', function ($query) {
                        $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                    })->select('id', 'name', 'image_url', 'retention')->get();

                    $bonus = [];
                    foreach ($professionals as $professional) {
                        $bonus[] = $this->metaService->store_box_close($branch, $yesterday, $professional->id);
                    }

                    $bonusCollection = collect($bonus);
                    $flattenedBonus = $bonusCollection->flatMap(function ($item) {
                        return collect($item);
                    });

                    $totalBonus = $flattenedBonus->sum('amount');

                    if ($totalBonus) {
                        $difference = $totalBonus - $totalAmount;
                        $box->existence -= $difference;
                        $box->save();
                    }

                    $payments = Payment::where('branch_id', $branch->id)
                        ->whereDate('created_at', $yesterday)
                        ->get();

                    $cars_id = $payments->pluck('car_id');
                    $cars = Car::whereIn('id', $cars_id)->get();
                    $orders = Order::whereIn('car_id', $cars_id)->get();
                    $cashiers = CashierSale::where('branch_id', $branch->id)
                        ->whereDate('data', $yesterday)
                        ->get();

                    $services = $orders->where('is_product', 0)->sum('price');
                    $products = $orders->where('is_product', 1)->sum('price') + $cashiers->sum('price');

                    $totalMount = $services + $products + $cars->sum('tip');

                    // Guardar los datos en BoxClose
                    $boxClose->box_id = $box->id;
                    $boxClose->totalMount = $totalMount;
                    $boxClose->totalService = $services;
                    $boxClose->totalProduct = $products;
                    $boxClose->totalTip = $cars->sum('tip');
                    $boxClose->totalCash = $payments->sum('cash');
                    $boxClose->totalCreditCard = $payments->sum('creditCard');
                    $boxClose->totalDebit = $payments->sum('debit');
                    $boxClose->totalTransfer = $payments->sum('transfer');
                    $boxClose->totalOther = $payments->sum('other');
                    $boxClose->totalCardGif = $payments->sum('cardGif');
                    $boxClose->data = $yesterday;
                    $boxClose->save();

                    DB::commit();

                    $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
                    $reporte = $pdf->output(); // Convertir el PDF en una cadena
                    $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                        $query->where('name', 'Administrador')
                            ->orWhere('name', 'Encargado')
                            ->orWhere('name', 'Administrador de Sucursal')
                            ->orWhere('name', 'Coordinador');
                    })->whereHas('branches', function ($query) use ($branch) {
                        $query->where('branches.id', $branch->id);
                    })->pluck('email');
                    $emailassociated = $branch->associates()->pluck('email');
                    $emailArray = $emailassociated->toArray();
                    $mergedEmails = $emails->merge($emailArray);
                    foreach ($mergedEmails as $email) {
                        try {
                            $this->sendEmailService->emailBoxClosure(
                                $email,
                                $reporte,
                                $branch->business['name'],
                                $branch['name'],
                                $box['data'],
                                $box['cashFound'],
                                $box['existence'],
                                $box['extraction'],
                                $cars->sum('tip'),
                                $products,
                                $services,
                                $payments->sum('cash'),
                                $payments->sum('creditCard'),
                                $payments->sum('debit'),
                                $payments->sum('transfer'),
                                $payments->sum('other'),
                                $totalMount,
                                $payments->sum('cardGif'),
                                $totalBonus
                            );
                        } catch (\Swift_TransportException $e) {
                            Log::error("Error al enviar correo a $email: " . $e->getMessage());
                        } catch (\Exception $e) {
                            Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                        }
                    }
                } catch (\Throwable $th) {
                    DB::rollBack();
                    Log::error("Error al procesar la sucursal {$branch->name}: " . $th->getMessage());
                }
            }

            return response()->json(['msg' => 'Cierre de caja realizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error("Error general en el cierre de caja: " . $th->getMessage());
            return response()->json(['msg' => 'Error interno del servidor'], 500);
        }
    }
}
