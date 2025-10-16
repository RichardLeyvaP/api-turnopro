<?php

namespace App\Services;

use App\Models\{
    Professional,
    Branch,
    BranchProfessional,
    CashierSale,
    Order,
    Trace,
    Car,
    Advance,
    Finance,
    OperationTip,
    ProfessionalPayment,
    Retention,
    WorkerPurchase
};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfessionalPaymentService
{
    public function calculatePayments(array $data): array
    {
        // Obtener información básica
        $professional = Professional::find($data['professional_id']);
        $branch = Branch::find($data['branch_id']);
        $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->first();

        // Valores base
        $retention = $professional->retention ?? 0;
        $salary = $branchProfessional->salary ?? 0;

        // 1. Cálculo de comisiones por productos
        $commissionResult = $this->calculateProductCommissions($data, $branchProfessional, $professional);
        
        // 2. Cálculo de propinas
        $tipsResult = $this->calculateTips($data, $branch, $professional);
        
        // 3. Cálculo de adelantos
        $advancesResult = $this->calculateAdvances($data);

        // 3. Cálculo de compra de productos
        $workerPurchaseResult = $this->getWorkerPurchases($data);
  
        // 3. Cálculo de prestacion de servicios
        $carsResult = $this->getServiceEarnings($data, $retention);

        $existingPayments = $this->getExistingPayments($data);

        $salaryData = [
            'salary_bruto' => round($salary, 2),
            'retention_salary' => round($salary * ($retention / 100), 2),
            'salary_neto' => round($salary - ($salary * ($retention / 100)), 2),
            'already_paid' => $existingPayments > 0 // Flag para saber si ya tiene pagos
        ];


        // Cálculo del total neto
        $totalNeto = $commissionResult['commission_neto'] 
                   + $tipsResult['tip_neto'] 
                   + $carsResult['total_neto']
                   + ($existingPayments > 0 ? 0 : $salaryData['salary_bruto']) // Aquí está el cambio
                   - $workerPurchaseResult['total_purchases'] 
                   - $advancesResult['total_advance'];
        // Cálculo del total neto
        $totalNetoPay = $carsResult['total_neto']
                   + ($existingPayments > 0 ? 0 : $salaryData['salary_bruto']) // Aquí está el cambio
                   - $workerPurchaseResult['total_purchases'] 
                   - $advancesResult['total_advance'];

        return [
            'success' => true,
            'products' => $commissionResult,
            'tips' => $tipsResult,
            'advances' => $advancesResult,
            'totalNeto' => round($totalNeto, 2),
            'totalNetoPay' => round($totalNetoPay, 2),
            'workerPurchases' => $workerPurchaseResult,
            'cars' => $carsResult,
            'salary' => $salaryData,

        ];
    }

    protected function getExistingPayments(array $data): float
    {
        // Obtener el primer y último día del mes actual
        $firstDayOfMonth = now()->firstOfMonth()->format('Y-m-d');
        $lastDayOfMonth = now()->lastOfMonth()->format('Y-m-d');

        // Suma de pagos en ProfessionalPayment (Bono productos + Mes)
        $professionalPayments = ProfessionalPayment::where('professional_id', $data['professional_id'])
            ->where('branch_id', $data['branch_id'])
            ->whereIn('type', ['Bono productos', 'Mes'])
            ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
            ->sum('amount');

        // Suma de propinas en OperationTip
        $tips = OperationTip::where('professional_id', $data['professional_id'])
            ->where('branch_id', $data['branch_id'])
            ->where('type', 'Pago Comision de Propinas')
            ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
            ->sum('amount');

        return $professionalPayments + $tips;
    }

    protected function calculateProductCommissions(array $data, $branchProfessional, $professional): array
    {
        // Configuración de tiers (igual que antes)
        $tiers = [
            [
                'name' => 'tier1',
                'min' => (int)$branchProfessional->tier1_min_sales,
                'max' => (int)$branchProfessional->tier2_min_sales - 1,
                'rate' => (float)$branchProfessional->tier1_commission_rate
            ],
            [
                'name' => 'tier2',
                'min' => (int)$branchProfessional->tier2_min_sales,
                'max' => (int)$branchProfessional->tier3_min_sales - 1,
                'rate' => (float)$branchProfessional->tier2_commission_rate
            ],
            [
                'name' => 'tier3',
                'min' => (int)$branchProfessional->tier3_min_sales,
                'max' => null,
                'rate' => (float)$branchProfessional->tier3_commission_rate
            ]
        ];

        usort($tiers, fn($a, $b) => $a['min'] <=> $b['min']);

        $startDate = isset($data['startDate']) 
            ? Carbon::parse($data['startDate'])->startOfDay()
            : Carbon::now()->startOfMonth();
        
        $endDate = isset($data['endDate']) 
            ? Carbon::parse($data['endDate'])->endOfDay()
            : Carbon::now()->endOfMonth();

        // Obtener transacciones
        $allSales = CashierSale::with([
            'productStore' => function ($query) {
                // Si ProductStore usa soft deletes, inclúyelos
                $query->withTrashed();
            },
            'productStore.product' => function ($query) {
                // Incluir productos eliminados lógicamente
                $query->withTrashed();
            }
            ])
            ->where('professional_id', $data['professional_id'])
            ->where('branch_id', $data['branch_id'])
            ->where('pay', 1)
            ->where('paycashier', 0)
            ->whereBetween('data', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        $allOrders = Order::with([
            'productStore' => function ($query) {
                $query->withTrashed();
            },
            'productStore.product' => function ($query) {
                $query->withTrashed();
            },
            'car'
			])
            ->where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->whereHas('car', fn($q) => $q->where('pay', 1))
            ->where('is_product', 1)
            ->where('paycashier', 0)
            ->whereBetween('data', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        // Combinar TODAS las transacciones (sin filtrar por comisión)
        $allTransactions = $allSales->concat($allOrders)->sortBy('created_at');

        // Cálculo de productos vendidos (TODOS)
        $totalProductsSold = $allTransactions->sum('cant');

        $totalCommission = 0;
        $accumulatedProducts = 0;
        $commissionDetails = [];
        $nonCommissionProductsTotal = 0; // Contador de productos sin comisión

        foreach ($allTransactions as $transaction) {
            $productsInTransaction = (int)$transaction->cant;
            $transactionCommission = 0;
            $nonCommissionProducts = 0;
            $hasCommission = !is_null($transaction->commission_amount) && $transaction->commission_amount != 0;
            
            $transactionDetails = [
                'transaction_id' => $transaction->id,
                'type' => $transaction instanceof CashierSale ? 'sale' : 'order',
                'total_products' => $productsInTransaction,
                'has_commission' => $hasCommission,
                'total_commission' => $hasCommission ? (float)$transaction->commission_amount : 0,
                'tiers_applied' => [],
                'non_commission_products' => 0
            ];

            // Solo procesar comisión si la transacción tiene comisión
            if ($hasCommission) {
                // Procesar cada producto individualmente
                for ($i = 1; $i <= $productsInTransaction; $i++) {
                    $currentProductNumber = $accumulatedProducts + 1;
                    
                    // Determinar si el producto actual genera comisión
                    if ($currentProductNumber < $tiers[0]['min']) {
                        $nonCommissionProducts++;
                        $accumulatedProducts++;
                        continue;
                    }

                    // Obtener el tier actual para este producto
                    $currentTier = $this->getTierForProduct($tiers, $currentProductNumber);
                    
                    if (!$currentTier) {
                        $accumulatedProducts++;
                        continue;
                    }

                    // Calcular comisión para este producto individual
                    $productCommission = ($transaction->commission_amount / $productsInTransaction) * ($currentTier['rate'] / 100);
                    $transactionCommission += $productCommission;
                    $accumulatedProducts++;

                    // Agrupar por tier para el reporte
                    $tierKey = $currentTier['name'];
                    $foundTier = false;
                    
                    foreach ($transactionDetails['tiers_applied'] as &$appliedTier) {
                        if ($appliedTier['tier_name'] === $tierKey) {
                            $appliedTier['products']++;
                            $appliedTier['commission'] += $productCommission;
                            $foundTier = true;
                            break;
                        }
                    }
                    
                    if (!$foundTier) {
                        $transactionDetails['tiers_applied'][] = [
                            'tier_name' => $currentTier['name'],
                            'products' => 1,
                            'rate' => $currentTier['rate'],
                            'commission' => $productCommission,
                            'accumulated_products' => $accumulatedProducts
                        ];
                    }
                }
            } else {
                // Si no tiene comisión, simplemente contamos los productos
                $nonCommissionProducts = $productsInTransaction;
            }
            
            $transactionDetails['non_commission_products'] = $nonCommissionProducts;
            $nonCommissionProductsTotal += $nonCommissionProducts;
            $totalCommission += $transactionCommission;
            $commissionDetails[] = $transactionDetails;
        }

        $retentionRate = $professional->retention ?? 0;
        $retentionAmount = $totalCommission * ($retentionRate / 100);
        $commissionAfterRetention = $totalCommission - $retentionAmount;

        return [
            'total_products_sold' => $totalProductsSold,
            'total_commission' => round($totalCommission, 2),
            'retention_amount' => round($retentionAmount, 2),
            'commission_neto' => round($commissionAfterRetention, 2),
            'commission_details' => $commissionDetails,
            'sales_ids' => $allSales->pluck('id')->toArray(),
            'order_ids' => $allOrders->pluck('id')->toArray(),
            'commission_sales_ids' => $allSales->pluck('id')->toArray(),
            'commission_order_ids' => $allOrders->pluck('id')->toArray()
        ];
    }

    protected function calculateTips(array $data, $branch, $professional): array
    {

        $tipIds = Trace::where('branch', $branch->name)
            ->where('cashier', $professional->name)
            ->where('operation', 'Paga Carro')
            ->get('details')
            ->map(function($trace) {
                if (preg_match('/Carro:\s*(\d+)/', $trace->details, $matches)) {
                    return (int)$matches[1];
                }
                return null;
            })
            ->filter()
            ->values()
            ->toArray();

        $totalTipBruto = Car::whereIn('id', $tipIds)
            ->where('operation_tip_id', null)
            ->where('pay', 1)
            ->where('tip', '>', 0)
            ->whereHas('reservation', fn($q) => $q->where('branch_id', $data['branch_id']))
            ->sum('tip');

        $totalTipCashierBruto = $totalTipBruto * 0.10;
        //$retentionAmount = $totalTipCashierBruto * ($professional->retention / 100);
        //$tipAfterRetention = $totalTipCashierBruto - $retentionAmount;

        return [
            'total_tip' => round($totalTipBruto, 2),
            'tip_neto' => round($totalTipCashierBruto, 2),
            'tip_ids' => $tipIds
        ];
    }

    public function calculateAdvances(array $data): array
    {
        $advancesQuery = Advance::where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->whereIn('status', ['Pagado'])
            ->whereNull('discount_date');
        return [
            'total_advance' => round($advancesQuery->sum('amount'), 2),
            'advance_ids' => $advancesQuery->pluck('id')->toArray()
        ];
    }


    public function calculateTipsDetails(array $data, $branch, $professional): array
    {
        // Convertir fechas a objetos Carbon
        $startDate = isset($data['startDate']) 
            ? \Carbon\Carbon::parse($data['startDate'])->startOfDay()
            : now()->startOfMonth();
            
        $endDate = isset($data['endDate']) 
            ? \Carbon\Carbon::parse($data['endDate'])->endOfDay()
            : now()->endOfMonth();

        // 1. Obtener IDs de carros desde las trazas
        $tipIds = Trace::where('branch', $branch->name)
            ->where('cashier', $professional->name)
            ->where('operation', 'Paga Carro')
            ->whereBetween('data', [$startDate, $endDate])
            ->get('details')
            ->map(fn($trace) => preg_match('/Carro:\s*(\d+)/', $trace->details, $matches) ? (int)$matches[1] : null)
            ->filter()
            ->values()
            ->toArray();

        if (empty($tipIds)) {
            return [
                'total_tip' => 0,
                'tip_neto' => 0,
                'tip_ids' => [],
                'cars' => [],
            ];
        }

        // 2. Consulta única optimizada
        $cars = Car::whereIn('id', $tipIds)
            ->where('pay', 1)
            ->where('tip', '>', 0)
            ->whereHas('reservation', function($q) use ($data, $startDate, $endDate) {
                $q->where('branch_id', $data['branch_id'])
                ->whereBetween('data', [$startDate, $endDate]);
            })
            ->with(['reservation', 'clientProfessional.client', 'clientProfessional.professional'])
            ->get();

        // 3. Procesamiento de resultados
        $processedCars = $cars->map(function ($car) {
            $tipCashier = $car->tip * 0.10;
            $tipCoffe = $car->tip * 0.10;
            $professional = $car->clientProfessional->professional;
            $client = $car->clientProfessional->client;

            return [
                'id' => $car->id,
                'professional_id' => $professional->id,
                'clientName' => $client->name,
                'client_image' => $client->client_image ?: 'comments/default.jpg',
                'professionalName' => $professional->name,
                'image_url' => $professional->image_url,
                'branch_id' => $car->reservation->branch_id,
                'data' => $car->reservation->data,
                'tip' => round($car->tip, 2),
                'tipCashier' => round($tipCashier, 2),
                'tipCoffe' => round($tipCoffe, 2),
                'created_at' => $car->created_at->format('Y-m-d H:i:s')
            ];
        });

        // 4. Calcular totales
        $totalTipBruto = $cars->sum('tip');
        $totalTipCashierBruto = $totalTipBruto * 0.10;

        return [
            'total_tip' => round($totalTipBruto, 2),
            'tip_neto' => round($totalTipCashierBruto, 2),
            'tip_ids' => $tipIds,
            'cars' => $processedCars,
        ];
    }

    public function calculateTipsLastMoth(array $data, $branch, $professional): array
    {
        // Obtener el rango de fechas del mes anterior
        $previousMonthStart = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        // 1. Obtener IDs de carros desde las trazas del mes anterior
        $tipIds = Trace::where('branch', $branch->name)
            ->where('cashier', $professional->name)
            ->where('operation', 'Paga Carro')
            ->whereBetween('data', [$previousMonthStart, $previousMonthEnd])
            ->get('details')
            ->map(fn($trace) => preg_match('/Carro:\s*(\d+)/', $trace->details, $matches) ? (int)$matches[1] : null)
            ->filter()
            ->values()
            ->toArray();

        if (empty($tipIds)) {
            return [
                'total_tip' => 0,
                'tip_neto' => 0,
                'tip_ids' => [],
               ];
        }

        // 2. Consulta única optimizada que obtiene los datos y calcula los totales
        $cars = Car::whereIn('id', $tipIds)
            ->where('pay', 1)
            ->where('tip', '>', 0)
            ->whereHas('reservation', function($q) use ($data, $previousMonthStart, $previousMonthEnd) {
                $q->where('branch_id', $data['branch_id'])
                ->where('data', '>=', $previousMonthStart->format('Y-m-d'))
                ->where('data', '<=', $previousMonthEnd->format('Y-m-d'));
            })
            ->with(['reservation', 'clientProfessional.client', 'clientProfessional.professional'])
            ->get();
        // 4. Calcular totales a partir de los datos ya obtenidos
        $totalTipBruto = $cars->sum('tip');
        $totalTipCashierBruto = $totalTipBruto * 0.10;

        return [
            'total_tip' => round($totalTipBruto, 2),
            'tip_neto' => round($totalTipCashierBruto, 2),
            'tip_ids' => $tipIds,
        ];
    }

    public function calculateAdvancesDetails(array $data): array
    {
        $referenceDate = now();
        // Calcular períodos basados en la misma referencia
        $currentMonthStart = $referenceDate->copy()->startOfMonth();
        $previousMonthStart = $referenceDate->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $referenceDate->copy()->subMonth()->endOfMonth();

        // Consulta base reusable
        $baseQuery = function ($query) use ($data) {
            return $query->where('branch_id', $data['branch_id'])
                ->where('professional_id', $data['professional_id'])
                ->whereIn('status', ['Pagado']);
        };

        // Adelantos del mes actual
        $currentMonthAdvances = $baseQuery(clone Advance::query())
            ->where('data', '>=', $currentMonthStart)
            ->get();

        // Adelantos del mes anterior
        $previousMonthAdvances = $baseQuery(clone Advance::query())
            ->whereBetween('data', [$previousMonthStart, $previousMonthEnd])
            ->get();

        return [
            'current_month' => [
                'total' => $currentMonthAdvances->sum('amount'),
                'advances' => $currentMonthAdvances,
                'advance_ids' => $currentMonthAdvances->pluck('id')->toArray()
            ],
            'previous_month' => [
                'total' => $previousMonthAdvances->sum('amount'),
                'advances' => $previousMonthAdvances,
                'advance_ids' => $previousMonthAdvances->pluck('id')->toArray()
            ],
        ];
    }
 
    protected function getCurrentTier(array $tiers, int $totalProductsSold, int $remainingProducts): ?array
    {
        foreach ($tiers as $tier) {
            if ($totalProductsSold < $tier['min']) {
                if (($totalProductsSold + $remainingProducts) >= $tier['min']) {
                    return $tier;
                }
                continue;
            }
            
            if ($tier['max'] !== null && $totalProductsSold > $tier['max']) {
                continue;
            }
            
            return $tier;
        }
        
        return null;
    }

    protected function calculateProductsInTier(array $tier, int $totalProductsSold, int $remainingProducts): int
    {
        // Si estamos antes del mínimo del tier
        if ($totalProductsSold < $tier['min']) {
            $neededToReachTier = $tier['min'] - $totalProductsSold;
            
            // Si no alcanzamos el mínimo con los productos restantes
            if ($remainingProducts < $neededToReachTier) {
                return 0;
            }
            
            // Calculamos cuántos productos caen en este tier
            $productsInTier = $remainingProducts - $neededToReachTier + 1;
            return min($remainingProducts, $productsInTier);
        }
        
        // Si estamos dentro del tier
        if ($tier['max'] === null) {
            return $remainingProducts;
        } else {
            $availableInTier = $tier['max'] - $totalProductsSold + 1;
            return min($remainingProducts, $availableInTier);
        }
    }

    public function calculateProductCommissionsWithDetails(array $data, $branchProfessional, $professional): array
    {
        // Configuración de tiers (se mantiene igual)
        $tiers = [
            [
                'name' => 'tier1',
                'min' => (int)$branchProfessional->tier1_min_sales,
                'max' => (int)$branchProfessional->tier2_min_sales - 1,
                'rate' => (float)$branchProfessional->tier1_commission_rate
            ],
            [
                'name' => 'tier2',
                'min' => (int)$branchProfessional->tier2_min_sales,
                'max' => (int)$branchProfessional->tier3_min_sales - 1,
                'rate' => (float)$branchProfessional->tier2_commission_rate
            ],
            [
                'name' => 'tier3',
                'min' => (int)$branchProfessional->tier3_min_sales,
                'max' => null,
                'rate' => (float)$branchProfessional->tier3_commission_rate
            ]
        ];

        usort($tiers, fn($a, $b) => $a['min'] <=> $b['min']);

        // Determinar rango de fechas
        $startDate = isset($data['startDate']) 
            ? Carbon::parse($data['startDate'])->startOfDay()
            : Carbon::now()->startOfMonth();
        
        $endDate = isset($data['endDate']) 
            ? Carbon::parse($data['endDate'])->endOfDay()
            : Carbon::now()->endOfMonth();

        // Obtener transacciones
        $allSales = CashierSale::with([
            'productStore' => function ($query) {
                // Si ProductStore usa soft deletes, inclúyelos
                $query->withTrashed();
            },
            'productStore.product' => function ($query) {
                // Incluir productos eliminados lógicamente
                $query->withTrashed();
            }
            ])
            ->where('professional_id', $data['professional_id'])
            ->where('branch_id', $data['branch_id'])
            ->where('pay', 1)
            ->whereBetween('data', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        $allOrders = Order::with([
            'productStore' => function ($query) {
                $query->withTrashed();
            },
            'productStore.product' => function ($query) {
                $query->withTrashed();
            },
            'car'
			])
            ->where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->whereHas('car', fn($q) => $q->where('pay', 1))
            ->where('is_product', 1)
            ->whereBetween('data', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();
        // Nuevo cálculo por producto individual
        $transactionsWithCommissions = [];
        $accumulatedProducts = 0;
        $totalCommission = 0;
        $totalProducts = 0; // Contador para todos los productos

        // Usamos todas las transacciones, no solo las con comisión
        foreach ($allSales->concat($allOrders)->sortBy('created_at') as $transaction) {
            $productsInTransaction = (int)$transaction->cant;
            $transactionCommission = 0;
            $tiersApplied = [];
            $nonCommissionProducts = 0;
            $hasCommission = $transaction->commission_amount && $transaction->commission_amount != 0;

            // Procesar cada producto individualmente
            for ($i = 1; $i <= $productsInTransaction; $i++) {
                $currentProductNumber = $accumulatedProducts + 1;
                $totalProducts++; // Contamos todos los productos
                
                // Solo procesamos comisión si la transacción tiene comisión
                if ($hasCommission) {
                    // Determinar si el producto actual genera comisión
                    if ($currentProductNumber < $tiers[0]['min']) {
                        $nonCommissionProducts++;
                        $accumulatedProducts++;
                        continue;
                    }

                    // Obtener el tier actual para este producto
                    $currentTier = $this->getTierForProduct($tiers, $currentProductNumber);
                    
                    if (!$currentTier) {
                        $accumulatedProducts++;
                        continue;
                    }

                    // Calcular comisión para este producto individual
                    $productCommission = ($transaction->commission_amount / $productsInTransaction) * ($currentTier['rate'] / 100);
                    $transactionCommission += $productCommission;
                    $accumulatedProducts++;

                    // Agrupar por tier para el reporte
                    $tierKey = $currentTier['name'];
                    if (!isset($tiersApplied[$tierKey])) {
                        $tiersApplied[$tierKey] = [
                            'tier_name' => $currentTier['name'],
                            'products' => 0,
                            'rate' => $currentTier['rate'],
                            'commission' => 0,
                            'commission_neto' => 0
                        ];
                    }
                    
                    $tiersApplied[$tierKey]['products']++;
                    $tiersApplied[$tierKey]['commission'] += $productCommission;
                } else {
                    $nonCommissionProducts++;
                }
            }
            
            $transactionsWithCommissions[$transaction->id] = [
                'total_commission' => $transactionCommission,
                'tiers_applied' => array_values($tiersApplied),
                'non_commission_products' => $nonCommissionProducts,
                'total_products' => $productsInTransaction // Guardamos el total de productos en esta transacción
            ];
            $totalCommission += $transactionCommission;
        }

        // Aplicar retención a cada transacción y tier
        $retentionRate = $professional->retention ?? 0;
        
        foreach ($transactionsWithCommissions as &$transaction) {
            foreach ($transaction['tiers_applied'] as &$tier) {
                $tierRetention = $tier['commission'] * ($retentionRate / 100);
                $tier['commission_neto'] = $tier['commission'] - $tierRetention;
                $tier['retention_amount'] = $tierRetention;
            }
            unset($tier); // Romper la referencia
            
            $transaction['total_commission_neto'] = array_sum(array_column($transaction['tiers_applied'], 'commission_neto'));
        }
        unset($transaction); // Romper la referencia

        // Mapear todos los productos incluyendo la comisión neta
        $mappedProducts = collect();

        foreach ($allSales as $sale) {
            $this->mapProductData($sale, $transactionsWithCommissions, $mappedProducts, 'cashier_sale');
        }

        foreach ($allOrders as $order) {
            $this->mapProductData($order, $transactionsWithCommissions, $mappedProducts, 'order');
        }

        // Agrupamiento por fecha y producto
        $groupedProducts = $mappedProducts->groupBy(['data', function ($item) {
            return $item['name'];
        }])->map(function ($dateGroup) {
            return $dateGroup->map(function ($productGroup) {
                return $this->combineProductGroup($productGroup);
            });
        });
        
        // Reorganizar la estructura
        $finalProducts = collect();
        foreach ($groupedProducts as $date => $products) {
            foreach ($products as $product) {
                $finalProducts->push($product);
            }
        }
        
        // Ordenar por fecha
        $finalProducts = $finalProducts->sortBy('data')->values();

        // Calcular totales globales
        $totalCommissionBruto = $finalProducts->sum('professional_commission_bruto');
        $totalRetention = $finalProducts->sum('retention_amount');
        $totalCommissionNeto = $finalProducts->sum('professional_commission');

        return [
            'total_products_sold' => $mappedProducts->sum('cant'),
            'total_commission' => round($totalCommissionBruto, 2),
            'retention_amount' => round($totalRetention, 2),
            'commission_neto' => round($totalCommissionNeto, 2),
            'products_sold' => $finalProducts,
            'sales_ids' => $allSales->pluck('id')->toArray(),
            'order_ids' => $allOrders->pluck('id')->toArray(),
            'commission_sales_ids' => $allSales->pluck('id')->toArray(),
            'commission_order_ids' => $allOrders->pluck('id')->toArray()
        ];
    }

    // Métodos auxiliares

    protected function getTierForProduct($tiers, $productNumber)
    {
        foreach ($tiers as $tier) {
            if ($productNumber < $tier['min']) {
                continue;
            }

            if ($tier['max'] === null || $productNumber <= $tier['max']) {
                return $tier;
            }
        }

        return null;
    }

    protected function mapProductData($item, $transactionsWithCommissions, &$mappedProducts, $type)
    {
        $productName = 'Producto no disponible';
        $productImage = 'products/default.jpg';
        
        if ($item->productStore && $item->productStore->product) {
            $productName = $item->productStore->product->name;
            $productImage = $item->productStore->product->image_product ?? $productImage;
        }
        
        $commissionInfo = $transactionsWithCommissions[$item->id] ?? null;
        $professionalCommissionNet = $commissionInfo['total_commission_neto'] ?? 0;
        $professionalCommissionBruto = $commissionInfo ? array_sum(array_column($commissionInfo['tiers_applied'], 'commission')) : 0;
        $retentionAmount = $professionalCommissionBruto - $professionalCommissionNet;
        
        $mappedProducts->push([
            'id' => $item->id,
            'price' => round($item->price, 2),
            'pay' => $type === 'order' ? ($item->car->pay ?? 0) : $item->pay,
            'cant' => $item->cant,
            'name' => $productName,
            'image_product' => $productImage,
            'type' => $type,
            'has_commission' => !is_null($item->commission_amount) && $item->commission_amount != 0,
            'commission_amount' => $item->commission_amount ? round($item->commission_amount, 2) : 0,
            'professional_commission' => round($professionalCommissionNet, 2),
            'professional_commission_bruto' => round($professionalCommissionBruto, 2),
            'retention_amount' => round($retentionAmount, 2),
            'commission_per_unit' => $item->cant > 0 ? round($professionalCommissionNet / $item->cant, 2) : 0,
            'commission_tiers' => $commissionInfo['tiers_applied'] ?? [],
            'created_at' => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
            'data' => Carbon::parse($item->data)->format('Y-m-d')
        ]);
    }

    protected function combineProductGroup($productGroup)
    {
        $firstProduct = $productGroup->first();
        
        $combinedData = [
            'id' => $firstProduct['id'],
            'price' => $productGroup->sum('price'),
            'pay' => $firstProduct['pay'],
            'cant' => $productGroup->sum('cant'),
            'name' => $firstProduct['name'],
            'image_product' => $firstProduct['image_product'],
            'type' => $firstProduct['type'],
            'has_commission' => $firstProduct['has_commission'],
            'commission_amount' => round($productGroup->sum('commission_amount'), 2),
            'professional_commission' => round($productGroup->sum('professional_commission'), 2),
            'professional_commission_bruto' => round($productGroup->sum('professional_commission_bruto'), 2),
            'retention_amount' => round($productGroup->sum('retention_amount'), 2),
            'commission_per_unit' => $productGroup->sum('cant') > 0 
                ? round($productGroup->sum('professional_commission') / $productGroup->sum('cant'), 2) 
                : 0,
            'created_at' => $firstProduct['created_at'],
            'data' => $firstProduct['data']
        ];

        // Combinar tiers
        $combinedTiers = [];
        foreach ($productGroup as $product) {
            foreach ($product['commission_tiers'] as $tier) {
                $found = false;
                foreach ($combinedTiers as &$combinedTier) {
                    if ($combinedTier['tier_name'] === $tier['tier_name']) {
                        $combinedTier['products'] += $tier['products'];
                        $combinedTier['commission'] += $tier['commission'];
                        $combinedTier['commission_neto'] += $tier['commission_neto'];
                        $combinedTier['retention_amount'] += $tier['retention_amount'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $combinedTiers[] = $tier;
                }
            }
        }
        
        $combinedData['commission_tiers'] = $combinedTiers;

        return $combinedData;
    }

    public function calculateProductCommissionsNopay(array $data, $branchProfessional, $professional): array
    {
        // Configuración de tiers (se mantiene igual)
        $tiers = [
            [
                'name' => 'tier1',
                'min' => (int)$branchProfessional->tier1_min_sales,
                'max' => (int)$branchProfessional->tier2_min_sales - 1,
                'rate' => (float)$branchProfessional->tier1_commission_rate
            ],
            [
                'name' => 'tier2',
                'min' => (int)$branchProfessional->tier2_min_sales,
                'max' => (int)$branchProfessional->tier3_min_sales - 1,
                'rate' => (float)$branchProfessional->tier2_commission_rate
            ],
            [
                'name' => 'tier3',
                'min' => (int)$branchProfessional->tier3_min_sales,
                'max' => null,
                'rate' => (float)$branchProfessional->tier3_commission_rate
            ]
        ];

        usort($tiers, fn($a, $b) => $a['min'] <=> $b['min']);

        // Determinar rango de fechas
        $startDate = isset($data['startDate']) 
            ? Carbon::parse($data['startDate'])->startOfDay()
            : Carbon::now()->startOfMonth();
        
        $endDate = isset($data['endDate']) 
            ? Carbon::parse($data['endDate'])->endOfDay()
            : Carbon::now()->endOfMonth();

        // Obtener transacciones
        $allSales = CashierSale::with([
            'productStore' => function ($query) {
                // Si ProductStore usa soft deletes, inclúyelos
                $query->withTrashed();
            },
            'productStore.product' => function ($query) {
                // Incluir productos eliminados lógicamente
                $query->withTrashed();
            }
            ])
            ->where('professional_id', $data['professional_id'])
            ->where('branch_id', $data['branch_id'])
            ->where('pay', 1)
            ->where('paycashier', 0)
            ->whereBetween('data', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        $allOrders = Order::with([
            'productStore' => function ($query) {
                $query->withTrashed();
            },
            'productStore.product' => function ($query) {
                $query->withTrashed();
            },
            'car'
			])
            ->where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->whereHas('car', fn($q) => $q->where('pay', 1))
            ->where('is_product', 1)
            ->where('paycashier', 0)
            ->whereBetween('data', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

       // Obtener TODAS las transacciones sin filtrar por comisión
        $allTransactions = $allSales->concat($allOrders)->sortBy('created_at');

        // Variables para el cálculo
        $transactionsWithCommissions = [];
        $accumulatedProducts = 0;
        $totalCommission = 0;
        $totalProductsSold = 0; // Contador para todos los productos vendidos

        foreach ($allTransactions as $transaction) {
            $productsInTransaction = (int)$transaction->cant;
            $transactionCommission = 0;
            $tiersApplied = [];
            $nonCommissionProducts = 0;
            $hasCommission = !is_null($transaction->commission_amount) && $transaction->commission_amount != 0;

            // Procesar cada producto individualmente
            for ($i = 1; $i <= $productsInTransaction; $i++) {
                $currentProductNumber = $accumulatedProducts + 1;
                $totalProductsSold++; // Contamos TODOS los productos

                // Solo procesamos comisión si la transacción tiene comisión
                if ($hasCommission) {
                    // Determinar si el producto actual genera comisión
                    if ($currentProductNumber < $tiers[0]['min']) {
                        $nonCommissionProducts++;
                        $accumulatedProducts++;
                        continue;
                    }

                    // Obtener el tier actual para este producto
                    $currentTier = $this->getTierForProduct($tiers, $currentProductNumber);
                    
                    if (!$currentTier) {
                        $accumulatedProducts++;
                        continue;
                    }

                    // Calcular comisión para este producto individual
                    $productCommission = ($transaction->commission_amount / $productsInTransaction) * ($currentTier['rate'] / 100);
                    $transactionCommission += $productCommission;
                    $accumulatedProducts++;

                    // Agrupar por tier para el reporte
                    $tierKey = $currentTier['name'];
                    if (!isset($tiersApplied[$tierKey])) {
                        $tiersApplied[$tierKey] = [
                            'tier_name' => $currentTier['name'],
                            'products' => 0,
                            'rate' => $currentTier['rate'],
                            'commission' => 0,
                            'commission_neto' => 0
                        ];
                    }
                    
                    $tiersApplied[$tierKey]['products']++;
                    $tiersApplied[$tierKey]['commission'] += $productCommission;
                } else {
                    $nonCommissionProducts++;
                }
            }
            
            $transactionsWithCommissions[$transaction->id] = [
                'total_commission' => $transactionCommission,
                'tiers_applied' => array_values($tiersApplied),
                'non_commission_products' => $nonCommissionProducts,
                'total_products' => $productsInTransaction, // Total de productos en esta transacción
                'has_commission' => $hasCommission // Indica si la transacción tenía comisión
            ];
            
            $totalCommission += $transactionCommission;
        }


        // Aplicar retención a cada transacción y tier
        $retentionRate = $professional->retention ?? 0;
        
        foreach ($transactionsWithCommissions as &$transaction) {
            foreach ($transaction['tiers_applied'] as &$tier) {
                $tierRetention = $tier['commission'] * ($retentionRate / 100);
                $tier['commission_neto'] = $tier['commission'] - $tierRetention;
                $tier['retention_amount'] = $tierRetention;
            }
            unset($tier); // Romper la referencia
            
            $transaction['total_commission_neto'] = array_sum(array_column($transaction['tiers_applied'], 'commission_neto'));
        }
        unset($transaction); // Romper la referencia

        // Mapear todos los productos incluyendo la comisión neta
        $mappedProducts = collect();

        foreach ($allSales as $sale) {
            $this->mapProductData($sale, $transactionsWithCommissions, $mappedProducts, 'cashier_sale');
        }

        foreach ($allOrders as $order) {
            $this->mapProductData($order, $transactionsWithCommissions, $mappedProducts, 'order');
        }

        // Agrupamiento por fecha y producto
        $groupedProducts = $mappedProducts->groupBy(['data', function ($item) {
            return $item['name'];
        }])->map(function ($dateGroup) {
            return $dateGroup->map(function ($productGroup) {
                return $this->combineProductGroup($productGroup);
            });
        });
        
        // Reorganizar la estructura
        $finalProducts = collect();
        foreach ($groupedProducts as $date => $products) {
            foreach ($products as $product) {
                $finalProducts->push($product);
            }
        }
        
        // Ordenar por fecha
        $finalProducts = $finalProducts->sortBy('data')->values();

        // Calcular totales globales
        $totalCommissionBruto = $finalProducts->sum('professional_commission_bruto');
        $totalRetention = $finalProducts->sum('retention_amount');
        $totalCommissionNeto = $finalProducts->sum('professional_commission');

        return [
            'total_products_sold' => $mappedProducts->sum('cant'),
            'total_commission' => round($totalCommissionBruto, 2),
            'retention_amount' => round($totalRetention, 2),
            'commission_neto' => round($totalCommissionNeto, 2),
            'products_sold' => $finalProducts,
            'sales_ids' => $allSales->pluck('id')->toArray(),
            'order_ids' => $allOrders->pluck('id')->toArray(),
            'commission_sales_ids' => $allSales->pluck('id')->toArray(),
            'commission_order_ids' => $allOrders->pluck('id')->toArray()
        ];
    }

    public function getWorkerPurchases(array $data): array
    {
        $purchasesQuery = WorkerPurchase::where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->where('status', 1) // Status 1 indica compras aprobadas/pagadas
            ->whereNull('discount_date'); // Solo compras no descontadas aún

        $totalPurchases = $purchasesQuery->sum('total');
        $purchaseIds = $purchasesQuery->pluck('id')->toArray();
        return [
            'total_purchases' => round($totalPurchases, 2),
            'purchase_ids' => $purchaseIds,
        ];
    }

    public function getServiceEarnings(array $data, ?float $retention = null): array
    {
        // Obtener carros no pagados aún
        $cars = Car::with(['reservation', 'clientProfessional'])
            ->where('professional_payment_id', null)
            ->whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->whereHas('clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })
            ->where('pay', 1)
            ->get();

        $carIds = $cars->pluck('id')->toArray();

        // Calcular propinas (80% para el profesional)
        $tips = $cars->sum('tip');
        $totalTips = $tips * 0.80;

        // Calcular ganancias por servicios
        $orders = Order::whereIn('car_id', $carIds)
            ->where('is_product', 0)
            ->get();

        $totalServicesBruto = $orders->sum('percent_win');
        
        // Aplicar retención si existe
        $retentionAmount = $retention ? ($totalServicesBruto * $retention / 100) : 0;
        $totalServicesNeto = $totalServicesBruto - $retentionAmount;
        return [
            'total_tips' => round($totalTips, 2),
            'tips' => round($tips, 2),
            'total_services_bruto' => round($totalServicesBruto, 2),
            'total_services_neto' => round($totalServicesNeto, 2),
            'retention_amount' => round($retentionAmount, 2),
            'car_ids' => $carIds,
            'total_combined' => round($totalServicesBruto + $tips, 2),
            'total_neto' => round($totalServicesNeto + $totalTips, 2),
        ];
    }

    public function getServiceEarningsDetails(array $data, ?float $retention = null): array
    {
        // Obtener carros no pagados aún con todas las relaciones necesarias
        $cars = Car::with([
                'reservation', 
                'clientProfessional.client',
            ])
            ->where('professional_payment_id', null)
            ->whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->whereHas('clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })
            ->where('pay', 1)
            ->get();

        $carIds = $cars->pluck('id')->toArray();

        // Calcular propinas (80% para el profesional)
        $tips = $cars->sum('tip');
        $totalTips = $tips * 0.80;

        // Calcular ganancias por servicios
        $orders = Order::whereIn('car_id', $carIds)
            ->where('is_product', 0)
            ->get();

        $totalServicesBruto = $orders->sum('percent_win');
        
        // Aplicar retención si existe
        $retentionAmount = $retention ? ($totalServicesBruto * $retention / 100) : 0;
        $totalServicesNeto = $totalServicesBruto - $retentionAmount;

        // Mapear los datos detallados de cada carro
        $detailedCars = $cars->map(function ($car) use ($retention) {
            $serviceOrders = $car->orders->where('is_product', 0);
            $productOrders = Order::where('car_id', $car->id)
                                ->where('is_product', 1)
                                ->get();
            
            $metaCounterServ = $serviceOrders->sum(function ($order) {
                return $order->meta == 1 ? 1 : 0;
            });

            $client = $car->clientProfessional->client;
            $carRetention = $retention ? ($serviceOrders->sum('percent_win') * $retention) / 100 : 0;
            $amountServ = $serviceOrders->sum('percent_win');

            return [
                'id' => $car->id,
                'professional_id' => $car->clientProfessional->professional_id,
                'clientName' => $client->name . ' ' . $client->surname,
                'client_image' => $client->client_image ? $client->client_image . '?$' . now() : 'comments/default.jpg',
                'branch_id' => $car->reservation->branch_id,
                'data' => $car->reservation->data,
                'attendedClient' => 1,
                'services' => $serviceOrders->count(),
                'products' => $productOrders->sum('cant'),
                'totalServices' => round(($amountServ - $carRetention), 2),
                'clientAleator' => $car->select_professional,
                'amountGenerate' => round($car->amount, 2),
                'tip' => $car->tip * 0.80,
                'meta' => ($serviceOrders->count() == 1 && $amountServ == 0) ? 'Si' : 'No',
                'metaService' => $metaCounterServ > 0 ? 'SI' : 'NO',
                'selectable' => ($serviceOrders->count() == 1 && $amountServ == 0 && ($car->tip * 0.80) <= 0) ? false : true
            ];
        })->sortBy('data')->values();
        return [
            'tips' => round($tips, 2),
            'total_tips' => round($totalTips, 2),
            'total_services_bruto' => round($totalServicesBruto, 2),
            'total_services_neto' => round($totalServicesNeto, 2),
            'retention_amount' => round($retentionAmount, 2),
            'car_ids' => $carIds,
            'total_combined' => round($totalServicesBruto + $tips, 2),
            'total_neto' => round($totalServicesNeto + $totalTips, 2),
            'detailed_cars' => $detailedCars
        ];
    }

    public function processPayment(array $data)
    {
        $data['paymentDate'] = isset($data['paymentDate']) 
        ? Carbon::parse($data['paymentDate']) 
        : Carbon::now();
        return DB::transaction(function () use ($data) {
            $professional = Professional::findOrFail($data['professional_id']);
            
            $this->processWorkerPurchases($data);
            $this->processAdvances($data);
            
            if ($this->hasProductCommissions($data)) {
                $this->processProductCommissions($data, $professional);
            }
            
            if ($this->hasTips($data)) {
                $this->processTips($data, $professional);
            }
            
            if ($this->hasSalaryPayment($data)) {
                $this->processSalaryPayment($data, $professional);
            }

            if($this->hasCarPayments($data)) {
                $this->processCarPayments($data, $professional);
            }
            
            return $data;
        });
    }

    protected function processWorkerPurchases($data)
    {
        if (!empty($data['payments']['workerPurchases']['purchase_ids'])) {
            WorkerPurchase::whereIn('id', $data['payments']['workerPurchases']['purchase_ids'])
                        ->update(['discount_date' => $data['paymentDate']]);
        }
    }

    protected function processAdvances($data)
    {
        if (!empty($data['payments']['advances']['advance_ids'])) {
            Advance::whereIn('id', $data['payments']['advances']['advance_ids'])
                        ->update(['discount_date' => $data['paymentDate']]);
        }
    }

    protected function hasProductCommissions($data)
    {
        $products = $data['payments']['products'];
        return $products['commission_neto'] > 0 || $products['retention_amount'] > 0;
    }

    protected function processProductCommissions($data, $professional)
    {
         $payments = $data['payments'];
        $productsData = $payments['products'];

        // Registrar retención (si existe)
        if ($productsData['retention_amount'] > 0) {
            $retention = new Retention();
            $retention->branch_id = $data['branch_id'];
            $retention->professional_id = $data['professional_id'];
            $retention->data = $data['paymentDate'];
            $retention->retention = $productsData['retention_amount'];
            $retention->type = 'Products';
            $retention->save();
        }

        // Registrar pago y movimiento financiero solo si hay comisión neta
        if ($productsData['commission_neto'] > 0) {
        // 1. Registrar pago al profesional
        $professionalPayment = new ProfessionalPayment();
        $professionalPayment->branch_id = $data['branch_id'];
        $professionalPayment->professional_id = $data['professional_id'];
        $professionalPayment->date = $data['paymentDate'];
        $professionalPayment->amount = $productsData['commission_neto'];
        $professionalPayment->type = 'Bono productos';
        $professionalPayment->cant = $productsData['total_products_sold'];
        $professionalPayment->save();

        // 3. Registrar movimiento financiero
        $finance = Finance::orderBy('control', 'desc')->first();         
        $control = $finance ? $finance->control + 1 : 1;

        $finance = new Finance();
        $finance->control = $control;
        $finance->operation = 'Gasto';
        $finance->amount = $productsData['commission_neto'];
        $finance->comment = 'Gasto por pago de bono de productos a ' . $professional->name;
        $finance->branch_id = $data['branch_id'];
        $finance->type = 'Sucursal';
        $finance->expense_id = 5;
        $finance->data = $data['paymentDate'];
        $finance->professional_payment_id = $professionalPayment->id;
        $finance->file = '';
        $finance->save();

        // 4. Ajustar totalNeto
        $data['payments']['totalNeto'] -= $productsData['commission_neto'];
        }
        // 2. Actualizar sales/orders asociadas
        if (!empty($productsData['sales_ids'])) {
            CashierSale::whereIn('id', $productsData['sales_ids'])
                ->update(['paycashier' => $professionalPayment->id]);
        }

        if (!empty($productsData['order_ids'])) {
            Order::whereIn('id', $productsData['order_ids'])
                ->update(['paycashier' => $professionalPayment->id]);
        }
    }

    protected function hasTips($data)
    {
        return $data['payments']['tips']['tip_neto'] > 0;
    }

    protected function processTips($data, $professional)
    {
        $operationTip = new OperationTip();
        $operationTip->branch_id = $data['branch_id'];
        $operationTip->professional_id = $data['professional_id'];
        $operationTip->date = $data['paymentDate'];
        $operationTip->amount = $data['payments']['tips']['tip_neto'];
        $operationTip->type = 'Pago Comision de Propinas';
        $operationTip->coffe_percent = $data['payments']['tips']['tip_neto'];
        $operationTip->save();

        if (!empty($data['payments']['tips']['tip_ids'])) {
            Car::whereIn('id', $data['payments']['tips']['tip_ids'])
                ->update(['operation_tip_id' => $operationTip->id]);
        }

        $finance = Finance::orderBy('control', 'desc')->first();
                    
        if($finance !== null) {
            $control = $finance->control+1;
        } else {
            $control = 1;
        }
        
        $finance = new Finance();
        $finance->control = $control++;
        $finance->operation = 'Gasto';
        $finance->amount = $data['payments']['tips']['tip_neto'];
        $finance->comment = 'Gasto por pago de 10% de propinas a cajero (a) '.$professional->name;
        $finance->branch_id = $data['branch_id'];
        $finance->type = 'Sucursal';
        $finance->expense_id = 4;
        $finance->data = $data['paymentDate']; 
        $finance->operation_tip_id = $operationTip->id;               
        $finance->file = '';
        $finance->save();
        
        $data['payments']['totalNeto'] -= $data['payments']['tips']['tip_neto'];
    }
    protected function hasSalaryPayment($data)
    {
        return $data['payments']['totalNetoPay'] > 0 && $data['payments']['salary']['salary_bruto'] > 0;
    }
    protected function processSalaryPayment($data, $professional)
    {
        $professionalPaymentSalary = new ProfessionalPayment();
        $professionalPaymentSalary->branch_id = $data['branch_id'];
        $professionalPaymentSalary->professional_id = $data['professional_id'];
        $professionalPaymentSalary->date = $data['paymentDate'];
        $professionalPaymentSalary->amount = $data['payments']['totalNetoPay'];
        $professionalPaymentSalary->type = 'Mes';
        $professionalPaymentSalary->save();

        $finance = Finance::orderBy('control', 'desc')->first();
                    
        if($finance !== null) {
            $control = $finance->control+1;
        } else {
            $control = 1;
        }
        
        $finance = new Finance();
        $finance->control = $control;
        $finance->operation = 'Gasto';
        $finance->amount = $data['payments']['totalNetoPay'];
        $finance->comment = 'Gasto por pago a '.$professional->name;
        $finance->branch_id = $data['branch_id'];
        $finance->type = 'Sucursal';
        $finance->expense_id = 4;
        $finance->data = $data['paymentDate'];                
        $finance->file = '';
        $finance->professional_payment_id = $professionalPaymentSalary->id;               
        $finance->save();
    }

    protected function hasCarPayments($data)
    {
        return $data['payments']['cars']['total_neto'] > 0 && $data['payments']['totalNetoPay'] > 0;
    }

    protected function processCarPayments($data, $professional)
    {
        $professionalPaymentBarbero = new ProfessionalPayment();
        $professionalPaymentBarbero->branch_id = $data['branch_id'];
        $professionalPaymentBarbero->professional_id = $data['professional_id'];
        $professionalPaymentBarbero->date = $data['paymentDate'];
        $professionalPaymentBarbero->amount = $data['payments']['totalNetoPay'];
        $professionalPaymentBarbero->type = "Mes";
        $professionalPaymentBarbero->save();

        if (!empty($data['payments']['cars']['car_ids'])) {
            Car::whereIn('id', $data['payments']['cars']['car_ids'])
                ->update(['professional_payment_id' => $professionalPaymentBarbero->id]);
        }

        $finance = Finance::orderBy('control', 'desc')->first();         
        if($finance !== null) {
            $control = $finance->control+1;
        } else {
            $control = 1;
        }
        
        $finance = new Finance();
        $finance->control = $control;
        $finance->operation = 'Gasto';
        $finance->amount = $data['payments']['totalNetoPay'];
        $finance->comment = 'Gasto por pago a '.$professional->name;
        $finance->branch_id = $data['branch_id'];
        $finance->type = 'Sucursal';
        $finance->expense_id = 4;
        $finance->data = $data['paymentDate'];                
        $finance->file = '';
        $finance->professional_payment_id = $professionalPaymentBarbero->id;
        $finance->save();
    }
}