<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\map;

class ProfessionalService
{
    public function store($data)
    {
        $professional = new Professional();
        $professional->name = $data['name'];
        $professional->surname = $data['surname'];
        $professional->second_surname = $data['second_surname'];
        $professional->email = $data['email'];
        $professional->phone = $data['phone'];
        $professional->charge_id = $data['charge_id'];
        $professional->user_id = $data['user_id'];
        $professional->image_url = $data['image_url'];
        $professional->state = 0;
        $professional->save();
        return $professional;
    }

    public function professionals_branch($branch_id, $professional_id)
    {
        $professionals = Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->find($professional_id);

        $dataUser = [];
        if ($professionals) {
            $date = Carbon::now();
            $dataUser['id'] = $professionals->id;
            $dataUser['usuario'] = $professionals->name;
            $dataUser['fecha'] = $date->toDateString();
            $dataUser['hora'] = $date->Format('g:i:s A');
        }

        return $dataUser;
    }

    public function branch_professionals($branch_id)
    {
        return $professionals = Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->get();
    }

    public function verifi_tec_prof($email, $branch_id)
    {
        $professionals = Professional::where('email', $email)->whereHas('branches', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->first();
        Log::info($professionals);
        if ($professionals) {
            if( ($professionals->charge_id == 1) || ($professionals->charge_id == 7) || ($professionals->charge_id == 3) || ($professionals->charge_id == 12))
            {
                if ($professionals->charge_id == 1) {//charge_id == 1
                    $type = 2;
                    $name = $professionals->name . ' ' . $professionals->surname . ' ' . $professionals->second_surname;
                    $professional_id = $professionals->id;
                }
                if ($professionals->charge_id == 7) {//charge_id == 7
                    $type = 1;
                    $name = $professionals->name . ' ' . $professionals->surname . ' ' . $professionals->second_surname;
                    $professional_id = $professionals->id;
                }
                if ($professionals->charge_id == 3 || $professionals->charge_id == 12) {//charge_id != 1 && charge_id != 7
                    $type = 0;
                    $name = $professionals->name . ' ' . $professionals->surname . ' ' . $professionals->second_surname;
                    $professional_id = $professionals->id;
                }
                return [
                    'name' => $name,
                    'type' => $type,
                    'professional_id' => $professional_id
                ];

            }
            else {
                return [
                    'name' => '',
                    'type' => 0,
                    'professional_id' => 0
                ];
            }
            
           
        } else {
            return [
                'name' => '',
                'type' => 0,
                'professional_id' => 0
            ];
        }
    }

    /*public function branch_professionals_service($branch_id, $services)
    {
        $totaltime = Service::whereIn('id', $services)->get()->sum('duration_service');
        Log::info($totaltime);
        //return $branchServId = BranchService::whereIn('service_id', $services)->get()->pluck('id');
        $professionals = Professional::whereHas('branches', function ($query) use ($branch_id, $services) {

            $query->where('branch_id', $branch_id);
        })->whereHas('branchServices', function ($query) use ($services) {
            $query->whereIn('service_id', $services);
        }, '=', count($services))->whereHas('charge', function ($query) {
            $query->where('id', 1);
        })->get();
        Log::info($professionals);
        return $professionals;
    }*/

    /*public function encontrarHoraDisponible($timeService, $horaActual, $arrayHoras) {
        // Convertir la hora actual a un objeto Carbon para facilitar la comparación
        $horaActualCarbon = Carbon::createFromFormat('H:i', $horaActual);
    
        foreach ($arrayHoras as $hora) {
            Log::info('$horaActualCarbon');
            Log::info($horaActualCarbon);
            Log::info('$hora');
            Log::info($hora);
            // Convertir la hora del array a un objeto Carbon para comparar
            $horaCarbon = Carbon::createFromFormat('H:i', $hora);
            
            // Verificar si la hora del array es mayor que la hora actual
            if ($horaCarbon->gt($horaActualCarbon)) {
                // Calcular la diferencia entre la hora del array y la hora actual
                $diferenciaMinutos = $horaCarbon->diffInMinutes($horaActualCarbon);
                Log::info('$diferenciaMinutos');
                Log::info($diferenciaMinutos);
                // Verificar si la diferencia de tiempo es mayor o igual al tiempo de servicio
                if ($diferenciaMinutos >= ($timeService)) {
                    return $horaActualCarbon->format('H:i'); // Devolver la hora actual
                } else {
                    // Actualizar la hora actual y continuar el bucle
                    $horaActualCarbon = $horaCarbon;
                }
            }
        }
    
        // Si no se encuentra ninguna hora disponible, devolver la última hora del array
        return $arrayHoras[count($arrayHoras) - 1];
    }*/

    public function branch_professionals_service($branch_id, $services)
    {
        // Ejemplo de uso
        //$timeService = 20;
        //$horaActual = '15:00';
        //$arrayHoras = ['10:25', '10:30', '11:00', '11:15', '11:30','14:30'];
        $totalTiempo = Service::whereIn('id', $services)->get()->sum('duration_service');
        $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $start_time = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
        $closingTime = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('closing_time');
        $current_date = Carbon::now()->format('Y-m-d');
        $availableProfessionals = [];
        //return Carbon::now()->addMinutes($totalTiempo);
        if(Carbon::now()->addMinutes($totalTiempo) >  Carbon::parse($closingTime)){
            return $availableProfessionals = [];
        }
        else{
        $professionals = Professional::whereHas('branchServices', function ($query) use ($services, $branch_id) {
            $query->whereIn('service_id', $services)->where('branch_id', $branch_id);
        })->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero');
        })->get();

        foreach ($professionals as $professional) {
            $reservations = $professional->reservations()
            ->whereHas('car.orders.branchServiceProfessional.branchService', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })
            ->whereDate('data', $current_date)
            ->get()->map(function ($query){
                return [
                    'start_time' => $query->start_time,
                    'final_hour' => $query->final_hour
                ];
            });
            // Decodificar la entrada JSON a un array de objetos
            $entrada = json_decode($reservations, true);
            //return $entrada[0];
            if($reservations->isEmpty()){
                if(Carbon::now() < Carbon::parse($start_time)){
                    $professional->start_time = Carbon::parse($start_time)->format('H:i');
                $availableProfessionals[] = $professional;
                }
                else{
                $professional->start_time = date('H:i');
                $availableProfessionals[] = $professional;
            }
            }else{

                //$arrayHoras = $this->professional_reservations_time1($branch_id, $professional->id, $current_date);
                //return $arrayHoras;
                $professional->start_time = $this->encontrarHoraDisponible($totalTiempo, Carbon::now()->format('H:i'), $entrada);
                $availableProfessionals[] = $professional;
                //break;
            }//else
        }//for
    }//else
        //return $availableProfessionals;
        $returnedProfessionals = [];

            foreach ($availableProfessionals as $professional) {
                $time = strtotime($professional->start_time);
                if ($time + ($totalTiempo * 60) <= strtotime($closingTime)) {
                    // Si el tiempo final es menor o igual al horario de cierre, agregar al profesional a la lista de devolución
                    $returnedProfessionals[] = $professional;
                }
            }

            return $returnedProfessionals;
              
    }


    ///nuevo metodo
    function encontrarHoraDisponible($timeService, $horaActual, $arrayIntervalos) {
        // Convertir la hora actual a un objeto Carbon para facilitar la comparación
        $horaActualCarbon = Carbon::createFromFormat('H:i', $horaActual);
    
        foreach ($arrayIntervalos as $key => $intervalo) {
            $horaInicioActual = Carbon::createFromFormat('H:i:s', $intervalo['start_time']);
            $horaFinActual = Carbon::createFromFormat('H:i:s', $intervalo['final_hour']);
    
            // Si la hora actual está dentro del intervalo, continuamos al siguiente intervalo
            if ($horaActualCarbon->between($horaInicioActual, $horaFinActual)) {
                continue;
            }
    
            // Si la hora actual es anterior al inicio del intervalo actual,
            // devolvemos la hora de inicio del intervalo actual
            if ($horaActualCarbon->lt($horaInicioActual)) {
                return $horaActual;
            }
    
            // Si la hora actual es posterior al final del intervalo actual y 
            // no hay más intervalos después, devolvemos la última hora del intervalo actual
            if ($horaActualCarbon->gt($horaFinActual) && !isset($arrayIntervalos[$key + 1])) {
                return $horaActual;
            }
    
            // Si hay intervalos posteriores, verificamos si el tiempo de servicio es menor que
            // la diferencia entre el inicio del siguiente intervalo y el final del intervalo actual
            if (isset($arrayIntervalos[$key + 1])) {
                $horaInicioSiguiente = Carbon::createFromFormat('H:i:s', $arrayIntervalos[$key + 1]['start_time']);
                $diferenciaMinutos = $horaInicioSiguiente->diffInMinutes($horaFinActual);
                if ($timeService <= $diferenciaMinutos) {
                    return $horaInicioActual->format('H:i');
                }
            }
        }
    
        // Si no se encuentra ninguna hora disponible,
        // devolvemos la última hora del último intervalo
        return end($arrayIntervalos)['final_hour'];
    }
    
    // Ejemplo de uso
    /*$timeService = 20;
    $horaActual = '10:25';
    $arrayIntervalos = [
        ["start_time" => "09:00:00", "final_hour" => "10:40:00"],
        ["start_time" => "11:00:00", "final_hour" => "12:30:00"],
        ["start_time" => "14:00:00", "final_hour" => "15:30:00"]
    ];*/
    
    //$horaDisponible = encontrarHoraDisponible($timeService, $horaActual, $arrayIntervalos);
    //echo "La próxima hora disponible es: $horaDisponible";

    ///cierre nuevo metodo

    

    public function professional_reservations_time1($branch_id, $professional_id, $data)
    {
            $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
            $start_time = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
            //$startTime = strtotime($start_time);
            //Log::info('$startTime');
            //Log::info($startTime);
            $professional = Professional::where('id', $professional_id)
                ->whereHas('branches', function ($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })
                ->with(['reservations' => function ($query) use ($data) {
                    $query->whereDate('data', $data);
                }])
                ->first();

            $currentDateTime = Carbon::now();
            // Verificar si hay reservas para este profesional y día
            if (count($professional->reservations) > 0) {
                // Obtener las reservas y mapearlas para obtener los intervalos de tiempo
                $reservations = $professional->reservations->map(function ($reservation) {
                    $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                    $finalMinutes = Carbon::parse($reservation->final_hour)->minute;
                    Log::info('$finalMinutes');
                    Log::info($finalMinutes);
                    $intervalos = [$startFormatted];
                    $startTime = Carbon::parse($startFormatted);
                    $finalFormatted = Carbon::parse($reservation->final_hour)->format('H:i');
                    Log::info('$finalFormatted');
                    Log::info($finalFormatted);
                    $finalTime = Carbon::parse($finalFormatted);
                    Log::info('$finalTime');
                    Log::info($finalTime);
                    // Agregar las horas intermedias de 15 en 15 minutos
                    while ($startTime->addMinutes(15) <= $finalTime) {
                        $intervalos[] = $startTime->format('H:i');
                    }

                    return $intervalos;
                })->flatten()->values()->all();
                //return $reservations;
                if ($currentDateTime->isToday()) {
                    // Verificar si la hora actual es menor que el primer start_time de las reservas del día
                    $firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                    if ($currentDateTime->lessThan($firstReservationStartTime)) {
                        $startTime = Carbon::parse($start_time);
                        while ($startTime->addMinutes(15) <= $currentDateTime) {
                            $reservations[] = $startTime->format('H:i');
                        }
                    } else {
                        $startTime = Carbon::parse($start_time);
                        while ($startTime->addMinutes(15) <= $firstReservationStartTime) {
                            $reservations[] = $startTime->format('H:i');
                        }
                    }
                }
            } else {
                if ($currentDateTime->isToday()) {
                    // Verificar si la hora actual es menor que el primer start_time de las reservas del día
                    //$firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                    //if ($currentDateTime->lessThan($firstReservationStartTime)) {
                        $startTime = Carbon::parse($start_time);
                        while ($startTime->addMinutes(15) <= $currentDateTime) {
                            $reservations[] = $startTime->format('H:i');
                        }
                    //}
                }
                //$reservations = [];
            }
            sort($reservations);
            return $reservations;
        }

    public function branch_professionals_service1($branch_id, $services)
    {
                // Calcular el tiempo total del servicio
        $totalTiempo = Service::whereIn('id', $services)->get()->sum('duration_service');

        // Obtener el nombre del día en español
        $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));

        // Obtener la hora de cierre del establecimiento para el día actual
        $closingTime = strtotime(Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('closing_time'));

        // Obtener los profesionales que ofrecen los servicios seleccionados y son barberos
        $professionals = Professional::whereHas('branchServices', function ($query) use ($services, $branch_id) {
            $query->whereIn('service_id', $services)->where('branch_id', $branch_id);
        })->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero');
        })->get();

        $current_date = Carbon::now();

        $availableProfessionals = [];
        //return $current_date->format('Y-m-d H:i:s');
        // Verificar la disponibilidad de los profesionales
        foreach ($professionals as $professional) {
            $reservations = $professional->reservations()
                ->whereHas('car.orders.branchServiceProfessional.branchService', function ($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })
                ->whereDate('data', $current_date)
                ->where('start_time', '>=', $current_date->format('H:i'))
                ->orderBy('start_time')
                ->get();

            if ($reservations->isEmpty()) {
                // Si no hay reservas, agregar el profesional con la hora actual como tiempo de inicio
                $professional->start_time = $current_date->format('H:i');
                $availableProfessionals[] = $professional;
            } else {
                $firstValidReservation = null;
                
                    $count = count($reservations);
                    Log::info('$count = count($reservations)');
                    Log::info($count = count($reservations));
                    for ($i = 0; $i < $count -1; $i++) {
                        $startTime1 = strtotime($reservations[$i]->final_hour);
                        $startTime2 = strtotime($reservations[$i + 1]->start_time);
                    
                        $differenceInMinutes = ($startTime2 - $startTime1) / 60;
                    
                        if ($differenceInMinutes >=  ($totalTiempo * 60)) {
                            //$professional->start_time = $reservations[$i]->final_hour;
                            $firstValidReservation = $reservations[$i];
                            break; // Detener el bucle una vez que se encuentra la primera reserva válida
                        }
                    }
                    // Comparar el final_hour de la última reserva con el $closingtime
                        $lastReservationFinalHour = strtotime($reservations[$count - 1]->final_hour);
                        $closingTime = strtotime($closingTime);

                        if (($closingTime - $lastReservationFinalHour) >= ($totalTiempo * 60)) {
                            // La última reserva permite suficiente tiempo antes del cierre
                            $firstValidReservation = $reservations[$count - 1];
                        }
                    // Verificar si $firstValidReservation no es nulo antes de acceder a sus propiedades
                    if ($firstValidReservation !== null) {
                        $professional->start_time = $firstValidReservation->final_hour < date('H:i') ? $current_date->format('H:i') : $firstValidReservation->final_hour;
                        $availableProfessionals[] = $professional;
                            }
                    }//else
                }

            // Filtrar los profesionales por la hora de cierre
            $returnedProfessionals = [];

            foreach ($availableProfessionals as $professional) {
                $time = strtotime($professional->start_time);
                if ($time + ($totalTiempo * 60) <= $closingTime) {
                    // Si el tiempo final es menor o igual al horario de cierre, agregar al profesional a la lista de devolución
                    $returnedProfessionals[] = $professional;
                }
            }

            return $returnedProfessionals;
        /*$totaltime = Service::whereIn('id', $services)->get()->sum('duration_service');

        $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $closing_time = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('closing_time');
        $closingTime = strtotime($closing_time);
        Log::info($totaltime);
        //return $branchServId = BranchService::whereIn('service_id', $services)->get()->pluck('id');
        $professionals = Professional::where(function ($query) use ($services, $branch_id) {
            foreach ($services as $service) {
                $query->whereHas('branchServices', function ($q) use ($service, $branch_id) {
                    $q->where('service_id', $service)->where('branch_id', $branch_id);
                });
            }
        })->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero');
        })->get();
        Log::info($professionals);
        $current_date = Carbon::now();
        Log::info($current_date);
        $availableProfessionals = [];
        foreach ($professionals as $professional) {
            $reservations = $professional->reservations()->whereHas('car.orders.branchServiceProfessional.branchService', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })
                ->whereDate('data', $current_date)
                ->where('start_time', '>=', $current_date->format('Y-m-d H:i:s'))
                ->orderBy('start_time')
                ->get();

                if ($reservations->isEmpty()) {
                    // Si no hay reservas, agregar el profesional con hora actual como tiempo de inicio
                    $professional->start_time = $current_date->format('H:i:s');
                    $availableProfessionals[] = $professional;
                } else {
                    $previousReservationEndTime = null;

                foreach ($reservations as $reservation) {
                    $startTime = strtotime($reservation->start_time);
                    $finalHour = strtotime($reservation->final_hour);
                    $currentTime = time();
                    
                    // Comprobar si la reserva cumple con las condiciones
                    if ($previousReservationEndTime === null && (($startTime-$previousReservationEndTime) >= ($totaltime * 60))) {
                        $professional->start_time = $reservation->final_hour;
                        $availableProfessionals[] = $professional;
                        break;
                    }
                    $previousReservationEndTime = strtotime($reservation->final_hour);
                }
            }
        }
        //return $availableProfessionals;
        $returnedProfessionals = [];
        foreach ($availableProfessionals as $professional) {
            $time = strtotime($professional->start_time);
            if ($time<=$closingTime) {
                // Si el tiempo final es menor o igual al horario de cierre, agregar al profesional a la lista de devolución
                $returnedProfessionals[] = $professional;
            }
        }

        return $returnedProfessionals;*/
    }
    public function get_professionals_service($data)
    {
        return $professionals = Professional::whereHas('branchServices', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->where('service_id', $data['service_id']);
        })->select('id', 'name', 'surname', 'second_surname')->get();
    }

    public function professionals_ganancias($data)
    {
        $startDate = Carbon::parse($data['startDate']);
        $endDate = Carbon::parse($data['endDate']);
        $dates = [];
        $i = 0;
        $day = $data['day'] - 1; //en $day = 1 es Lunes,$day=2 es Martes...$day=7 es Domingo, esto e spara el front

        $cars = Car::whereHas('clientProfessional', function ($query) use ($data) {
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query) use ($data) {
            $query->whereBetween('data', [$data['startDate'], Carbon::parse($data['endDate'])->addDay()]);
        })->with('orders')->get()->map(function ($car) {
            return [
                'date' => $car->orders->value('data'),
                'earnings' => $car->amount
            ];
        });
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $machingResult = $cars->where('date', $date->toDateString())->sum('earnings');
            $dates[$i]['date'] = $date->toDateString();

            $day += 1;
            $dates[$i]['day_week'] = $day;
            if ($day == 7)
                $day = 0;

            $dates[$i++]['earnings'] = $machingResult ? $machingResult : 0;
        }
        $result = [
            'dates' => $dates,
            'totalEarnings' => $cars->sum('earnings'),
            'averageEarnings' => $cars->avg('earnings')
        ];
        return $result;
    }

    public function professionals_ganancias_branch_date($data)
    {
        Log::info('Obtener los cars');
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data) {
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query) {
            $query->whereDate('data', Carbon::now());
        })->get();
        $services = 0;
        $products = 0;
        $totalClients = 0;
        foreach ($cars as $car) {
            $services = $services + count($car->orders->where('is_product', 0));
            $products = $products + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional', function ($query) use ($data) {
            $query->whereHas('branchService', function ($query) use ($data) {
                $query->WhereHas('service', function ($query) {
                    $query->where('type_service', 'Especial');
                })->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id']);
        })->whereDate('data', Carbon::now())->get();
        $totalClients = $cars->count();
        return $result = [
            'Monto Generado' => round($cars->sum('amount'), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Propina 80%' => round($cars->sum('tip') * 0.8, 2),
            'Servicios Realizados' => $services,
            'Productos Vendidos' => $products,
            'Servicios Regulares' => $services - $orders->count(),
            'Servicios Especiales' => $orders->count(),
            'Monto Especial' => round($orders->sum('percent_win'), 2),
            'Ganancia Barbero 45%' => round($cars->sum('amount') * 0.45, 2),
            'Ganancia Total Barbero 45%' => round($cars->sum('amount') * 0.45 + $cars->sum('tip') * 0.8, 2),
            'Clientes Atendidos' => $totalClients,
            'Seleccionado' => $cars->where('select_professional', 1)->count(),
            'Aleatorio' => $cars->where('select_professional', 0)->count()
        ];
    }

    public function professionals_ganancias_branch_Periodo($data, $startDate, $endDate)
    {
        Log::info('Obtener los cars');
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data) {
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query) use ($startDate, $endDate) {
            $query->whereBetWeen('data', [$startDate, $endDate]);
        })->get();
        $services = 0;
        $products = 0;
        $totalClients = 0;
        foreach ($cars as $car) {
            $services = $services + count($car->orders->where('is_product', 0));
            $products = $products + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional', function ($query) use ($data) {
            $query->whereHas('branchService', function ($query) use ($data) {
                $query->WhereHas('service', function ($query) {
                    $query->where('type_service', 'Especial');
                })->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id']);
        })->whereBetWeen('data', [$startDate, $endDate])->get();
        $totalClients = $cars->count();
        return $result = [
            'Monto Generado' => round($cars->sum('amount'), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Propina 80%' => round($cars->sum('tip') * 0.8, 2),
            'Servicios Realizados' => $services,
            'Productos Vendidos' => $products,
            'Servicios Regulares' => $services - $orders->count(),
            'Servicios Especiales' => $orders->count(),
            'Monto Especial' => round($orders->sum('percent_win'), 2),
            'Ganancia Barbero  45%' => round($cars->sum('amount') * 0.45, 2),
            'Ganancia Total Barbero  45%' => round($cars->sum('amount') * 0.45 + $cars->sum('tip') * 0.8, 2),
            'Clientes Atendidos' => $totalClients,
            'Seleccionado' => $cars->where('select_professional', 1)->count(),
            'Aleatorio' => $cars->where('select_professional', 0)->count()
        ];
    }

    public function professionals_ganancias_branch_month($data, $mes, $year)
    {
        Log::info('Obtener los cars');
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data) {
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query) use ($mes, $year) {
            $query->whereMonth('data', $mes)->whereYear('data', $year);
        })->get();
        $services = 0;
        $products = 0;
        $totalClients = 0;
        foreach ($cars as $car) {
            $services = $services + count($car->orders->where('is_product', 0));
            $products = $products + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional', function ($query) use ($data) {
            $query->whereHas('branchService', function ($query) use ($data) {
                $query->WhereHas('service', function ($query) {
                    $query->where('type_service', 'Especial');
                })->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id']);
        })->whereMonth('data', $mes)->whereYear('data', $year)->get();
        Log::info($orders);
        $totalClients = $cars->count();
        return $result = [
            'Monto Generado' => round($cars->sum('amount'), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Propina 80%' => round($cars->sum('tip') * 0.8, 2),
            'Servicios Realizados' => $services,
            'Productos Vendidos' => $products,
            'Servicios Regulares' => $services - $orders->count(),
            'Servicios Especiales' => $orders->count(),
            'Monto Especial' => round($orders->sum('percent_win'), 2),
            'Ganancia Barbero 45%' => round($cars->sum('amount') * 0.45, 2),
            'Ganancia Total Barbero 45%' => round($cars->sum('amount') * 0.45 + $cars->sum('tip') * 0.8, 2),
            'Clientes Atendidos' => $totalClients,
            'Seleccionado' => $cars->where('select_professional', 1)->count(),
            'Aleatorio' => $cars->where('select_professional', 0)->count()
        ];
    }

    // public function professionals_state($branch_id)
    // {   Carbon::now()->format('H:i:s');
    //     $time = 20;
    //     $horaActual = Carbon::parse(Carbon::now()->format('H:i:s'))->addMinutes($time)->toTimeString();
    //     $professionals = Professional::whereHas('branches', function ($query) use ($branch_id){
    //         $query->where('branch_id', $branch_id);
    //        })->whereHas('tails', function ($query) use ($horaActual) {
    //         $query->whereHas('reservation', function ($query) use ($horaActual) {
    //             $query->where('start_time', '>=', $horaActual);
    //         })->whereIn('attended', [0,2,3]);
    //        })->get();

    //        return $professionals;
    // }
    public function professionals_state($branch_id)
    {
        $time = 20;
        $branchId = 1; // Reemplaza con el ID de la sucursal que estás buscando
        $currentTime = Carbon::now();
        $endTimeThreshold = $currentTime->copy()->addMinutes(20);

        $professionals = Professional::whereHas('branches', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->where(function ($query) use ($endTimeThreshold) {
            $query->orWhereDoesntHave('tails')
                ->orWhereHas('tails', function ($subquery) {
                    $subquery->whereIn('attended', [0, 2, 3]);
                })
                ->orWhereHas('tails', function ($subquery) use ($endTimeThreshold) {
                    $subquery->whereNotIn('attended', [0, 2, 3])
                        ->where('start_time', '>', $endTimeThreshold->format('H:i:s'))
                        ->orWhereNull('start_time');
                });
        })->get();

        // Convertir el campo telefono a string
        $professionals->map(function ($professional) {
            $professional->phone = (string)$professional->phone;
            return $professional;
        });

        return $professionals->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero');
        })->values();
    }
}
