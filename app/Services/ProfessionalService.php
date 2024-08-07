<?php

namespace App\Services;

use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Order;
use App\Models\Professional;
use App\Models\ProfessionalWorkPlace;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
            $dataUser['hora'] = $date->Format('g:i A');
        }

        return $dataUser;
    }

    public function branch_professionals($branch_id)
    {
        return $professionals = Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->get()->map(function ($query) {
            return [
                'id' => $query->id,
                'name' => $query->name,
                'surname' => $query->surname,
                'second_surname' => $query->second_surname,
                'email' => $query->email,
                'phone' => $query->phone,
                'created_at' => $query->created_at,
                'updated_at' => $query->updated_at,
                'charge_id' => $query->charge->name,
                'user_id' => $query->user_id,
                'state' => $query->state,
                'image_url' => $query->image_url,
                'business_id' => $query->business_id,
                'retention' => $query->retention
            ];
        });
    }

    public function verifi_tec_prof($email, $branch_id)
    {
        /*$professionals = Professional::where('email', $email)->whereHas('branches', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->first();*/
        $professionals = Professional::whereHas('user', function ($query) use ($email) {
            $query->where('name', $email);
        })->whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->first();
        Log::info($professionals);
        if ($professionals) {
            if (($professionals->charge->name == 'Barbero') || ($professionals->charge->name == 'Tecnico') || ($professionals->charge->name == 'Encargado') || ($professionals->charge->name == 'Coordinador')  || ($professionals->charge->name == 'Barbero y Encargado')) {
                if ($professionals->charge->name == 'Barbero') { //charge_id == 1
                    $type = 2;
                    $name = $professionals->name . ' ' . $professionals->surname . ' ' . $professionals->second_surname;
                    $professional_id = $professionals->id;
                }
                if ($professionals->charge->name == 'Tecnico') { //charge_id == 7
                    $type = 1;
                    $name = $professionals->name . ' ' . $professionals->surname . ' ' . $professionals->second_surname;
                    $professional_id = $professionals->id;
                }
                if ($professionals->charge->name == 'Barbero y Encargado') { //charge_id == 7
                    $type = 3;
                    $name = $professionals->name . ' ' . $professionals->surname . ' ' . $professionals->second_surname;
                    $professional_id = $professionals->id;
                }
                if ($professionals->charge->name == 'Encargado' || $professionals->charge->name == 'Coordinador') { //charge_id != 1 && charge_id != 7
                    $type = 0;
                    $name = $professionals->name . ' ' . $professionals->surname . ' ' . $professionals->second_surname;
                    $professional_id = $professionals->id;
                }
                return [
                    'name' => $name,
                    'type' => $type,
                    'professional_id' => $professional_id
                ];
            } else {
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
        $totalTiempo = Service::whereIn('id', $services)->get()->sum('duration_service');
        $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $startTime = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
        $closingTime = Carbon::now()->setTime(23, 59, 59)->format('H:i:s');/*Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('closing_time');*/
        $current_date = Carbon::now()->format('Y-m-d');
        $availableProfessionals = [];
        $fechaDada = Carbon::now()->format('Y-m-d');
        //return Carbon::now()->addMinutes($totalTiempo);
        if (Carbon::now()->addMinutes($totalTiempo) >  Carbon::parse($closingTime)) {
            return $availableProfessionals = [];
        } else {
            $professionals1 = Professional::whereHas('branchServices', function ($query) use ($services, $branch_id) {
                $query->whereIn('service_id', $services)->where('branch_id', $branch_id);
            }, '=', count($services))
                ->whereHas('charge', function ($query) {
                    $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                })
                ->whereIn('state', [1, 2])
                ->join('branch_professional', function ($join) use ($branch_id) {
                    $join->on('professionals.id', '=', 'branch_professional.professional_id')
                        ->where('branch_professional.branch_id', '=', $branch_id)
                        ->where('branch_professional.arrival', '!=', NULL);
                })
                ->select(
                    'professionals.id',
                    'professionals.name',
                    'professionals.surname',
                    'professionals.second_surname',
                    'professionals.email',
                    'professionals.phone',
                    'professionals.charge_id',
                    'professionals.state',
                    'professionals.start_time as colacion_time',
                    'professionals.image_url',
                    'branch_professional.arrival',
                    'branch_professional.living',
                    'branch_professional.numberRandom'
                )->orderBy('branch_professional.numberRandom', 'asc')
                ->orderBy('branch_professional.living', 'asc')
                ->orderBy('branch_professional.arrival', 'asc')
                ->get();
            foreach ($professionals1 as $professional1) {
                $vacation = Vacation::where('professional_id', $professional1->id)->whereDate('startDate', '<=', $fechaDada)
                    ->whereDate('endDate', '>=', $fechaDada)
                    ->first();
                Log::info($vacation);
                if (!$vacation) {
                    //Log::info();
                    $professionals[] = $professional1;
                }
            }
            $current_time = now()->format('H:i:s');
            foreach ($professionals1 as $professional) {
                $reservations = $professional->reservations()->where('branch_id', $branch_id)->whereIn('confirmation', [1, 4])
                    ->whereDate('data', $current_date)
                    /*->whereHas('tail', function ($subquery) {
                        $subquery->where('aleatorie', '!=', 1);
                    })*/
                    ->get()
                    ->sortBy('start_time')
                    ->map(function ($query) use ($current_time, $professional) {
                        $attended_values = [1, 11, 111, 4, 5, 33];
                        // Comprobación de start_time y attended
                        Log::info('Resevaciones');
                        Log::info($query);
                        if ($query->confirmation == 4 && $query->from_home == 1) {
                             Log::info('Msg-Este profesional no esta libre');
                            Log::info('Entrando a verificar el horario de la reserva que esta en atendiendose');
                            Log::info('Professional id' . $professional->id);
                            Log::info('Tiempoo inicio de la reserva' . $query->start_time);
                            Log::info('Tiempoo total de la reserva' . $query->total_time);
                            $start_time = $current_time;
                            $final_hour = date('H:i:s', strtotime($start_time) + strtotime($query->total_time) - strtotime('TODAY'));
                            Log::info('Tiempoo inicio de la reserva actualizado' . $start_time);
                            Log::info('Tiempoo final de la reserva' . $final_hour);
                            return [
                                'start_time' => $start_time,
                                'final_hour' => $final_hour
                            ];
                        }
                        return [
                            'start_time' => $query->start_time,
                            'final_hour' => $query->final_hour
                        ];
                    });
                Log::info('$reservations');
                Log::info($reservations);
                // Decodificar la entrada JSON a un array de objetos
                $entrada = json_decode($reservations, true);
                //return $entrada[0];
                if ($reservations->isEmpty()) {
                    if (Carbon::now() < Carbon::parse($startTime)) {
                        $professional->start_time = Carbon::parse($startTime)->format('H:i');
                        $availableProfessionals[] = $professional;
                    } else {
                        $professional->start_time = date('H:i');
                        $availableProfessionals[] = $professional;
                    }
                } else {
                    //$arrayHoras = $this->professional_reservations_time1($branch_id, $professional->id, $current_date);
                    //return $arrayHoras;
                    $professional->start_time = $this->encontrarHoraDisponible($totalTiempo, $entrada, $startTime);
                    $availableProfessionals[] = $professional;
                    //break;
                } //else
            } //for
        } //else
        //return $availableProfessionals;

        $returnedProfessionals = [];

        foreach ($availableProfessionals as $professional) {
            $time = strtotime($professional->start_time);
            if ($time + ($totalTiempo * 60) <= strtotime($closingTime)) {
                // Si el tiempo final es menor o igual al horario de cierre, agregar al profesional a la lista de devolución
                $returnedProfessionals[] = $professional;
            }
        }
        foreach ($returnedProfessionals as $professional) {
            if ($professional->state == 2) {
                if ($professional->colacion_time != NUll) {
                    //return 'Esta en colacion'.$professional->colacion_time;
                    Log::info('Esta en colacion'.$professional->colacion_time);
                    $colacion_time = Carbon::parse($professional->colacion_time)->addMinutes(60);
                if (Carbon::parse($professional->start_time) < $colacion_time){
                    $reservs = $professional->reservations()->where('branch_id', $branch_id)->whereIn('confirmation', [1, 4])
                    ->whereDate('data', $current_date)
                    ->where('start_time', '>', $colacion_time->format('H:i'))
                    ->orderBy('start_time')
                    /*->whereHas('tail', function ($subquery) {
                        $subquery->where('aleatorie', '!=', 1);
                    })*/
                    ->get();
                    $colacion_time1 = $colacion_time;
                    if($reservs->isNotEmpty()){
                        foreach($reservs as $reserv){
                            if(Carbon::parse($reserv->start_time) >= $colacion_time1->addMinutes($totalTiempo)){
                                break;
                            }else{
                                $colacion_time1 = Carbon::parse($reserv->final_hour);
                                $colacion_time = Carbon::parse($reserv->final_hour);
                            }
                        }
                    }
                    /*if($reserv != Null && Carbon::parse($reserv->start_time) >= $colacion_time->addMinutes($totalTiempo)){
                        $professional->start_time = Carbon::parse($professional->colacion_time)->addMinutes(60)->format('H:i');
                    } else {                        
                        $professional->start_time = Carbon::parse($reserv->final_hour)->format('H:i');
                    }*/
                    $professional->start_time = $colacion_time->format('H:i');
                }
                }
                
            }
        }

        unset($professional); // Romper la referencia

        // Ordenar por 'state' y luego por 'start_time'
        usort($returnedProfessionals, function ($a, $b) {
            // Primero comparar por 'state'
            if ($a->state != $b->state) {
                return $a->state - $b->state;
            }
            // Si 'state' es igual, comparar por 'start_time'
            return strtotime($a->start_time) - strtotime($b->start_time);
        });

        return $returnedProfessionals;
    }

    /*public function branch_professionals_service($branch_id, $services)
    {
        $totalTiempo = Service::whereIn('id', $services)->get()->sum('duration_service');
        $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $startTime = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
        $closingTime = Carbon::now()->setTime(23, 59, 59)->format('H:i:s');
        $current_date = Carbon::now()->format('Y-m-d');
        $availableProfessionals = [];
        $fechaDada = Carbon::now()->format('Y-m-d');
        //return Carbon::now()->addMinutes($totalTiempo);
        if (Carbon::now()->addMinutes($totalTiempo) >  Carbon::parse($closingTime)) {
            return $availableProfessionals = [];
        } else {
            $professionals1 = Professional::whereHas('branchServices', function ($query) use ($services, $branch_id) {
                $query->whereIn('service_id', $services)->where('branch_id', $branch_id);
            }, '=', count($services))
                ->whereHas('charge', function ($query) {
                    $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                })
                ->where('state', 1)
                ->join('branch_professional', function ($join) use ($branch_id) {
                    $join->on('professionals.id', '=', 'branch_professional.professional_id')
                        ->where('branch_professional.branch_id', '=', $branch_id)
                        ->where('branch_professional.arrival', '!=', NULL);
                })
                ->select(
                    'professionals.id',
                    'professionals.name',
                    'professionals.surname',
                    'professionals.second_surname',
                    'professionals.email',
                    'professionals.phone',
                    'professionals.charge_id',
                    'professionals.state',
                    'professionals.image_url',
                    'branch_professional.arrival',
                    'branch_professional.living'
                )->orderByRaw('branch_professional.living ASC')
                ->orderBy('branch_professional.arrival', 'asc')
                ->get();
            foreach ($professionals1 as $professional1) {
                $vacation = Vacation::where('professional_id', $professional1->id)->whereDate('startDate', '<=', $fechaDada)
                    ->whereDate('endDate', '>=', $fechaDada)
                    ->first();
                Log::info($vacation);
                if (!$vacation) {
                    //Log::info();
                    $professionals[] = $professional1;
                }
            }
            $current_time = now()->format('H:i:s');
            $duration = 0;
            $cantAleatorie = 0;
            $proAleatorie = 0;
            $reservationAleatorie = Reservation::where('branch_id', $branch_id)->whereHas('tail', function ($query){
                $query->where('aleatorie', 1);
            })->where('start_time', '<', Carbon::now()->format('H:i'))->get();
            if($reservationAleatorie){
                foreach($reservationAleatorie as $aleatorie){
                    $duration += $this->convertirHoraAMinutos($aleatorie->total_time);
                }
            }
            $cantAleatorie = $reservationAleatorie->count();
            $promAleatorie = $duration/$cantAleatorie;
            Log::info('Duracion de los servcios');
            Log::info($duration);
            Log::info('Promedio de aleatories services');
            Log::info($promAleatorie);
            foreach ($professionals1 as $professional) {
                $reservations = $professional->reservations()->where('branch_id', $branch_id)->whereIn('confirmation', [1, 4])
                    ->whereDate('data', $current_date)
                    ->whereHas('tail', function ($subquery) {
                        $subquery->where('aleatorie', '!=', 1);
                    })
                    ->get()
                    ->sortBy('start_time')
                    ->map(function ($query) use ($current_time, $professional, $promAleatorie) {
                        $attended_values = [1, 11, 111, 4, 5, 33];
                        // Comprobación de start_time y attended
                        Log::info('Resevaciones');
                        Log::info($query);
                        if ($query->confirmation == 4 && $query->from_home == 1) {
                             Log::info('Msg-Este profesional no esta libre');
                            Log::info('Entrando a verificar el horario de la reserva que esta en atendiendose');
                            Log::info('Pprofessional id' . $professional->id);
                            Log::info('Tiempoo inicio de la reserva' . $query->start_time);
                            Log::info('Tiempoo total de la reserva' . $query->total_time);
                            $start_time = Carbon::parse($current_time)->addMinutes($promAleatorie)->toTimeString();//$current_time;//Carbon::parse($current_time)->addMinutes($promAleatorie)->toTimeString()
                            $final_hour = date('H:i:s', strtotime($start_time) + strtotime($query->total_time) - strtotime('TODAY'));
                            Log::info('Tiempoo inicio de la reserva actualizado' . $start_time);
                            Log::info('Tiempoo final de la reserva' . $final_hour);
                            return [
                                'start_time' => $start_time,
                                'final_hour' => $final_hour
                            ];
                        }
                        return [
                            'start_time' => Carbon::parse($query->start_time)->addMinutes($promAleatorie)->toTimeString(),//$query->start_time,
                            'final_hour' => Carbon::parse($query->final_hour)->addMinutes($promAleatorie)->toTimeString()//$query->final_hour
                        ];
                    });
                Log::info('$reservations');
                Log::info($reservations);
                // Decodificar la entrada JSON a un array de objetos
                $entrada = json_decode($reservations, true);
                //return $entrada[0];
                if ($reservations->isEmpty()) {
                    if (Carbon::now() < Carbon::parse($startTime)) {
                        $timeOpen = Carbon::parse($startTime)->format('H:i');
                        $professional->start_time = Carbon::parse($timeOpen)->addMinutes($promAleatorie)->toTimeString();//Carbon::parse($startTime)->format('H:i');//
                        $availableProfessionals[] = $professional;
                    } else {
                        $timeOpen = date('H:i');
                        $professional->start_time = Carbon::parse($timeOpen)->addMinutes($promAleatorie)->toTimeString();//date('H:i');//
                        $availableProfessionals[] = $professional;
                    }
                } else {
                    //$arrayHoras = $this->professional_reservations_time1($branch_id, $professional->id, $current_date);
                    //return $arrayHoras;
                    $timeFound = $this->encontrarHoraDisponible($totalTiempo, $entrada, $startTime);
                    $professional->start_time = Carbon::parse($timeFound)->addMinutes($promAleatorie)->toTimeString();//$timeFound;//
                    $availableProfessionals[] = $professional;
                    //break;
                } //else
            } //for
        } //else
        //return $availableProfessionals;

        $returnedProfessionals = [];

        foreach ($availableProfessionals as $professional) {
            $time = strtotime($professional->start_time);
            if ($time + ($totalTiempo * 60) <= strtotime($closingTime)) {
                // Si el tiempo final es menor o igual al horario de cierre, agregar al profesional a la lista de devolución
                $returnedProfessionals[] = $professional;
            }
        }

        usort($returnedProfessionals, function ($a, $b) {
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        });

        return $returnedProfessionals;
    }*/


    ///nuevo metodo
    function encontrarHoraDisponible($timeService, $arrayIntervalos, $startTime)
    {
        // Convertir la hora actual a un objeto Carbon para facilitar la comparación
        //$horaActualCarbon = Carbon::createFromFormat('H:i', $horaActual);
        $horaActual = Carbon::now();

        if ($horaActual < Carbon::parse($startTime)) {
            $horaActual = Carbon::parse($startTime);
        }

        // Convertir la hora de inicio del primer intervalo a Carbon para comparar
        //$primerIntervaloInicio = Carbon::createFromFormat('H:i:s', $arrayIntervalos[0]['start_time']);

        // Si la hora actual es menor que la del primer intervalo, devuelve la hora final del último intervalo
        //if ($horaActualCarbon->lt($primerIntervaloInicio)) {
        //return end($arrayIntervalos)['final_hour'];
        //}
        //$auxActual = $horaActual->addMinutes($timeService);
        $i = 0;
        Log::info('$horaActual');
        Log::info($horaActual);
        foreach ($arrayIntervalos as $key => $intervalo) {
            $auxActual = Carbon::now();
            $horaInicioActual = Carbon::createFromFormat('H:i:s', $intervalo['start_time']);
            $horaFinActual = Carbon::createFromFormat('H:i:s', $intervalo['final_hour']);
            Log::info('horaInicioActual');
            Log::info($horaInicioActual);
            Log::info('horaFinActual');
            Log::info($horaFinActual);
            Log::info('horaActual');
            Log::info($horaActual);
            if ($horaActual > $horaInicioActual && $horaActual > $horaFinActual) {
                continue;
            } else {
                $nuevaHora = $horaActual->copy()->addMinutes($timeService);
                Log::info('$nuevaHora----');
                Log::info($nuevaHora);
                Log::info($horaInicioActual);
                Log::info('horaFinActual');
                if ($nuevaHora <= $horaInicioActual) {
                    Log::info('Respuesta 1');
                    return $horaActual->format('H:i');
                } else {
                    if ($horaActual->between($horaInicioActual, $horaFinActual)) {
                        $horaActual = $horaFinActual;
                        Log::info($i++);
                        continue;
                    } else {
                        $horaActual = $horaFinActual;
                    }
                }
            }

            /*// Si la hora actual está dentro del intervalo, continuamos al siguiente intervalo
            if ($horaActualCarbon->between($horaInicioActual, $horaFinActual)) {
                Log::info('Resp0');
                continue;
            }*/

            // Si la hora actual es posterior al final del intervalo actual y no hay más intervalos después, devolvemos la hora actual
            /*if ($horaActualCarbon->gt($horaFinActual) && !isset($arrayIntervalos[$key + 1])) {
                Log::info('Resp1');
                return $horaActual;
            }*/

            // Si hay intervalos posteriores, verificamos si el tiempo de servicio cabe y si el intervalo es mayor a la hora actual
            /*if (isset($arrayIntervalos[$key + 1])) {
                $horaInicioSiguiente = Carbon::createFromFormat('H:i:s', $arrayIntervalos[$key + 1]['start_time']);
                $diferenciaMinutos = $horaInicioSiguiente->diffInMinutes($horaFinActual);
                
                if ($timeService <= $diferenciaMinutos && $horaInicioSiguiente->gt($horaActualCarbon)) {
                    Log::info('Resp2');
                    Log::info('horaInicioActual');
                    Log::info($horaInicioActual);
                    Log::info('horaFinActual');
                    Log::info($horaFinActual);
                    return $horaActual;
                }
            }*/
        } //endForm   
        Log::info('Respuesta 2');
        Log::info($horaActual->format('H:i'));

        return $horaActual->format('H:i');
        //Log::info('Resp3');
        // Si no se encuentra ninguna hora disponible, devolvemos la última hora del último intervalo
        //return end($arrayIntervalos)['final_hour'];
    }

    //todo ESTA DE AQUI ES NUEVA, NUEVO METODO DE ENCONTRAR HORA DISPONIBLE RLP

    public function branch_professionals_serviceNew($branch_id, $services)
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
        if (Carbon::now()->addMinutes($totalTiempo) >  Carbon::parse($closingTime)) {
            return $availableProfessionals = [];
        } else {
            $professionals = Professional::whereHas('branchServices', function ($query) use ($services, $branch_id) {
                $query->whereIn('service_id', $services)->where('branch_id', $branch_id);
            }, '=', count($services))->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->get();

            foreach ($professionals as $professional) {
                $reservations = $professional->reservations()->where('branch_id', $branch_id)
                    /*->whereHas('car.orders.branchServiceProfessional.branchService', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })*/
                    ->whereDate('data', $current_date)
                    ->get()
                    ->sortBy('start_time')
                    ->map(function ($query) {
                        return [
                            'start_time' => $query->start_time,
                            'final_hour' => $query->final_hour
                        ];
                    });
                Log::info('$reservations');
                Log::info($reservations);
                // Decodificar la entrada JSON a un array de objetos
                $entrada = json_decode($reservations, true);
                //return $entrada[0];
                if ($reservations->isEmpty()) {
                    if (Carbon::now() < Carbon::parse($start_time)) {
                        $professional->start_time = Carbon::parse($start_time)->format('H:i');
                        $availableProfessionals[] = $professional;
                    } else {
                        $professional->start_time = date('H:i');
                        $availableProfessionals[] = $professional;
                    }
                } else {

                    //$arrayHoras = $this->professional_reservations_time1($branch_id, $professional->id, $current_date);
                    //return $arrayHoras;
                    $rangosHoras = $entrada;
                    $tiempoServicio = $totalTiempo;
                    $horaInicial = $start_time;
                    $horaFinal = $closingTime;
                    //$horaActual = Carbon::now()->format('H:i:s');
                    $horaActual = '08:30:60';
                    //
                    //
                    //
                    Log::info('$rangosHoras');
                    Log::info($rangosHoras);
                    Log::info('$tiempoServicio');
                    Log::info($tiempoServicio);
                    Log::info('$horaInicial');
                    Log::info($horaInicial);
                    Log::info('$horaActual');
                    Log::info($horaActual);


                    $professional->start_time = $this->calcularHoraDisponible($horaInicial, $horaFinal, $rangosHoras, $tiempoServicio, $horaActual);
                    $availableProfessionals[] = $professional;
                    //break;
                } //else
            } //for
        } //else
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


    //
    //
    //
    //
    public function calcularHoraDisponible($horaInicial, $horaFinal, $rangosHoras, $tiempoServicio, $horaActual)
    {
        // Convertir las horas a minutos para facilitar la comparación
        $horaInicialMinutos = $this->convertirHoraAMinutos($horaInicial);
        $horaFinalMinutos = $this->convertirHoraAMinutos($horaFinal);
        $horaActualMinutos = $this->convertirHoraAMinutos($horaActual);

        // Paso 1: Comprobar si la hora actual está dentro del rango inicial y final
        if ($horaActualMinutos >= $horaInicialMinutos && $horaActualMinutos < $horaFinalMinutos) {
            $horaInicialMinutos = $horaActualMinutos;
        } else {
            return 'No tiene horario disponible';
        }

        // Paso 2: Iterar sobre los rangos de horas
        foreach ($rangosHoras as $rango) {
            $horaIniMinutos = $this->convertirHoraAMinutos($rango['start_time']);
            $horaFinMinutos = $this->convertirHoraAMinutos($rango['final_hour']);
            Log::info('$horaIniMinutos');
            Log::info($horaIniMinutos);
            Log::info('$horaFinMinutos');
            Log::info($horaFinMinutos);
            Log::info('$horaInicialMinutos');
            Log::info($horaInicialMinutos);

            // Comprobar si la hora inicial está dentro del rango actual
            if ($horaInicialMinutos < $horaIniMinutos) {
                $resultHoras = $horaIniMinutos - $horaInicialMinutos;
                if ($resultHoras >= $tiempoServicio) {
                    Log::info('ESTOY ENTRANDO AQUI if 111if ($resultHoras >= $tiempoServicio) {');
                    Log::info($resultHoras - $tiempoServicio);
                    return $this->convertirMinutosAHora($horaInicialMinutos);
                } else {
                    $horaInicialMinutos = $horaFinMinutos;
                }
            }
        }

        // Si no hay más rangos y la hora inicial está dentro del rango global
        if ($horaInicialMinutos < $horaFinalMinutos) {
            $resultHoras = $horaFinalMinutos - $horaInicialMinutos;
            if ($resultHoras >= $tiempoServicio) {
                Log::info('ESTOY ENTRANDO AQUI if2222 ($resultHoras >= $tiempoServicio)');
                Log::info($resultHoras - $tiempoServicio);

                return $this->convertirMinutosAHora($horaInicialMinutos);
            } else {
                return 'No tiene horario disponible';
            }
        }

        return 'No tiene horario disponible';
    }

    private function convertirHoraAMinutos($hora)
    {
        list($horas, $minutos) = explode(':', $hora);
        return ($horas * 60) + $minutos;
    }

    private function convertirMinutosAHora($minutos)
    {
        $horas = floor($minutos / 60);
        $minutos = $minutos % 60;
        return sprintf('%02d:%02d', $horas, $minutos);
    }

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
        }, '=', count($services))->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->get();


        $current_date = Carbon::now();

        $availableProfessionals = [];
        //return $current_date->format('Y-m-d H:i:s');
        // Verificar la disponibilidad de los profesionales
        foreach ($professionals as $professional) {
            $reservations = $professional->reservations()->where('branch_id', $branch_id)
                /*->whereHas('car.orders.branchServiceProfessional.branchService', function ($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })*/
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
                for ($i = 0; $i < $count - 1; $i++) {
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
            } //else
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
        $retention = Professional::where('id', $data['professional_id'])->first()->retention;
        $cars = Car::whereHas('reservation', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate']);
        })->whereHas('clientProfessional', function ($query) use ($data) {
            $query->where('professional_id', $data['professional_id']);
        })->get()->map(function ($car) use ($retention) {
            $tip = $car->sum('tip') * 0.80;
            $retentionPorcent = $retention ? $car->orders->sum('percent_win') * $retention / 100 : $car->orders->sum('percent_win');
            $winner = $car->orders->sum('percent_win');
            return [
                'date' => $car->orders->value('data'),
                'earnings' => $winner - $retentionPorcent + $tip
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
        if ($data['charge'] == 'Barbero' || $data['charge'] == 'Barbero y Encargado') {
            $professional = Professional::where('id', $data['professional_id'])->first();
            $cars = Car::whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
            })->whereHas('clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->where('pay', 1)->get();
            $carIds = $cars->pluck('id');
            $services = 0;
            $products = 0;
            $totalClients = 0;
            //foreach ($cars as $car) {
            $orderServ = Order::whereIn('car_id', $carIds)->where('is_product', 0)->get();
            $orderProd = Order::whereIn('car_id', $carIds)->where('is_product', 1)->get();
            $services = $orderServ->count();
            //$services = $services + count($car->orders->where('is_product', 0));
            //$products = $products + count($car->orders->where('is_product', 1));
            $products = $orderProd->sum('cant');
            //}
            $ServiceEspecial = Order::whereIn('car_id', $carIds)->where('is_product', 0)->whereHas('branchServiceProfessional', function ($query) {
                $query->where('type_service', 'Especial');
            })->get();
            $ServiceRegular = Order::whereIn('car_id', $carIds)->where('is_product', 0)->whereHas('branchServiceProfessional', function ($query) {
                $query->where('type_service', 'Regular');
            })->get();
            $totalClients = $cars->count();
            $amountGenral = $cars->sum('amount');
            /*$winProfessional =$cars->sum(function ($car){
            return $car->orders->sum('percent_win');
        });*/
            $winProfessional = $orderServ->sum('percent_win');
            $retentionPorcent = $professional->retention ? $professional->retention : 0;
            $winTips = intval($cars->sum('tip') * 0.80);
            return $result = [
                'Clientes Atendidos' => $totalClients,
                'Clientes Aleatorios' => $cars->where('select_professional', 0)->count(),
                'Clientes Seleccionados' => $cars->where('select_professional', 1)->count(),
                'Productos Vendidos' => $products,
                'Cantidad de Servicios' => $services,
                'Servicios Regulares' => $ServiceRegular->count(),
                'Servicios Especiales' => $ServiceEspecial->count(),
                'Monto Servicios Especial' => number_format(round($ServiceRegular->sum('percent_win'), 2), 2),
                'Propina' => number_format(round($cars->sum('tip'), 2), 2),
                'Propina 80%' => number_format(round($winTips, 2), 2),
                'Monto Generado' => number_format(round($amountGenral, 2), 2), //suma productos y servicios
                'Retención' => $retentionPorcent ? number_format(round($winProfessional * $retentionPorcent / 100, 2), 2) : 0, //monto generado percent_win % calculando la retención
                'Ganancia Barbero' => number_format(round($winProfessional, 2), 2), //monto generado percent_win 
                'Monto Líquido' => $retentionPorcent ? number_format(round($winProfessional - ($winProfessional * $retentionPorcent / 100) + $winTips, 2), 2) : number_format(round($winProfessional + $winTips, 2), 2), //ganancia barbero - retencion + propinas 80%
            ];
        }
        if ($data['charge'] == 'Tecnico') {
            $cars = Car::with('reservation')
                ->where('pay', 1)
                ->where('tecnico_id', $data['professional_id'])
                ->whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
                })
                ->get();
            $amountGenral = $cars->sum('technical_assistance') * 5000;
            return $result = [
                'Clientes Atendidos' => $cars->sum('technical_assistance'),
                'Monto Generado' => number_format(round($amountGenral, 2), 2)
            ];
        } else {
            return $result = [];
        }
    }

    public function professionals_ganancias_branch_Periodo($data, $startDate, $endDate)
    {
        Log::info('Obtener los cars');
        if ($data['charge'] == 'Barbero' || $data['charge'] == 'Barbero y Encargado') {
            $professional = Professional::where('id', $data['professional_id'])->first();
            $cars = Car::whereHas('reservation', function ($query) use ($data, $startDate, $endDate) {
                $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);
            })->whereHas('clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->where('pay', 1)->get();
            $carIds = $cars->pluck('id');
            $services = 0;
            $products = 0;
            $totalClients = 0;
            //foreach ($cars as $car) {
            $orderServ = Order::whereIn('car_id', $carIds)->where('is_product', 0)->get();
            $orderProd = Order::whereIn('car_id', $carIds)->where('is_product', 1)->get();
            $services = $orderServ->count();
            //$services = $services + count($car->orders->where('is_product', 0));
            //$products = $products + count($car->orders->where('is_product', 1));
            $products = $orderProd->sum('cant');
            //}
            $ServiceEspecial = Order::whereIn('car_id', $carIds)->where('is_product', 0)->whereHas('branchServiceProfessional', function ($query) {
                $query->where('type_service', 'Especial');
            })->get();
            $ServiceRegular = Order::whereIn('car_id', $carIds)->where('is_product', 0)->whereHas('branchServiceProfessional', function ($query) {
                $query->where('type_service', 'Regular');
            })->get();
            $totalClients = $cars->count();
            $amountGenral = $cars->sum('amount');
            /*$winProfessional =$cars->sum(function ($car){
            return $car->orders->sum('percent_win');
        });*/
            $winProfessional = $orderServ->sum('percent_win');
            $retentionPorcent = $professional->retention ? $professional->retention : 0;
            $winTips = intval($cars->sum('tip') * 0.80);
            return $result = [
                'Clientes Atendidos' => $totalClients,
                'Clientes Aleatorios' => $cars->where('select_professional', 0)->count(),
                'Clientes Seleccionados' => $cars->where('select_professional', 1)->count(),
                'Productos Vendidos' => $products,
                'Cantidad de Servicios' => $services,
                'Servicios Regulares' => $ServiceRegular->count(),
                'Servicios Especiales' => $ServiceEspecial->count(),
                'Monto Servicios Especial' => number_format(round($ServiceRegular->sum('percent_win'), 2), 2),
                'Propina' => number_format(round($cars->sum('tip'), 2), 2),
                'Propina 80%' => number_format(round($winTips, 2), 2),
                'Monto Generado' => number_format(round($amountGenral, 2), 2), //suma productos y servicios
                'Retención' => $retentionPorcent ? number_format(round($winProfessional * $retentionPorcent / 100, 2), 2) : 0, //monto generado percent_win % calculando la retención
                'Ganancia Barbero' => number_format(round($winProfessional, 2), 2), //monto generado percent_win 
                'Monto Líquido' => $retentionPorcent ? number_format(round($winProfessional - ($winProfessional * $retentionPorcent / 100) + $winTips, 2), 2) : number_format(round($winProfessional + $winTips, 2), 2), //ganancia barbero - retencion + propinas 80%
            ];
        }
        if ($data['charge'] == 'Tecnico') {
            $cars = Car::with('reservation')
                ->where('pay', 1)
                ->where('tecnico_id', $data['professional_id'])
                ->whereHas('reservation', function ($query) use ($data, $startDate, $endDate) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);
                })
                ->get();
            $amountGenral = $cars->sum('technical_assistance') * 5000;
            return $result = [
                'Clientes Atendidos' => $cars->sum('technical_assistance'),
                'Monto Generado' => number_format(round($amountGenral, 2), 2)
            ];
        } else {
            return $result = [];
        }
    }

    public function professionals_ganancias_branch_month($data, $mes, $year)
    {
        Log::info('Obtener los cars');
        if ($data['charge'] == 'Barbero' || $data['charge'] == 'Barbero y Encargado') {
            $professional = Professional::where('id', $data['professional_id'])->first();
            $cars = Car::whereHas('reservation', function ($query) use ($data, $mes, $year) {
                $query->where('branch_id', $data['branch_id'])->whereMonth('data', $mes)->whereYear('data', $year);
            })->whereHas('clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->where('pay', 1)->get();
            $carIds = $cars->pluck('id');
            $services = 0;
            $products = 0;
            $totalClients = 0;
            //foreach ($cars as $car) {
            $orderServ = Order::whereIn('car_id', $carIds)->where('is_product', 0)->get();
            $orderProd = Order::whereIn('car_id', $carIds)->where('is_product', 1)->get();
            $services = $orderServ->count();
            //$services = $services + count($car->orders->where('is_product', 0));
            //$products = $products + count($car->orders->where('is_product', 1));
            $products = $orderProd->sum('cant');
            //}
            $ServiceEspecial = Order::whereIn('car_id', $carIds)->where('is_product', 0)->whereHas('branchServiceProfessional', function ($query) {
                $query->where('type_service', 'Especial');
            })->get();
            $ServiceRegular = Order::whereIn('car_id', $carIds)->where('is_product', 0)->whereHas('branchServiceProfessional', function ($query) {
                $query->where('type_service', 'Regular');
            })->get();
            $totalClients = $cars->count();
            $amountGenral = $cars->sum('amount');
            /*$winProfessional =$cars->sum(function ($car){
                return $car->orders->sum('percent_win');
            });*/
            $winProfessional = $orderServ->sum('percent_win');
            $retentionPorcent = $professional->retention ? $professional->retention : 0;
            $winTips = intval($cars->sum('tip') * 0.80);
            return $result = [
                'Clientes Atendidos' => $totalClients,
                'Clientes Aleatorios' => $cars->where('select_professional', 0)->count(),
                'Clientes Seleccionados' => $cars->where('select_professional', 1)->count(),
                'Productos Vendidos' => $products,
                'Cantidad de Servicios' => $services,
                'Servicios Regulares' => $ServiceRegular->count(),
                'Servicios Especiales' => $ServiceEspecial->count(),
                'Monto Servicios Especial' => number_format(round($ServiceRegular->sum('percent_win'), 2), 2),
                'Propina' => number_format(round($cars->sum('tip'), 2), 2),
                'Propina 80%' => number_format(round($winTips, 2), 2),
                'Monto Generado' => number_format(round($amountGenral, 2), 2), //suma productos y servicios
                'Retención' => $retentionPorcent ? number_format(round($winProfessional * $retentionPorcent / 100, 2), 2) : 0, //monto generado percent_win % calculando la retención
                'Ganancia Barbero' => number_format(round($winProfessional, 2), 2), //monto generado percent_win 
                'Monto Líquido' => $retentionPorcent ? number_format(round($winProfessional - ($winProfessional * $retentionPorcent / 100) + $winTips, 2), 2) : number_format(round($winProfessional + $winTips, 2), 2), //ganancia barbero - retencion + propinas 80%
            ];
        }
        if ($data['charge'] == 'Tecnico') {
            $cars = Car::with('reservation')
                ->where('pay', 1)
                ->where('tecnico_id', $data['professional_id'])
                ->whereHas('reservation', function ($query) use ($data, $mes, $year) {
                    $query->where('branch_id', $data['branch_id'])->whereMonth('data', $mes)->whereYear('data', $year);
                })
                ->get();
            $amountGenral = $cars->sum('technical_assistance') * 5000;
            return $result = [
                'Clientes Atendidos' => $cars->sum('technical_assistance'),
                'Monto Generado' => number_format(round($amountGenral, 2), 2)
            ];
        } else {
            return $result = [];
        }
    }

    public function professionals_state($branch_id, $reservation_id)
    {
        $reservation = Reservation::find($reservation_id);
        $orders = Order::where('car_id', $reservation->car_id)->get()->pluck('branch_service_professional_id');
        $branchService = BranchServiceProfessional::whereIn('id', $orders)->get()->pluck('branch_service_id');
        $total_timeMin = $this->convertirHoraAMinutos($reservation->total_time);
        //$branchId = 1; // Reemplaza con el ID de la sucursal que estás buscando
        $currentTime = Carbon::now();

        $professionals = Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->where('arrival', '!=', NULL);;
        })->whereHas('branchServiceProfessionals', function ($query) use ($branchService) {
            $query->whereIn('branch_service_id', $branchService);
        }, '=', count($branchService))->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->where('state', 1)->join('branch_professional', function ($join) use ($branch_id) {
            $join->on('professionals.id', '=', 'branch_professional.professional_id')
                ->where('branch_professional.branch_id', '=', $branch_id)
                ->where('branch_professional.arrival', '!=', NULL);
        })->select(
            'professionals.id',
            'professionals.name',
            'professionals.surname',
            'professionals.second_surname',
            'professionals.email',
            'professionals.phone',
            'professionals.charge_id',
            'professionals.state',
            'professionals.image_url',
            'branch_professional.arrival',
            'branch_professional.living',
            'branch_professional.numberRandom'
            )->orderBy('branch_professional.numberRandom', 'asc')
            ->orderBy('branch_professional.living', 'asc')
            ->orderBy('branch_professional.arrival', 'asc')
            ->get();
        $professionalFree = [];
        // Convertir el campo telefono a string
        // Iterar sobre los profesionales
        foreach ($professionals as $professional) {
            Log::info('Professional analizando');
            Log::info($professional);
            // Convertir el campo teléfono a string
            $professional->phone = (string) $professional->phone;
        
            // Verificar la disponibilidad del profesional en su lugar de trabajo
            $workplaceProfessional = ProfessionalWorkPlace::where('professional_id', $professional->id)
                ->whereDate('data', Carbon::now())
                ->where('state', 1)
                ->whereHas('workplace', function ($query) use ($branch_id) {
                    $query->where('busy', 1)->where('branch_id', $branch_id);
                })->first();
        
            Log::info('Puesto de trabajo');
            Log::info($workplaceProfessional);
        
            $current_date = Carbon::now();
            $nuevaHoraInicio = Carbon::now();
        
            if ($workplaceProfessional) {
                $professional->position = $workplaceProfessional->workplace->name;
                $professional->charge_id = $professional->charge->name;
        
                $attended = $professional->reservations()
                    ->where('branch_id', $reservation->branch_id)
                    ->whereIn('confirmation', [1, 4])
                    ->whereDate('data', Carbon::now())
                    ->whereHas('tail', function ($subquery) {
                        $subquery->whereIn('attended', [1, 11, 111, 4, 5, 33]);
                    })
                    ->get();
        
                if ($attended->isNotEmpty()) {
                    Log::info('Está atendiendo');
                } else {
                    $reservations = $professional->reservations()
                        ->where('branch_id', $reservation->branch_id)
                        ->whereIn('confirmation', [1, 4])
                        ->whereDate('data', Carbon::now())
                        ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1);
                        })
                        ->orderBy('start_time')
                        ->get();
        
                    if ($reservations->isEmpty()) {
                        Log::info('No tiene reservas, lo agrego como libre');
                        $professionalFree[] = $professional;
                    } else {
                        foreach ($reservations as $reservation1) {
                            // Comprobación de start_time y attended
                            Log::info('Reservaciones');
                            Log::info($reservation1);
                            $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
                            $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));
        
                            if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin && $reservation1->confirmation !=4) {
                                Log::info('Cabe antes de la primera reserva despues de la hora actual que possee en la cola');
                                $professionalFree[] = $professional;
                                break;
                            }else{
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $professionalFree;
    }
}
