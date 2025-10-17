<?php

namespace App\Services;

use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Order;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\ProfessionalWorkPlace;
use App\Models\Record;
use App\Models\Reservation;
use App\Models\Retention;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Vacation;
use Carbon\Carbon;
use Exception;
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
        $professionals = Professional::whereHas('user', function ($query) use ($email) {
            $query->where('name', $email);
        })->whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->first();
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

    private function encontrarIntervaloLibreOld($reservations, $horaActual, $tiempoReserva, $reservation)
    {
        $encontrado = false;
        $nuevaHoraInicio = $horaActual;
        $total_timeMin = $this->convertirHoraAMinutos($tiempoReserva);

        foreach ($reservations as $index => $reservation1) {
            $car = $reservation1->car;
            if ($reservation1->from_home == 1 && $reservation1->confirmation == 4) {
                $nuevaHoraInicio = Carbon::parse($reservation1->final_hour)->format('H:i');
                $encontrado = true;
                break;
            }
            elseif ($reservation1->from_home == 0 && $car->select_professional == 1) {
                $nuevaHoraInicio = $reservation1->final_hour;
                $encontrado = true;
                break;
            }elseif ($car->select_professional == 0 && $reservation1->tail->aleatorie == 2) {
                //if ($reservation1->created_at < $reservation->created_at) {
                    $nuevaHoraInicio = $reservation1->final_hour;
                    $encontrado = true;
                    break;
                //}
            }else {
                $nuevaHoraInicio = Carbon::parse($horaActual)->format('H:i');
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            $nuevaHoraInicio = Carbon::parse($reservations->last()->final_hour)->format('H:i');
        }

        return $nuevaHoraInicio;
    }

    public function branch_professionals_service_tottem($branch_id, $services, $professional_id, $reservation)//Cambio 31-08-24
    {
        try{
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
                })->whereNot('professionals.id', $professional_id)
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
                )->orderBy('branch_professional.living', 'asc')
                ->orderBy('branch_professional.arrival', 'asc')
                ->get();

            $horaActual = $horaActual = Carbon::now();
            $tiempoReserva = $reservation->total_time;
            foreach ($professionals1 as $professional) {
                $reservations = $professional->reservations()
                ->where('branch_id', $branch_id)
                ->where('confirmation', 4)
                ->whereHas('tail', function ($query) {
                    $query->whereNot('aleatorie', 1);
                })
                ->whereDate('data', Carbon::now())
                ->orderByDesc('start_time')
                ->get();
                if ($reservations->isEmpty()) {
                    $professional->start_time = date('H:i');
                    $professional->free = 'Libre';
                    $availableProfessionals[] = $professional;
                }else {
                    $nuevaHoraInicio = $this->encontrarIntervaloLibreOld($reservations, $horaActual, $tiempoReserva, $reservation);                    
                    if ($nuevaHoraInicio == $horaActual->format('H:i')) {
                        $professional->free = 'Libre';
                    }else {
                        $professional->free = 'Ocupado';
                    }
                    $professional->start_time = $nuevaHoraInicio;
                    $availableProfessionals[] = $professional;
                    $firstReservation = $reservations->first();
                    if (in_array($firstReservation->tail->attended, [1, 11, 111, 4, 5, 33])) { // Cambia 'estado1', 'estado2', etc., por los estados específicos
                        $professional->free = 'Ocupado';
                    }
                }
            } //for
        } //else
        //return $availableProfessionals;

        $returnedProfessionals = [];

        foreach ($professionals1 as $professional) {
            $time = strtotime($professional->start_time);
            if ($time + ($totalTiempo * 60) <= strtotime($closingTime)) {
                // Si el tiempo final es menor o igual al horario de cierre, agregar al profesional a la lista de devolución
                $returnedProfessionals[] = $professional;
            }
        }
        foreach ($professionals1 as $professional) {
            $professional->charge_id = $professional->charge->name;
            $workplaceProfessional = ProfessionalWorkPlace::where('professional_id', $professional->id)
                ->whereDate('data', Carbon::now())
                ->where('state', 1)
                ->whereHas('workplace', function ($query) use ($branch_id) {
                    $query->where('busy', 1)->where('branch_id', $branch_id);
                })->first();
            if ($workplaceProfessional) {
                $professional->position = $workplaceProfessional->workplace->name;
            }else {
                $professional->position = '';
            }
            if ($professional->state == 2) {
                if ($professional->colacion_time != NUll) {
                    //return 'Esta en colacion'.$professional->colacion_time;
                    $colacion_time = Carbon::parse($professional->colacion_time)->addMinutes(60);
                if (Carbon::parse($professional->start_time) < $colacion_time){
                    $reservs = $professional->reservations()->where('branch_id', $branch_id)->where('confirmation', 4)
                    ->whereDate('data', $current_date)
                    ->where('start_time', '>', $colacion_time->format('H:i'))
                    ->orderBy('start_time')
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
                    $professional->start_time = $colacion_time->format('H:i');
                }
                }                
                $professional->free = 'Colación';
                $professional->position = 'COLACIÓN';
            }
        }
        $returnedProfessionals = collect($returnedProfessionals)->sortBy([
            ['state', 'asc'],
            ['start_time', 'asc']
        ])->values();

        return $returnedProfessionals;
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el Professionalservic(branch_professionals_service_tottem): " . $e->getMessage());
        }
    }

    public function branch_professionals_serviceOld($branch_id, $services)
    {
        try{
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
                    'professionals.end_time',
                    'branch_professional.arrival',
                    'branch_professional.living'
                )->orderBy('branch_professional.living', 'asc')
                ->orderBy('branch_professional.arrival', 'asc')
                ->get();
            foreach ($professionals1 as $professional1) {
                $vacation = Vacation::where('professional_id', $professional1->id)->whereDate('startDate', '<=', $fechaDada)
                    ->whereDate('endDate', '>=', $fechaDada)
                    ->first();
                if (!$vacation) {
                    $professionals[] = $professional1;
                }
            }
            $current_time = now()->format('H:i:s');
            foreach ($professionals1 as $professional) {
                $reservations = $professional->reservations()->where('branch_id', $branch_id)->where('confirmation', 4)
                    ->whereDate('data', $current_date)
                    ->whereHas('tail', function ($subquery) {
                        $subquery->where('aleatorie', '!=', 1);
                    })
                    ->get()
                    ->sortBy('start_time')
                    ->map(function ($query) use ($current_time, $professional) {
                        $attended_values = [1, 11, 111, 4, 5, 33];
                        $attended = (int) $query->tail->attended;
                        if (($attended !== 0 && $attended !==3) || $query->tail->aleatorie != 1) {
                            $professional->attended = 1;
                            $professional->finalHourAttended = $query->final_hour;
                        } else {
                            $professional->attended = 0;
                            $professional->finalHourAttended = '';
                        }
                        if ($query->confirmation == 4 && $query->from_home == 1) {
                            $start_time = $current_time;
                            $final_hour = date('H:i:s', strtotime($start_time) + strtotime($query->total_time) - strtotime('TODAY'));
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
                // Decodificar la entrada JSON a un array de objetos
                $entrada = json_decode($reservations, true);
                //return $entrada[0];
                if ($reservations->isEmpty()) {
                    $professional->attended = 0;
                    $professional->finalHourAttended = '';
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
                    $colacion_time = Carbon::parse($professional->colacion_time)->addMinutes(60);
                if (Carbon::parse($professional->start_time) < $colacion_time){
                    $reservs = $professional->reservations()->where('branch_id', $branch_id)->where('confirmation', 4)
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
                    $professional->start_time = $colacion_time->format('H:i');
                    
                }
                }
                
            }
            $reservation = Reservation::where('branch_id', $branch_id)->where('confirmation', 2)->whereHas('car.clientProfessional', function ($query) use ($professional) {
                $query->where('professional_id', $professional->id);
            })->orderByDesc('finished_at')->whereDate('data', Carbon::now())->first();
            if ($reservation != null && $professional->end_time == null) {
                $professional->disponible = $reservation->finished_at->format('H:i:s');
            }
            else if ($professional->end_time !== null && Carbon::parse($professional->end_time)->toDateString() == Carbon::now()->toDateString()) {
                // Convertir end_time y el tiempo de la última reserva en instancias de Carbon
                $endTime = Carbon::parse($professional->end_time);
                $lastReservationTime = Carbon::parse($reservation->finished_at);

                // Comparar y decidir el tiempo que se asignará a `disponible`
                if ($lastReservationTime->gt($endTime)) {
                    $professional->disponible = $lastReservationTime->format('H:i:s');
                } else {
                    $professional->disponible = $endTime->format('H:i:s');
                }
            }
            else {
                $record = Record::where('professional_id', $professional->id)->where('branch_id', $branch_id)->whereDate('start_time', Carbon::now())->orderByDesc('start_time')->first();
                if ($record != null) {
                    $professional->disponible = Carbon::parse($record->start_time)->format('H:i:s');
                }
                else {
                    $professional->disponible = Carbon::parse($startTime)->format('H:i:s');
                }
                
            }
            if ($professional->attended != 0) {
                $finalHourAttended = Carbon::parse($professional->finalHourAttended);
                $disponible = Carbon::parse($professional->disponible);

                if ($finalHourAttended->gt($disponible)) {
                    // Aquí puedes agregar la lógica que necesites
                    $professional->disponible = $finalHourAttended->format('H:i:s');
                }
            }
        }

        $returnedProfessionals = collect($returnedProfessionals)->sortBy([
            ['state', 'asc'],
            ['start_time', 'asc'],
            ['disponible', 'asc'],
            ['living', 'asc'],
            ['arrival', 'asc']
        ])->values();

        return $returnedProfessionals;
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el ProfessionalService(branch_professionals_service): " . $e->getMessage());
        }
    }

    public function branch_professionals_service($branch_id, $services)
    {
        try{
        $totalTiempo = Service::whereIn('id', $services)->get()->sum('duration_service');
        $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $startTime = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
        $closingTime = Carbon::now()->setTime(23, 59, 59)->format('H:i:s');/*Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('closing_time');*/
        $current_date = Carbon::now()->format('Y-m-d');
        $availableProfessionals = [];
        $fechaDada = Carbon::now()->format('Y-m-d');
        $returnedProfessionals = [];
        //return Carbon::now()->addMinutes($totalTiempo);
        if (Carbon::now()->addMinutes($totalTiempo) >  Carbon::parse($closingTime)) {
            return $availableProfessionals = [];
        } else {
            $professionals1 = Professional::whereHas('branchServiceProfessionals', function ($query) use ($services, $branch_id) {
                    $query->whereHas('branchService', function ($q) use ($services, $branch_id) {
                        $q->whereIn('service_id', $services)
                        ->where('branch_id', $branch_id);
                    });
                }, '=', count($services))
                ->whereHas('charge', function ($query) {
                    $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                })
                /*->whereIn('state', [1, 2])*/
                ->join('branch_professional', function ($join) use ($branch_id) {
                    $join->on('professionals.id', '=', 'branch_professional.professional_id')
                        ->where('branch_professional.branch_id', '=', $branch_id)
                        /*->where('branch_professional.arrival', '!=', NULL)*/;
                })
                ->select(
                    'professionals.id',
                    'professionals.name',
                    'professionals.state',
                    'professionals.start_time as colacion_time',
                    'professionals.image_url',
                    'professionals.end_time',
                    'branch_professional.arrival',
                    'branch_professional.living'
                )->orderBy('branch_professional.living', 'asc')
                ->orderBy('branch_professional.arrival', 'asc')
                ->get();
                if ($professionals1->isEmpty()) {
                    return $returnedProfessionals;
                }
                else {
                    $current_time = now()->format('H:i:s');
            foreach ($professionals1 as $professional) {
                $reservations = $professional->reservations()->where('branch_id', $branch_id)->where('confirmation', 4)
                    ->whereDate('data', $current_date)
                    ->whereHas('tail', function ($subquery) {
                        $subquery->where('aleatorie', '!=', 1);
                    })
                    ->get()
                    ->sortBy('start_time')
                    ->map(function ($query) use ($current_time, $professional) {
                        $attended_values = [1, 11, 111, 4, 5, 33];
                          $attended = (int) $query->tail->attended;
                        if (($attended !== 0 && $attended !==3) || $query->tail->aleatorie != 1) {
                            $professional->attended = 1;
                            $professional->finalHourAttended = $query->final_hour;
                        } else {
                            $professional->attended = 0;
                            $professional->finalHourAttended = '';
                        }
                        if ($query->confirmation == 4 && $query->from_home == 1) {
                             $start_time = $current_time;
                            $final_hour = date('H:i:s', strtotime($start_time) + strtotime($query->total_time) - strtotime('TODAY'));
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
                // Decodificar la entrada JSON a un array de objetos
                $entrada = json_decode($reservations, true);
                //return $entrada[0];
                if ($reservations->isEmpty()) {
                    $professional->attended = 0;
                    $professional->finalHourAttended = '';
                    if (Carbon::now() < Carbon::parse($startTime)) {
                        $professional->start_time = Carbon::parse($startTime)->format('H:i');
                        $availableProfessionals[] = $professional;
                    } else {
                        $professional->start_time = date('H:i');
                    }
                } else {
                    //$arrayHoras = $this->professional_reservations_time1($branch_id, $professional->id, $current_date);
                    //return $arrayHoras;
                    $professional->start_time = $this->encontrarHoraDisponible($totalTiempo, $entrada, $startTime);
                    //break;
                } //else
                // Validar el horario de cierre
                $time = strtotime($professional->start_time);
                if ($time + ($totalTiempo * 60) <= strtotime($closingTime)) {
                //comprobar estado de colacion
                if ($professional->state == 2) {
                    if ($professional->colacion_time != NUll) {
                        $colacion_time = Carbon::parse($professional->colacion_time)->addMinutes(60);
                    if (Carbon::parse($professional->start_time) < $colacion_time){
                        $reservs = $professional->reservations()->where('branch_id', $branch_id)->where('confirmation', 4)
                        ->whereDate('data', $current_date)
                        ->where('start_time', '>', $colacion_time->format('H:i'))
                        ->orderBy('start_time')
                        ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1);
                        })
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
                        $professional->start_time = $colacion_time->format('H:i');
                        
                    }
                    }                    
                }//end if Colacion

                //Obtener el tiempo Disponible de cada barbero
                $reservation = Reservation::where('branch_id', $branch_id)->where('confirmation', 2)->whereHas('car.clientProfessional', function ($query) use ($professional) {
                    $query->where('professional_id', $professional->id);
                })->orderByDesc('finished_at')->whereDate('data', Carbon::now())->first();
                if ($reservation != null && $professional->end_time == null) {
                    $professional->disponible = $reservation->finished_at->format('H:i:s');
                }
                else if ($professional->end_time != null && Carbon::parse($professional->end_time)->toDateString() == Carbon::now()->toDateString() && $reservation) {
                    // Convertir end_time y el tiempo de la última reserva en instancias de Carbon
                    $endTime = Carbon::parse($professional->end_time);
                    $lastReservationTime = Carbon::parse($reservation->finished_at);
    
                    // Comparar y decidir el tiempo que se asignará a `disponible`
                    if ($lastReservationTime->gt($endTime)) {
                        $professional->disponible = $lastReservationTime->format('H:i:s');
                    } else {
                        $professional->disponible = $endTime->format('H:i:s');
                    }
                }
                else if($professional->end_time != null && Carbon::parse($professional->end_time)->toDateString() == Carbon::now()->toDateString() && !$reservation){
                    $endTime = Carbon::parse($professional->end_time);
                        $professional->disponible = $endTime->format('H:i:s');
                }
                else {
                    $record = Record::where('professional_id', $professional->id)->where('branch_id', $branch_id)->whereDate('start_time', Carbon::now())->orderByDesc('start_time')->first();
                    if ($record != null) {
                        $professional->disponible = Carbon::parse($record->start_time)->format('H:i:s');
                    }
                    else {
                        $professional->disponible = Carbon::parse($startTime)->format('H:i:s');
                    }
                    
                }
                if ($professional->attended != 0) {
                    $finalHourAttended = Carbon::parse($professional->finalHourAttended);
                    $disponible = Carbon::parse($professional->disponible);
    
                    if ($finalHourAttended->gt($disponible)) {
                        // Aquí puedes agregar la lógica que necesites
                        $professional->disponible = $finalHourAttended->format('H:i:s');
                    }
                }
                //end comprobacion del  campo disponible
                    $returnedProfessionals[] = $professional;
                }//end de si el start_time esta dentro del cierre de la sucursal
            } //for professional
                }
        } //else
        //return $availableProfessionals;
        $returnedProfessionals = collect($returnedProfessionals)->sortBy([
            ['state', 'asc'],
            ['start_time', 'asc'],
            ['attended', 'asc'],    // Si 'start_time' coincide, ordena por 'attended'
            ['disponible', 'asc'],
            ['living', 'asc'],
            ['arrival', 'asc']
        ])->values();

        return $returnedProfessionals;
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el ProfessionalService(branch_professionals_service): " . $e->getMessage());
        }
    }
    
    public function branch_professionals_service_tottem1($branch_id, $services)
    {
        try{
        $totalTiempo = Service::whereIn('id', $services)->get()->sum('duration_service');
        $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $startTime = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
        $closingTime = Carbon::now()->setTime(23, 59, 59)->format('H:i:s');/*Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('closing_time');*/
        $current_date = Carbon::now()->format('Y-m-d');
        $availableProfessionals = [];
        $fechaDada = Carbon::now()->format('Y-m-d');
        $returnedProfessionals = [];
        //return Carbon::now()->addMinutes($totalTiempo);
        if (Carbon::now()->addMinutes($totalTiempo) >  Carbon::parse($closingTime)) {
            return $availableProfessionals = [];
        } else {
            $professionals1 = Professional::whereHas('branchServiceProfessionals', function ($query) use ($services, $branch_id) {
                    $query->whereHas('branchService', function ($q) use ($services, $branch_id) {
                        $q->whereIn('service_id', $services)
                        ->where('branch_id', $branch_id);
                    });
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
                    'professionals.state',
                    'professionals.start_time as colacion_time',
                    'professionals.image_url',
                    'professionals.end_time',
                    'branch_professional.arrival',
                    'branch_professional.living'
                )->orderBy('branch_professional.living', 'asc')
                ->orderBy('branch_professional.arrival', 'asc')
                ->get();
                if ($professionals1->isEmpty()) {
                    return $returnedProfessionals;
                }
                else {
                    $current_time = now()->format('H:i:s');
            foreach ($professionals1 as $professional) {
                $reservations = $professional->reservations()->where('branch_id', $branch_id)->where('confirmation', 4)
                    ->whereDate('data', $current_date)
                    ->whereHas('tail', function ($subquery) {
                        $subquery->where('aleatorie', '!=', 1);
                    })
                    ->get()
                    ->sortBy('start_time')
                    ->map(function ($query) use ($current_time, $professional) {
                        $attended_values = [1, 11, 111, 4, 5, 33];
                        $attended = (int) $query->tail->attended;
                        if (($attended !== 0 && $attended !==3) || $query->tail->aleatorie != 1) {
                            $professional->attended = 1;
                            $professional->finalHourAttended = $query->final_hour;
                        } else {
                            $professional->attended = 0;
                            $professional->finalHourAttended = '';
                        }
                        if ($query->confirmation == 4 && $query->from_home == 1) {
                                 $start_time = $current_time;
                            $final_hour = date('H:i:s', strtotime($start_time) + strtotime($query->total_time) - strtotime('TODAY'));
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
                // Decodificar la entrada JSON a un array de objetos
                $entrada = json_decode($reservations, true);
                //return $entrada[0];
                if ($reservations->isEmpty()) {
                    $professional->attended = 0;
                    $professional->finalHourAttended = '';
                    if (Carbon::now() < Carbon::parse($startTime)) {
                        $professional->start_time = Carbon::parse($startTime)->format('H:i');
                        $availableProfessionals[] = $professional;
                    } else {
                        $professional->start_time = date('H:i');
                    }
                } else {
                    //$arrayHoras = $this->professional_reservations_time1($branch_id, $professional->id, $current_date);
                    //return $arrayHoras;
                    $professional->start_time = $this->encontrarHoraDisponible($totalTiempo, $entrada, $startTime);
                    //break;
                } //else
                // Validar el horario de cierre
                $time = strtotime($professional->start_time);
                if ($time + ($totalTiempo * 60) <= strtotime($closingTime)) {
                //comprobar estado de colacion
                if ($professional->state == 2) {
                    if ($professional->colacion_time != NUll) {
                        $colacion_time = Carbon::parse($professional->colacion_time)->addMinutes(60);
                    if (Carbon::parse($professional->start_time) < $colacion_time){
                        $reservs = $professional->reservations()->where('branch_id', $branch_id)->where('confirmation', 4)
                        ->whereDate('data', $current_date)
                        ->where('start_time', '>', $colacion_time->format('H:i'))
                        ->orderBy('start_time')
                        ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1);
                        })
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
                        $professional->start_time = $colacion_time->format('H:i');
                        
                    }
                    }                    
                }//end if Colacion

                //Obtener el tiempo Disponible de cada barbero
                $reservation = Reservation::where('branch_id', $branch_id)->where('confirmation', 2)->whereHas('car.clientProfessional', function ($query) use ($professional) {
                    $query->where('professional_id', $professional->id);
                })->orderByDesc('finished_at')->whereDate('data', Carbon::now())->first();
                if ($reservation != null && $professional->end_time == null) {
                    $professional->disponible = $reservation->finished_at->format('H:i:s');
                }
                else if ($professional->end_time != null && Carbon::parse($professional->end_time)->toDateString() == Carbon::now()->toDateString() && $reservation) {
                    // Convertir end_time y el tiempo de la última reserva en instancias de Carbon
                    $endTime = Carbon::parse($professional->end_time);
                    $lastReservationTime = Carbon::parse($reservation->finished_at);
    
                    // Comparar y decidir el tiempo que se asignará a `disponible`
                    if ($lastReservationTime->gt($endTime)) {
                        $professional->disponible = $lastReservationTime->format('H:i:s');
                    } else {
                        $professional->disponible = $endTime->format('H:i:s');
                    }
                }
                else if($professional->end_time != null && Carbon::parse($professional->end_time)->toDateString() == Carbon::now()->toDateString() && !$reservation){
                    $endTime = Carbon::parse($professional->end_time);
                        $professional->disponible = $endTime->format('H:i:s');
                }
                else {
                    $record = Record::where('professional_id', $professional->id)->where('branch_id', $branch_id)->whereDate('start_time', Carbon::now())->orderByDesc('start_time')->first();
                    if ($record != null) {
                        $professional->disponible = Carbon::parse($record->start_time)->format('H:i:s');
                    }
                    else {
                        $professional->disponible = Carbon::parse($startTime)->format('H:i:s');
                    }
                    
                }
                if ($professional->attended != 0) {
                    $finalHourAttended = Carbon::parse($professional->finalHourAttended);
                    $disponible = Carbon::parse($professional->disponible);
    
                    if ($finalHourAttended->gt($disponible)) {
                        // Aquí puedes agregar la lógica que necesites
                        $professional->disponible = $finalHourAttended->format('H:i:s');
                    }
                }
                //end comprobacion del  campo disponible
                    $returnedProfessionals[] = $professional;
                }//end de si el start_time esta dentro del cierre de la sucursal
            } //for professional
                }
        } //else
        //return $availableProfessionals;
        $returnedProfessionals = collect($returnedProfessionals)->sortBy([
            ['state', 'asc'],
            ['start_time', 'asc'],
            ['attended', 'asc'],    // Si 'start_time' coincide, ordena por 'attended'
            ['disponible', 'asc'],
            ['living', 'asc'],
            ['arrival', 'asc']
        ])->values();

        return $returnedProfessionals;
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el ProfessionalService(branch_professionals_service): " . $e->getMessage());
        }
    }   
    

    public function professional_reservations_time($branch_id, $professional_id, $day)
    {
        try {
            $data = [
                'branch_id' => $branch_id,
                'professional_id' => $professional_id,
                'data' => $day
            ];
            $nombreDia = ucfirst(strtolower(Carbon::parse($data['data'])->locale('es_ES')->dayName));
            $horario = Schedule::where('branch_id', $data['branch_id'])->where('day', $nombreDia)->first();
            $start_time = Carbon::parse($horario->start_time)->format('H:i');
            $closing_time = Carbon::parse($horario->closing_time)->format('H:i');
            //$closing_time = $horario->closing_time;
            //$startTime = strtotime($start_time);
            $reservations = [];

            $currentDateTime =  Carbon::now();
            if (Carbon::parse($data['data'])->isToday()) {
                $professional = Professional::where('professionals.id', $data['professional_id'])
                    ->whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })
                    ->with(['reservations' => function ($query) use ($data) {
                        $query->whereDate('data', $data['data'])
                              ->orderBy('start_time')
                              ->whereIn('confirmation', [1, 4])
                              ->whereHas('tail', function ($subquery) {
                                  $subquery->where('aleatorie', '!=', 1);
                              });
                    }])/*->whereIn('state',[1, 2])*/->join('branch_professional',function ($join) use ($data) {
                        $join->on('professionals.id', '=', 'branch_professional.professional_id')
                            ->where('branch_professional.branch_id', '=', $data['branch_id'])
                            /*->where('branch_professional.arrival', '!=', NULL)*/;
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
                        'branch_professional.living',
                        'branch_professional.numberRandom'
                    )->first();
                if ($professional == null) {
                    $startTime = Carbon::parse($start_time);
                    //$horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                    $closingTime = Carbon::parse($closing_time);
                    while ($startTime <= $closingTime) {
                        $reservations[] = $startTime->format('H:i');
                        $startTime->addMinutes(10);
                    }
                    sort($reservations);
                    return $reservations;
                } else {
                    if ($professional->reservations->isNotEmpty()) 
                    {
                        $reservations = $professional->reservations->filter(function ($reservation) {
                            // Filtrar las relaciones 'tails' para que 'aleatorio' sea distinto de 1
                            return $reservation->tail && $reservation->tail->aleatorio != 1;
                        })->map(function ($reservation) use ($start_time) {
                            $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                            $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                            $intervalos = [$startFormatted];
                            $startTime = Carbon::parse($startFormatted);

                            $finalTime = Carbon::parse($reservation->final_hour);
                            $finalMinutes = $finalTime->minute;
                            
                            if ($finalMinutes <= 10){
                            $roundedMinutes = '05';
                            }
                         elseif ($finalMinutes <= 20) {
                            
                            $roundedMinutes = '15';
                            }
                         elseif ($finalMinutes <= 30) {
                            $roundedMinutes = '25';
                        }elseif ($finalMinutes <= 40) {
                            $roundedMinutes = '35';
                        } 
                        elseif ($finalMinutes <= 50) {
                            $roundedMinutes = '45';
                        } 
                        elseif ($finalMinutes <= 59) {
                            $roundedMinutes = '55';
                        }
                        else {
                            $finalTime->addHour();
                            $roundedMinutes = '00';
                        }

                            $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                            $finalTime = Carbon::parse($finalFormatted);
                            $horaActual = Carbon::now();
                            $horaActualMas2Horas = $horaActual->copy()->addHours(1);

                            // Si $finalTime es menor que la hora actual más 2 horas, asignar la hora actual más 2 horas a $finalTime
                            if ($finalTime->lessThan($horaActualMas2Horas)) {
                                $finalTime = $horaActualMas2Horas;
                            }
                            while ($startTime->addMinutes(10) <= $finalTime) {
                                $intervalos[] = $startTime->format('H:i');
                            }

                            if (count($intervalos) % 2 !== 0) {
                                $intervalos[] = end($intervalos); // Agrega el último valor duplicado si es impar
                            }

                            return $intervalos;
                        })->flatten()->values()->all();
                        $firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                        $horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                        if ($horaActualMas2Horas->lessThan($firstReservationStartTime)) {
                            $startTime = Carbon::parse($start_time);
                            while ($startTime <= $horaActualMas2Horas) {
                                $reservations[] = $startTime->format('H:i');
                                $startTime->addMinutes(10);
                            }

                            if (count($reservations) % 2 !== 0) {
                                $reservations[] = end($reservations); // Agrega el último valor duplicado si es impar
                            }
                        } else {
                            $startTime = Carbon::parse($start_time);
                            while ($startTime <= $horaActualMas2Horas) {
                                $formattedTime = $startTime->format('H:i');
                                // Solo agrega el tiempo si no está en el array
                                if (!in_array($formattedTime, $reservations)) {
                                    $reservations[] = $formattedTime;
                                }

                                $startTime->addMinutes(10);
                            }

                            if (count($reservations) % 2 !== 0) {
                                $reservations[] = end($reservations); // Agrega el último valor duplicado si es impar
                            }
                        }

                        sort($reservations);
                        return $reservations;
                    } else {
                        $startTime = Carbon::parse($start_time);
                        $horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                        $closingTime = Carbon::parse($horaActualMas2Horas);
                        while ($startTime <= $closingTime) {
                            $reservations[] = $startTime->format('H:i');
                            $startTime->addMinutes(10);
                        }
                        sort($reservations);
                        return $reservations;
                    }
                }
            } else {
                $professional = Professional::where('id', $data['professional_id'])
                    ->whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })
                    ->with(['reservations' => function ($query) use ($data) {
                        $query->whereDate('data', $data['data'])->orderBy('start_time')->whereIn('confirmation', [1,4]);
                    }])
                    ->first();
                if ($professional && $professional->reservations->isNotEmpty()) {
                    $reservations = $professional->reservations->map(function ($reservation) {
                        $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                        $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                        $intervalos = [$startFormatted];
                        $startTime = Carbon::parse($startFormatted);

                        $finalTime = Carbon::parse($reservation->final_hour);
                        $finalMinutes = $finalTime->minute;

                         if ($finalMinutes <= 10){
                            $roundedMinutes = '05';
                            }
                         elseif ($finalMinutes <= 20) {
                            
                            $roundedMinutes = '15';
                            }
                         elseif ($finalMinutes <= 30) {
                            $roundedMinutes = '25';
                        }elseif ($finalMinutes <= 40) {
                            $roundedMinutes = '35';
                        } 
                        elseif ($finalMinutes <= 50) {
                            $roundedMinutes = '45';
                        } 
                        elseif ($finalMinutes <= 59) {
                            $roundedMinutes = '55';
                        }
                        else {
                            $finalTime->addHour();
                            $roundedMinutes = '00';
                        }


                        $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                        $finalTime = Carbon::parse($finalFormatted);
                        $horaActual = Carbon::now();
                        /*if ($finalTime->lessThan($horaActual)) {
                            $finalTime = $horaActual;
                        }*/
                        // Agregar las horas intermedias de 15 en 15 minutos
                        while ($startTime->addMinutes(10) <= $finalTime) {
                            $intervalos[] = $startTime->format('H:i');
                        }

                        if (count($intervalos) % 2 !== 0) {
                            $intervalos[] = end($intervalos); // Agrega el último valor duplicado si es impar
                        }

                        return $intervalos;
                    })->flatten()->values()->all();
                }
                sort($reservations);
                return $reservations;
            }
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el Professionalservice(professional_reservations_time): " . $e->getMessage());
        }
    }

    public function professional_reservations_time_tottem($branch_id, $professional_id, $day)
    {
        try {
            $data = [
                'branch_id' => $branch_id,
                'professional_id' => $professional_id,
                'data' => $day
            ];
            $nombreDia = ucfirst(strtolower(Carbon::parse($data['data'])->locale('es_ES')->dayName));
            $horario = Schedule::where('branch_id', $data['branch_id'])->where('day', $nombreDia)->first();
            $start_time = Carbon::parse($horario->start_time)->format('H:i');
            $closing_time = Carbon::parse($horario->closing_time)->format('H:i');
            //$closing_time = $horario->closing_time;
            //$startTime = strtotime($start_time);
            $reservations = [];

            $currentDateTime =  Carbon::now();
            if (Carbon::parse($data['data'])->isToday()) {
                $professional = Professional::where('professionals.id', $data['professional_id'])
                    ->whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })
                    ->with(['reservations' => function ($query) use ($data) {
                        $query->whereDate('data', $data['data'])
                              ->orderBy('start_time')
                              ->whereIn('confirmation', [1, 4])
                              ->whereHas('tail', function ($subquery) {
                                  $subquery->where('aleatorie', '!=', 1);
                              });
                    }])->whereIn('state',[1, 2])->join('branch_professional',function ($join) use ($data) {
                        $join->on('professionals.id', '=', 'branch_professional.professional_id')
                            ->where('branch_professional.branch_id', '=', $data['branch_id'])
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
                        'branch_professional.living',
                        'branch_professional.numberRandom'
                    )->first();
                if ($professional == null) {
                    $startTime = Carbon::parse($start_time);
                    //$horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                    $closingTime = Carbon::parse($closing_time);
                    while ($startTime <= $closingTime) {
                        $reservations[] = $startTime->format('H:i');
                        $startTime->addMinutes(10);
                    }
                    sort($reservations);
                    return $reservations;
                } else {
                    if ($professional->reservations->isNotEmpty()) 
                    {
                        $reservations = $professional->reservations->filter(function ($reservation) {
                            // Filtrar las relaciones 'tails' para que 'aleatorio' sea distinto de 1
                            return $reservation->tail && $reservation->tail->aleatorio != 1;
                        })->map(function ($reservation) use ($start_time) {
                            $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                            $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                            $intervalos = [$startFormatted];
                            $startTime = Carbon::parse($startFormatted);

                            $finalTime = Carbon::parse($reservation->final_hour);
                            $finalMinutes = $finalTime->minute;
                            
                            if ($finalMinutes <= 10){
                            $roundedMinutes = '05';
                            }
                         elseif ($finalMinutes <= 20) {
                            
                            $roundedMinutes = '15';
                            }
                         elseif ($finalMinutes <= 30) {
                            $roundedMinutes = '25';
                        }elseif ($finalMinutes <= 40) {
                            $roundedMinutes = '35';
                        } 
                        elseif ($finalMinutes <= 50) {
                            $roundedMinutes = '45';
                        } 
                        elseif ($finalMinutes <= 59) {
                            $roundedMinutes = '55';
                        }
                        else {
                            $finalTime->addHour();
                            $roundedMinutes = '00';
                        }

                            $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                            $finalTime = Carbon::parse($finalFormatted);
                            $horaActual = Carbon::now();
                            $horaActualMas2Horas = $horaActual->copy()->addHours(1);

                            // Si $finalTime es menor que la hora actual más 2 horas, asignar la hora actual más 2 horas a $finalTime
                            if ($finalTime->lessThan($horaActualMas2Horas)) {
                                $finalTime = $horaActualMas2Horas;
                            }
                            while ($startTime->addMinutes(10) <= $finalTime) {
                                $intervalos[] = $startTime->format('H:i');
                            }

                            if (count($intervalos) % 2 !== 0) {
                                $intervalos[] = end($intervalos); // Agrega el último valor duplicado si es impar
                            }

                            return $intervalos;
                        })->flatten()->values()->all();
                        $firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                        $horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                        if ($horaActualMas2Horas->lessThan($firstReservationStartTime)) {
                            $startTime = Carbon::parse($start_time);
                            while ($startTime <= $horaActualMas2Horas) {
                                $reservations[] = $startTime->format('H:i');
                                $startTime->addMinutes(10);
                            }

                            if (count($reservations) % 2 !== 0) {
                                $reservations[] = end($reservations); // Agrega el último valor duplicado si es impar
                            }
                        } else {
                            $startTime = Carbon::parse($start_time);
                            while ($startTime <= $horaActualMas2Horas) {
                                $formattedTime = $startTime->format('H:i');
                                // Solo agrega el tiempo si no está en el array
                                if (!in_array($formattedTime, $reservations)) {
                                    $reservations[] = $formattedTime;
                                }

                                $startTime->addMinutes(10);
                            }

                            if (count($reservations) % 2 !== 0) {
                                $reservations[] = end($reservations); // Agrega el último valor duplicado si es impar
                            }
                        }

                        sort($reservations);
                        return $reservations;
                    } else {
                        $startTime = Carbon::parse($start_time);
                        $horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                        $closingTime = Carbon::parse($horaActualMas2Horas);
                        while ($startTime <= $closingTime) {
                            $reservations[] = $startTime->format('H:i');
                            $startTime->addMinutes(10);
                        }
                        sort($reservations);
                        return $reservations;
                    }
                }
            } else {
                $professional = Professional::where('id', $data['professional_id'])
                    ->whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })
                    ->with(['reservations' => function ($query) use ($data) {
                        $query->whereDate('data', $data['data'])->orderBy('start_time')->whereIn('confirmation', [1,4]);
                    }])
                    ->first();
                if ($professional && $professional->reservations->isNotEmpty()) {
                    $reservations = $professional->reservations->map(function ($reservation) {
                        $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                        $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                        $intervalos = [$startFormatted];
                        $startTime = Carbon::parse($startFormatted);

                        $finalTime = Carbon::parse($reservation->final_hour);
                        $finalMinutes = $finalTime->minute;

                         if ($finalMinutes <= 10){
                            $roundedMinutes = '05';
                            }
                         elseif ($finalMinutes <= 20) {
                            
                            $roundedMinutes = '15';
                            }
                         elseif ($finalMinutes <= 30) {
                            $roundedMinutes = '25';
                        }elseif ($finalMinutes <= 40) {
                            $roundedMinutes = '35';
                        } 
                        elseif ($finalMinutes <= 50) {
                            $roundedMinutes = '45';
                        } 
                        elseif ($finalMinutes <= 59) {
                            $roundedMinutes = '55';
                        }
                        else {
                            $finalTime->addHour();
                            $roundedMinutes = '00';
                        }


                        $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                        $finalTime = Carbon::parse($finalFormatted);
                        $horaActual = Carbon::now();
                        /*if ($finalTime->lessThan($horaActual)) {
                            $finalTime = $horaActual;
                        }*/
                        // Agregar las horas intermedias de 15 en 15 minutos
                        while ($startTime->addMinutes(10) <= $finalTime) {
                            $intervalos[] = $startTime->format('H:i');
                        }

                        if (count($intervalos) % 2 !== 0) {
                            $intervalos[] = end($intervalos); // Agrega el último valor duplicado si es impar
                        }

                        return $intervalos;
                    })->flatten()->values()->all();
                }
                sort($reservations);
                return $reservations;
            }
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el Professionalservice(professional_reservations_time): " . $e->getMessage());
        }
    }

    ///nuevo metodo
    function encontrarHoraDisponible($timeService, $arrayIntervalos, $startTime)
    {
        // Convertir la hora actual a un objeto Carbon para facilitar la comparación
        //$horaActualCarbon = Carbon::createFromFormat('H:i', $horaActual);
        $horaActual = Carbon::now();

        if ($horaActual < Carbon::parse($startTime)) {
            $horaActual = Carbon::parse($startTime);
        }

        $i = 0;
        foreach ($arrayIntervalos as $key => $intervalo) {
            $auxActual = Carbon::now();
            $horaInicioActual = Carbon::createFromFormat('H:i:s', $intervalo['start_time']);
            $horaFinActual = Carbon::createFromFormat('H:i:s', $intervalo['final_hour']);
            if ($horaActual > $horaInicioActual && $horaActual > $horaFinActual) {
                continue;
            } else {
                $nuevaHora = $horaActual->copy()->addMinutes($timeService);
                if ($nuevaHora <= $horaInicioActual) {
                    return $horaActual->format('H:i');
                } else {
                    if ($horaActual->between($horaInicioActual, $horaFinActual)) {
                        $horaActual = $horaFinActual;
                        $i++;
                        continue;
                    } else {
                        $horaActual = $horaFinActual;
                    }
                }
            }
        } //endForm   
        return $horaActual->format('H:i');
        
    }

    //todo ESTA DE AQUI ES NUEVA, NUEVO METODO DE ENCONTRAR HORA DISPONIBLE RLP

    public function branch_professionals_serviceNew($branch_id, $services)
    {
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
            // Comprobar si la hora inicial está dentro del rango actual
            if ($horaInicialMinutos < $horaIniMinutos) {
                $resultHoras = $horaIniMinutos - $horaInicialMinutos;
                if ($resultHoras >= $tiempoServicio) {
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
                $intervalos = [$startFormatted];
                $startTime = Carbon::parse($startFormatted);
                $finalFormatted = Carbon::parse($reservation->final_hour)->format('H:i');
                $finalTime = Carbon::parse($finalFormatted);
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
        $fecha = Carbon::now();
        if ($data['charge'] == 'Barbero' || $data['charge'] == 'Barbero y Encargado') {
            $professional = Professional::where('id', $data['professional_id'])->first();
            $cars = Car::whereHas('reservation', function ($query) use ($data, $fecha) {
                $query->where('branch_id', $data['branch_id'])->whereDate('data', $fecha);
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
        $totalPrice = 0;
        $totalPercentWin = 0;
        foreach ($ServiceEspecial as $order) {
            if ($order->meta == 1) {
                $totalPrice += $order->price;
            } elseif ($order->meta == 0) {
                $totalPercentWin += $order->percent_win;
            }
        }
            $totalAmount = number_format(round($totalPrice + $totalPercentWin, 2), 2);
            //$winProfessional = $orderServ->sum('percent_win');
            $retentionPorcent = $professional->retention ? $professional->retention : 0;
            $winTips = intval($cars->sum('tip') * 0.80);
            $payments = ProfessionalPayment::where('branch_id', $data['branch_id'])
                ->where('professional_id', $professional->id)
                ->whereDate('date', $fecha)
                ->whereIn('type', ['Bono convivencias', 'Bono productos', 'Bono servicios'])
                ->get();
                $convivencia = 0;
                $service = 0;
                $bonos = 0;
                foreach ($payments as $payment) {
                    if ($payment->type == 'Bono convivencias') {
                            $convivencia += $payment->amount;
                        } 
                    if($payment->type == 'Bono servicios'){
                        $service += $payment->amount;
                    }
                    $bonos += $payment->amount;
                }
                $totalRetention = Retention::where('branch_id', $data['branch_id'])
                ->where('professional_id', $professional->id)->whereDate('data', $fecha)->sum('retention');
                $winProfessional = $cars->sum(function ($car) {
                    return $car->orders
                        ->where('is_product', 0)  // Filtrar donde is_product sea igual a 0
                        ->sum(function ($order) {
                            return ($order->meta == 1 ? $order->price : 0) + ($order->meta == 0 ? $order->percent_win : 0);
                        });
                });
                $retentionProfess = $retentionPorcent ? $winProfessional * $retentionPorcent / 100 : $winProfessional;
                $amountConvivencia = $winProfessional - $retentionProfess;
                $amountService = $amountConvivencia - $convivencia;
            return $result = [
                'Clientes Atendidos' => $totalClients,
                'Clientes Aleatorios' => $cars->where('select_professional', 0)->count(),
                'Clientes Seleccionados' => $cars->where('select_professional', 1)->count(),
                'Productos Vendidos' => $products,
                'Cantidad de Servicios' => $services,
                // 'Servicios Regulares' => $ServiceRegular->count(),
                // 'Servicios Especiales' => $ServiceEspecial->count(),
                // 'Monto Servicios Especial' => $totalAmount,
                'Propina' => number_format(round($cars->sum('tip'), 2), 2),
                'Propina 80%' => number_format(round($winTips, 2), 2),
                'Total Servicios' => number_format(round($amountGenral, 2), 2), //suma productos y servicios
                'Retención' => $totalRetention ? number_format(round($totalRetention, 2), 2) : (number_format(round($retentionProfess, 2), 2)), //monto generado percent_win % calculando la retención
                //'Gan.Serv C/Convivencias' => number_format(round($amountConvivencia, 2), 2),
                'Gan.Serv S/Convivencias' => number_format(round($amountService, 2), 2),
                'Bonos' => number_format(round($bonos, 2), 2),
                'Total' => number_format(round($amountService + $winTips + $bonos, 2), 2), //ganancia barbero - retencion + propinas 80%
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
        $totalPrice = 0;
        $totalPercentWin = 0;
        foreach ($ServiceEspecial as $order) {
            if ($order->meta == 1) {
                $totalPrice += $order->price;
            } elseif ($order->meta == 0) {
                $totalPercentWin += $order->percent_win;
            }
        }
        $totalAmount = number_format(round($totalPrice + $totalPercentWin, 2), 2);
            //$winProfessional = $orderServ->sum('percent_win');
            $retentionPorcent = $professional->retention ? $professional->retention : 0;
            $winTips = intval($cars->sum('tip') * 0.80);
            $payments = ProfessionalPayment::where('branch_id', $data['branch_id'])
                ->where('professional_id', $professional->id)
                ->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)
                ->whereIn('type', ['Bono convivencias', 'Bono productos', 'Bono servicios'])
                ->get();
                $convivencia = 0;
                $service = 0;
                $bonos = 0;
                foreach ($payments as $payment) {
                    if ($payment->type == 'Bono convivencias') {
                            $convivencia += $payment->amount;
                        } 
                    if($payment->type == 'Bono servicios'){
                        $service += $payment->amount;
                    }
                    $bonos += $payment->amount;
                }
                $totalRetention = Retention::where('branch_id', $data['branch_id'])
                ->where('professional_id', $professional->id)->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate)->sum('retention');
                $winProfessional = $cars->sum(function ($car) {
                    return $car->orders
                        ->where('is_product', 0)  // Filtrar donde is_product sea igual a 0
                        ->sum(function ($order) {
                            return ($order->meta == 1 ? $order->price : 0) + ($order->meta == 0 ? $order->percent_win : 0);
                        });
                });
                $retentionProfess = $retentionPorcent ? $winProfessional * $retentionPorcent / 100 : $winProfessional;
                $amountConvivencia = $winProfessional - $retentionProfess;
                $amountService = $amountConvivencia - $convivencia;
            return $result = [
                'Clientes Atendidos' => $totalClients,
                'Clientes Aleatorios' => $cars->where('select_professional', 0)->count(),
                'Clientes Seleccionados' => $cars->where('select_professional', 1)->count(),
                'Productos Vendidos' => $products,
                'Cantidad de Servicios' => $services,
                // 'Servicios Regulares' => $ServiceRegular->count(),
                // 'Servicios Especiales' => $ServiceEspecial->count(),
                // 'Monto Servicios Especial' => $totalAmount,
                'Propina' => number_format(round($cars->sum('tip'), 2), 2),
                'Propina 80%' => number_format(round($winTips, 2), 2),
                'Total Servicios' => number_format(round($amountGenral, 2), 2), //suma productos y servicios
                'Retención' => $totalRetention ? number_format(round($totalRetention, 2), 2) : (number_format(round($retentionProfess, 2), 2)), //monto generado percent_win % calculando la retención
                //'Gan.Serv C/Convivencias' => number_format(round($amountConvivencia, 2), 2),
                'Gan.Serv S/Convivencias' => number_format(round($amountService, 2), 2),
                'Bonos' => number_format(round($bonos, 2), 2),
                'Total' => number_format(round($amountService + $winTips + $bonos, 2), 2), //ganancia barbero - retencion + propinas 80%
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
            $totalPrice = 0;
        $totalPercentWin = 0;
        foreach ($ServiceEspecial as $order) {
            if ($order->meta == 1) {
                $totalPrice += $order->price;
            } elseif ($order->meta == 0) {
                $totalPercentWin += $order->percent_win;
            }
        }
            $totalAmount = number_format(round($totalPrice + $totalPercentWin, 2), 2);
            //$winProfessional = $orderServ->sum('percent_win');
            $retentionPorcent = $professional->retention ? $professional->retention : 0;
            $winTips = intval($cars->sum('tip') * 0.80);
            $payments = ProfessionalPayment::where('branch_id', $data['branch_id'])
                ->where('professional_id', $professional->id)
                ->whereMonth('date', $mes)->whereYear('date', $year)
                ->whereIn('type', ['Bono convivencias', 'Bono productos', 'Bono servicios'])
                ->get();
                $convivencia = 0;
                $service = 0;
                $bonos = 0;
                foreach ($payments as $payment) {
                    if ($payment->type == 'Bono convivencias') {
                            $convivencia += $payment->amount;
                        } 
                    if($payment->type == 'Bono servicios'){
                        $service += $payment->amount;
                    }
                    $bonos += $payment->amount;
                }
                $totalRetention = Retention::where('branch_id', $data['branch_id'])
                ->where('professional_id', $professional->id)->whereMonth('data', $mes)->whereYear('data', $year)->sum('retention');
                $winProfessional = $cars->sum(function ($car) {
                    return $car->orders
                        ->where('is_product', 0)  // Filtrar donde is_product sea igual a 0
                        ->sum(function ($order) {
                            return ($order->meta == 1 ? $order->price : 0) + ($order->meta == 0 ? $order->percent_win : 0);
                        });
                });
                $retentionProfess = $retentionPorcent ? $winProfessional * $retentionPorcent / 100 : $winProfessional;
                $amountConvivencia = $winProfessional - $retentionProfess;
                $amountService = $amountConvivencia - $convivencia;
            return $result = [
                'Clientes Atendidos' => $totalClients,
                'Clientes Aleatorios' => $cars->where('select_professional', 0)->count(),
                'Clientes Seleccionados' => $cars->where('select_professional', 1)->count(),
                'Productos Vendidos' => $products,
                'Cantidad de Servicios' => $services,
                // 'Servicios Regulares' => $ServiceRegular->count(),
                // 'Servicios Especiales' => $ServiceEspecial->count(),
                // 'Monto Servicios Especial' => $totalAmount,
                'Propina' => number_format(round($cars->sum('tip'), 2), 2),
                'Propina 80%' => number_format(round($winTips, 2), 2),
                'Total Servicios' => number_format(round($amountGenral, 2), 2), //suma productos y servicios
                'Retención' => $totalRetention ? number_format(round($totalRetention, 2), 2) : (number_format(round($retentionProfess, 2), 2)), //monto generado percent_win % calculando la retención
                //'Gan.Serv C/Convivencias' => number_format(round($amountConvivencia, 2), 2),
                'Gan.Serv S/Convivencias' => number_format(round($amountService, 2), 2),
                'Bonos' => number_format(round($bonos, 2), 2),
                'Total' => number_format(round($amountService + $winTips + $bonos, 2), 2), //ganancia barbero - retencion + propinas 80%
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
    
    

    public function professionals_state($branch_id, $reservation_id)//cambio 31-08-24
    {
        try{
            $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $reservation = Reservation::find($reservation_id);
        $orders = Order::where('car_id', $reservation->car_id)->get()->pluck('branch_service_professional_id');
        $startTime = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
        $branchService = BranchServiceProfessional::whereIn('id', $orders)->get()->pluck('branch_service_id');
        $total_timeMin = $this->convertirHoraAMinutos($reservation->total_time);
        //$branchId = 1; // Reemplaza con el ID de la sucursal que estás buscando
        $currentTime = Carbon::now();

        $professionals = Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->where('arrival', '!=', NULL);
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
            'professionals.state',
            'professionals.image_url',
            'branch_professional.arrival',
            'branch_professional.living',
            )->orderBy('branch_professional.living', 'asc')
            ->orderBy('branch_professional.arrival', 'asc')
            ->get();

            $returnedProfessionals = $professionals->map(function($professional) use ($branch_id, $startTime) {
                $reservation = Reservation::where('branch_id', $branch_id)
                    ->where('confirmation', 2)
                    ->whereHas('car.clientProfessional', function ($query) use ($professional) {
                        $query->where('professional_id', $professional->id);
                    })->orderByDesc('finished_at')
                    ->whereDate('data', Carbon::now())
                    ->first();
                    if ($reservation != null && $professional->end_time == null) {
                        $professional->disponible = $reservation->finished_at->format('H:i:s');
                    }
                    else if ($professional->end_time !== null && Carbon::parse($professional->end_time)->toDateString() == Carbon::now()->toDateString()) {
                        // Convertir end_time y el tiempo de la última reserva en instancias de Carbon
                        $endTime = Carbon::parse($professional->end_time);
                        $lastReservationTime = Carbon::parse($reservation->finished_at);
        
                        // Comparar y decidir el tiempo que se asignará a `disponible`
                        if ($lastReservationTime->gt($endTime)) {
                            $professional->disponible = $lastReservationTime->format('H:i:s');
                        } else {
                            $professional->disponible = $endTime->format('H:i:s');
                        }
                    }else {
                        $record = Record::where('professional_id', $professional->id)->where('branch_id', $branch_id)->whereDate('start_time', Carbon::now())->orderByDesc('start_time')->first();
                        if ($record != null) {
                            $professional->disponible = Carbon::parse($record->start_time)->format('H:i:s');
                        }
                        else {
                            $professional->disponible = Carbon::parse($startTime)->format('H:i:s');
                        }
                        
                    }            
                return $professional;
            });
            
            $returnedProfessionals = collect($returnedProfessionals)->sortBy([
                ['disponible', 'asc'],
                ['living', 'asc'],
                ['arrival', 'asc']
            ])->values();

        $professionalFree = [];
        // Convertir el campo telefono a string
        // Iterar sobre los profesionales
        foreach ($returnedProfessionals as $professional) {
            // Convertir el campo teléfono a string
            $professional->phone = (string) $professional->phone;
            $professionalCharge = Professional::where('id', $professional->id)->first();
            $charge = $professionalCharge->charge->name;
        
            // Verificar la disponibilidad del profesional en su lugar de trabajo
            $workplaceProfessional = ProfessionalWorkPlace::where('professional_id', $professional->id)
                ->whereDate('data', Carbon::now())
                ->where('state', 1)
                ->whereHas('workplace', function ($query) use ($branch_id) {
                    $query->where('busy', 1)->where('branch_id', $branch_id);
                })->first();       
        
            $current_date = Carbon::now();
            $nuevaHoraInicio = Carbon::now();
       
            if ($workplaceProfessional) {
                $professional->position = $workplaceProfessional->workplace->name;
                $professional->charge_id = $charge;
                $attended = $professional->reservations()
                    ->where('branch_id', $reservation->branch_id)
                    ->where('confirmation', 4)
                    ->whereDate('data', Carbon::now())
                    ->whereHas('tail', function ($subquery) {
                        $subquery->whereIn('attended', [1, 11, 111, 4, 5, 33]);
                    })
                    ->get();
                if ($attended->isNotEmpty()) {
                    
                } else {
                    $reservations = $professional->reservations()
                        ->where('branch_id', $reservation->branch_id)
                        ->where('confirmation', 4)
                        ->whereDate('data', Carbon::now())
                        ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1);
                        })
                        ->orderBy('start_time')
                        ->get();
                    if ($reservations->isEmpty()) {
                        $professionalFree[] = $professional;
                    } else {
                        foreach ($reservations as $reservation1) {
                            // Comprobación de start_time y attended
                            $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
                            $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));
        
                            if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin && $reservation1->confirmation !=4) {
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
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el Professionalservice(professionals_state): " . $e->getMessage());
        }
    }

    public function professionals_state_tottem($branch_id, $services)//cambio 31-08-24
    {
        try{
        $total_timeMin = Service::whereIn('id', $services)->get()->sum('duration_service');
        $currentTime = Carbon::now();

        $professionals = Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('branchServices', function ($query) use ($services, $branch_id) {
            $query->whereIn('service_id', $services)->where('branch_id', $branch_id);
        }, '=', count($services))->whereHas('charge', function ($query) {
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
            // Convertir el campo teléfono a string
            $professional->phone = (string) $professional->phone;
        
            // Verificar la disponibilidad del profesional en su lugar de trabajo
            $workplaceProfessional = ProfessionalWorkPlace::where('professional_id', $professional->id)
                ->whereDate('data', Carbon::now())
                ->where('state', 1)
                ->whereHas('workplace', function ($query) use ($branch_id) {
                    $query->where('busy', 1)->where('branch_id', $branch_id);
                })->first();
        
            $current_date = Carbon::now();
            $nuevaHoraInicio = Carbon::now();
        
            if ($workplaceProfessional) {
                $professional->position = $workplaceProfessional->workplace->name;
                $professional->charge_id = $professional->charge->name;
        
                $attended = $professional->reservations()
                    ->where('branch_id', $branch_id)
                    ->whereIn('confirmation', [1, 4])
                    ->whereDate('data', Carbon::now())
                    ->whereHas('tail', function ($subquery) {
                        $subquery->whereIn('attended', [1, 11, 111, 4, 5, 33]);
                    })
                    ->get();
        
                if ($attended->isNotEmpty()) {
                    
                } else {
                    $reservations = $professional->reservations()
                        ->where('branch_id', $branch_id)
                        ->whereIn('confirmation', [1, 4])
                        ->whereDate('data', Carbon::now())
                        ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1);
                        })
                        ->orderBy('start_time')
                        ->get();
        
                    if ($reservations->isEmpty()) {
                        $professional->start_time = Carbon::now()->format('H:i');
                        $professionalFree[] = $professional;
                    } else {
                        foreach ($reservations as $reservation1) {
                            $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
                            $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));
        
                            if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin && $reservation1->confirmation !=4) {
                                $professional->start_time = Carbon::now()->format('H:i');
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
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el Professionalservice(professionals_state_tottem): " . $e->getMessage());
        }
    }
        
    public function professionals_state1($branch_id, $reservation_id)//cambio 31-08-24
    {
        try{
            $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
        $reservation = Reservation::find($reservation_id);
        $orders = Order::where('car_id', $reservation->car_id)->get()->pluck('branch_service_professional_id');
        $startTime = Schedule::where('branch_id', $branch_id)->where('day', $nombreDia)->value('start_time');
        $branchService = BranchServiceProfessional::whereIn('id', $orders)->get()->pluck('branch_service_id');
        $total_timeMin = $this->convertirHoraAMinutos($reservation->total_time);
        //$branchId = 1; // Reemplaza con el ID de la sucursal que estás buscando
        $currentTime = Carbon::now();

        $professionals = $this->getAvailableProfessionals($branch_id, $branchService);
        $professionalsFree = $this->getFreeProfessionals($professionals, $branch_id, $total_timeMin);

            return $this->getReturnedProfessionals($professionalsFree, $branch_id, $startTime);

                   } catch (Exception $e) {
                    // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
                    throw new \RuntimeException("Error al ejecutar el Professionalservice(professionals_state): " . $e->getMessage());
                }
    }
	
	private function getAvailableProfessionals($branch_id, $branchService)
	{
		return Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->where('arrival', '!=', NULL);
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
            'professionals.state',
            'professionals.image_url',
            'branch_professional.arrival',
            'branch_professional.living',
            )->orderBy('branch_professional.living', 'asc')
            ->orderBy('branch_professional.arrival', 'asc')
            ->get();		
	}
	
	private function getFreeProfessionals($professionals, $branch_id, $total_timeMin)
	{
		 $professionalFree = [];
        // Convertir el campo telefono a string
        // Iterar sobre los profesionales
        $nuevaHoraInicio = Carbon::now();
        foreach ($professionals as $professional) {
            $professionalCharge = Professional::where('id', $professional->id)->first();
            $charge = $professionalCharge->charge->name;
            $professional->charge_id = $charge;
                $attended = $professional->reservations()
                    ->where('branch_id', $branch_id)
                    ->where('confirmation', 4)
                    ->whereDate('data', Carbon::now())
                    ->whereHas('tail', function ($subquery) {
                        $subquery->whereIn('attended', [1, 11, 111, 4, 5, 33]);
                    })
                    ->get();
                if ($attended->isNotEmpty()) {
                    
                } else {
                      
                    $reservations = $professional->reservations()
                        ->where('branch_id', $branch_id)
                        ->where('confirmation', 4)
                        ->whereDate('data', Carbon::now())
                        ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1);
                        })
                        ->orderBy('start_time')
                        ->get();
                    if ($reservations->isEmpty()) {
                        $professionalFree[] = $professional;
                    } else {
                        foreach ($reservations as $reservation1) {
                            $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
                            $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));
        
                            if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin && $reservation1->confirmation !=4) {
                                $professionalFree[] = $professional;
                                break;
                            }else{
                                break;
                            }
                        }
                    }
                }
        }
                return $professionalFree;
		
	}
	
	
	private function getReturnedProfessionals($professionals, $branch_id, $startTime)
	{
		$returnedProfessionals = collect($professionals)->map(function($professional) use ($branch_id, $startTime) {
                $reservation = Reservation::where('branch_id', $branch_id)
                    ->where('confirmation', 2)
                    ->whereHas('car.clientProfessional', function ($query) use ($professional) {
                        $query->where('professional_id', $professional->id);
                    })->orderByDesc('finished_at')
                    ->whereDate('data', Carbon::now())
                    ->first();
                    if ($reservation != null && $professional->end_time == null) {
                        $professional->disponible = $reservation->finished_at->format('H:i:s');
                    }
                    else if ($professional->end_time !== null && Carbon::parse($professional->end_time)->toDateString() == Carbon::now()->toDateString()) {
                        // Convertir end_time y el tiempo de la última reserva en instancias de Carbon
                        $endTime = Carbon::parse($professional->end_time);
                        $lastReservationTime = Carbon::parse($reservation->finished_at);
        
                        // Comparar y decidir el tiempo que se asignará a `disponible`
                        if ($lastReservationTime->gt($endTime)) {
                            $professional->disponible = $lastReservationTime->format('H:i:s');
                        } else {
                            $professional->disponible = $endTime->format('H:i:s');
                        }
                    }else {
                        $record = Record::where('professional_id', $professional->id)->where('branch_id', $branch_id)->whereDate('start_time', Carbon::now())->orderByDesc('start_time')->first();
                        if ($record != null) {
                            $professional->disponible = Carbon::parse($record->start_time)->format('H:i:s');
                        }
                        else {
                            $professional->disponible = Carbon::parse($startTime)->format('H:i:s');
                        }
                        
                    }            
                return $professional;
            });
            
            return $returnedProfessionals->sortBy([
                ['disponible', 'asc'],
                ['living', 'asc'],
                ['arrival', 'asc']
            ])->values();
	}
	

}
