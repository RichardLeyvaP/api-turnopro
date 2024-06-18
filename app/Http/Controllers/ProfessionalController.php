<?php

namespace App\Http\Controllers;

use App\Models\BranchProfessional;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Professional;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ProfessionalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProfessionalController extends Controller
{

    private ProfessionalService $professionalService;

    public function __construct(ProfessionalService $professionalService)
    {
        $this->professionalService = $professionalService;
    }


    public function index()
    {
        try {
            $now = Carbon::now();
            $professionals = Professional::with('user', 'charge')->get()->map(function ($professional) use ($now) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name,
                    'surname' => $professional->surname,
                    'second_surname' => $professional->second_surname,
                    'fullName' => $professional->fullName,
                    'email' => $professional->email,
                    'phone' => $professional->phone,
                    'user_id' => $professional->user_id,
                    'state' => $professional->state,
                    'image_url' => $professional->image_url.'?$'.$now,
                    'charge_id' => $professional->charge_id,
                    'user' => $professional->user->name,
                    'charge' => $professional->charge->name,
                    'retention' => $professional->retention,
                ];
            });
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las professionales"], 500);
        }
    }
    /*public function index()
    {
        try {
            return response()->json(['professionals' => Professional::with('user', 'charge')->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las professionales"], 500);
        }
    }*/
    public function show_autocomplete_Notin(Request $request)
    {
        try {
            Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branchProfessionals = BranchProfessional::where('branch_id', $data['branch_id'])->get()->pluck('professional_id');
            $professionals = Professional::whereNotin('id', $branchProfessionals)->with('charge')->get()->map(function ($professional) {
                return [
                    'id' => intval($professional->id),
                    'name' => $professional->name,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->charge->name

                ];
            });
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    public function show_autocomplete(Request $request)
    {
        try {
            $professionals = Professional::with('user', 'charge')->get()->map(function ($professional) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->charge->name

                ];
            });
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar el professional"], 500);
        }
    }

    public function show_autocomplete_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->get()->map(function ($professional) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url

                ];
            });
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar el professional"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['professional' => Professional::with('user', 'charge')->find($professionals_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    public function show_apk(Request $request)
    {
        try {
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $professional = Professional::where('id', $professionals_data['id'])->first();
            if ($professional !== null)
                return $professional->state;
            else
                return -1;
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    /*public function professional_reservations_time(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $nombreDia = ucfirst(strtolower(Carbon::parse($data['data'])->locale('es_ES')->dayName));
            $horario = Schedule::where('branch_id', $data['branch_id'])->where('day', $nombreDia)->get();
            $start_time = $horario->start_time;
            $closing_time = $horario->closing_time;
            //$startTime = strtotime($start_time);
            $reservations = [];
            /*$professional = Professional::where('id', $data['professional_id'])
                ->whereHas('branches', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                ->with(['reservations' => function ($query) use ($data) {
                    $query->whereDate('data', $data['data'])->orderBy('start_time')->whereIn('confirmation', [1, 4]);
                }])
                ->first();*/
                ///para saber si esta trabajando
                /*$professional = Professional::where('id', $data['professional_id'])
                ->whereHas('branches', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                ->with(['reservations' => function ($query) use ($data) {
                    $query->whereDate('data', $data['data'])->orderBy('start_time')->whereIn('confirmation', [1, 4]);
                }])->where('state', 1)->whereHas('records', function ($query){
                    $query->whereDate('start_time', Carbon::now());
                })->select('professionals.id', 'professionals.name', 'professionals.surname', 'professionals.second_surname', 'professionals.email', 'professionals.phone', 'professionals.charge_id', 'professionals.state', 'professionals.image_url', 
                DB::raw('(SELECT MAX(start_time) FROM records WHERE records.professional_id = professionals.id AND DATE(records.start_time) = CURDATE()) AS start_time'))->orderBy('start_time', 'asc')->first();
                ///end trabajando
            $currentDateTime =  Carbon::now();
            // Verificar si hay reservas para este profesional y día
            if ($professional && $professional->reservations->isNotEmpty()) {
                // Obtener las reservas y mapearlas para obtener los intervalos de tiempo
                if (Carbon::parse($data['data'])->isToday()) {
                    $reservations = $professional->reservations->whereIn('confirmation', [1, 4])->map(function ($reservation) {
                        $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                        $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                        $intervalos = [$startFormatted];
                        $startTime = Carbon::parse($startFormatted);
                        //$finalFormatted = Carbon::parse($reservation->final_hour)->format('H:') . ($finalMinutes <= 15 ? '15' : ($finalMinutes <= 30 ? '30' : ($finalMinutes <= 45 ? '45' : '00')));
                                                
                        $finalTime = Carbon::parse($reservation->final_hour);
                        $finalMinutes = $finalTime->minute;

                        if ($finalMinutes <= 15) {
                            $roundedMinutes = '15';
                        } elseif ($finalMinutes <= 30) {
                            $roundedMinutes = '30';
                        } elseif ($finalMinutes <= 45) {
                            $roundedMinutes = '45';
                        } else {
                            $finalTime->addHour();
                            $roundedMinutes = '00';
                        }

                        $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                        $finalTime = Carbon::parse($finalFormatted);
                        $horaActual = Carbon::now();
                        $horaActualMas2Horas = $horaActual->copy()->addHours(2);

                        // Si $finalTime es menor que la hora actual más 2 horas, asignar la hora actual más 2 horas a $finalTime
                        if ($finalTime->lessThan($horaActualMas2Horas)) {
                            $finalTime = $horaActualMas2Horas;
                        }
                        while ($startTime->addMinutes(15) <= $finalTime) {
                            $intervalos[] = $startTime->format('H:i');
                        }
                        /*if ($finalTime->lessThan($horaActual)) {
                            // $finalTime es menor que la hora actual, asignar la hora actual a $finalTime
                            $finalTime = $horaActual;
                        }
                        // Agregar las horas intermedias de 15 en 15 minutos
                        while ($startTime->addMinutes(15) <= $finalTime) {
                            $intervalos[] = $startTime->format('H:i');
                        }*/

                        /*return $intervalos;
                    })->flatten()->values()->all();
                } else {
                    $reservations = $professional->reservations->map(function ($reservation) {
                        $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                        $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                        $intervalos = [$startFormatted];
                        $startTime = Carbon::parse($startFormatted);
                        //$finalFormatted = Carbon::parse($reservation->final_hour)->format('H:') . ($finalMinutes <= 15 ? '15' : ($finalMinutes <= 30 ? '30' : ($finalMinutes <= 45 ? '45' : '00')));
                                                
                        $finalTime = Carbon::parse($reservation->final_hour);
                        $finalMinutes = $finalTime->minute;

                        if ($finalMinutes <= 15) {
                            $roundedMinutes = '15';
                        } elseif ($finalMinutes <= 30) {
                            $roundedMinutes = '30';
                        } elseif ($finalMinutes <= 45) {
                            $roundedMinutes = '45';
                        } else {
                            $finalTime->addHour();
                            $roundedMinutes = '00';
                        }

                        $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                        $finalTime = Carbon::parse($finalFormatted);
                        $horaActual = Carbon::now();
                        if ($finalTime->lessThan($horaActual)) {
                            // $finalTime es menor que la hora actual, asignar la hora actual a $finalTime
                            $finalTime = $horaActual;
                        }
                        // Agregar las horas intermedias de 15 en 15 minutos
                        while ($startTime->addMinutes(15) <= $finalTime) {
                            $intervalos[] = $startTime->format('H:i');
                        }

                        return $intervalos;
                    })->flatten()->values()->all();
                }

                if (Carbon::parse($data['data'])->isToday()) {
                    // Verificar si la hora actual es menor que el primer start_time de las reservas del día
                    $firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                    if ($currentDateTime->lessThan($firstReservationStartTime)) {
                        $startTime = Carbon::parse($start_time);
                        while ($startTime <= $currentDateTime) {
                            $reservations[] = $startTime->format('H:i');
                            $startTime->addMinutes(15);
                        }
                    } else {
                        $startTime = Carbon::parse($start_time);
                        while ($startTime <= $currentDateTime) {
                            $reservations[] = $startTime->format('H:i');
                            $startTime->addMinutes(15);
                        }
                    }
                }
            } else {
                if (Carbon::parse($data['data'])->isToday()) {
                    // Verificar si la hora actual es menor que el primer start_time de las reservas del día
                    //$firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                    //if ($currentDateTime->lessThan($firstReservationStartTime)) {
                    $startTime = Carbon::parse($start_time);
                    while ($startTime <= $currentDateTime) {
                        $reservations[] = $startTime->format('H:i');
                        $startTime->addMinutes(15);
                    }
                    //}
                }
                //$reservations = [];
            }
            sort($reservations);
            return response()->json(['reservations' => $reservations], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los profesionales"], 500);
        }
    }*/

    public function professional_reservations_time(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $nombreDia = ucfirst(strtolower(Carbon::parse($data['data'])->locale('es_ES')->dayName));
            $horario = Schedule::where('branch_id', $data['branch_id'])->where('day', $nombreDia)->first();
            $start_time = Carbon::parse($horario->start_time)->format('H:i');
            $closing_time = Carbon::parse($horario->closing_time)->format('H:i');
            //$closing_time = $horario->closing_time;
            //$startTime = strtotime($start_time);
            $reservations = [];
            
            $currentDateTime =  Carbon::now();
            if (Carbon::parse($data['data'])->isToday()) {
                $professional = Professional::where('id', $data['professional_id'])
                            ->whereHas('branches', function ($query) use ($data) {
                                $query->where('branch_id', $data['branch_id']);
                            })
                            ->with(['reservations' => function ($query) use ($data) {
                                $query->whereDate('data', $data['data'])->orderBy('start_time')->whereIn('confirmation', [1, 4]);
                            }])->where('state', 1)->whereHas('records', function ($query){
                                $query->whereDate('start_time', Carbon::now());
                            })->select('professionals.id', 'professionals.name', 'professionals.surname', 'professionals.second_surname', 'professionals.email', 'professionals.phone', 'professionals.charge_id', 'professionals.state', 'professionals.image_url', 
                            DB::raw('(SELECT MAX(start_time) FROM records WHERE records.professional_id = professionals.id AND DATE(records.start_time) = CURDATE()) AS start_time'))->orderBy('start_time', 'asc')->first();
                            if($professional == null){
                                    $startTime = Carbon::parse($start_time);
                                    //$horaActualMas2Horas = $currentDateTime->copy()->addHours(2);
                                    $closingTime = Carbon::parse($closing_time);
                                    while ($startTime <= $closingTime) {
                                        $reservations[] = $startTime->format('H:i');
                                        $startTime->addMinutes(15);
                                    }
                                    sort($reservations);
                                    return response()->json(['reservations' => $reservations], 200);
                            }
                            else {
                                if ($professional->reservations->isNotEmpty()) {
                                    $reservations = $professional->reservations->whereIn('confirmation', [1, 4])->map(function ($reservation) use ($start_time){
                                        $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                                        $finalMinutes = Carbon::parse($reservation->final_hour)->minute;
                
                                        $intervalos = [$startFormatted];
                                        $startTime = Carbon::parse($startFormatted);
                                                                
                                        $finalTime = Carbon::parse($reservation->final_hour);
                                        $finalMinutes = $finalTime->minute;
                
                                        if ($finalMinutes <= 15) {
                                            $roundedMinutes = '15';
                                        } elseif ($finalMinutes <= 30) {
                                            $roundedMinutes = '30';
                                        } elseif ($finalMinutes <= 45) {
                                            $roundedMinutes = '45';
                                        } else {
                                            $finalTime->addHour();
                                            $roundedMinutes = '00';
                                        }
                
                                        $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                                        $finalTime = Carbon::parse($finalFormatted);
                                        $horaActual = Carbon::now();
                                        $horaActualMas2Horas = $horaActual->copy()->addHours(2);
                
                                        // Si $finalTime es menor que la hora actual más 2 horas, asignar la hora actual más 2 horas a $finalTime
                                        if ($finalTime->lessThan($horaActualMas2Horas)) {
                                            $finalTime = $horaActualMas2Horas;
                                        }
                                        while ($startTime->addMinutes(15) <= $finalTime) {
                                            $intervalos[] = $startTime->format('H:i');
                                        }
                
                                        return $intervalos;
                                    })->flatten()->values()->all();
                                     $firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                                     $horaActualMas2Horas = $currentDateTime->copy()->addHours(2);
                                    if ($horaActualMas2Horas->lessThan($firstReservationStartTime)) {
                                        $startTime = Carbon::parse($start_time);
                                        while ($startTime <= $horaActualMas2Horas) {
                                            $reservations[] = $startTime->format('H:i');
                                            $startTime->addMinutes(15);
                                        }
                                    } else {
                                        $startTime = Carbon::parse($start_time);
                                        while ($startTime <= $horaActualMas2Horas) {
                                            $reservations[] = $startTime->format('H:i');
                                            $startTime->addMinutes(15);
                                        }
                                    }
                                    
                                    sort($reservations);
                                    return response()->json(['reservations' => $reservations], 200);
                                }
                                else{
                                    $startTime = Carbon::parse($start_time);
                                    $horaActualMas2Horas = $currentDateTime->copy()->addHours(2);
                                    $closingTime = Carbon::parse($horaActualMas2Horas);
                                    while ($startTime <= $closingTime) {
                                        $reservations[] = $startTime->format('H:i');
                                        $startTime->addMinutes(15);
                                    }
                                    sort($reservations);
                                    return response()->json(['reservations' => $reservations], 200);
                                }
                            }
                        }else{
                        $professional = Professional::where('id', $data['professional_id'])
                                ->whereHas('branches', function ($query) use ($data) {
                                    $query->where('branch_id', $data['branch_id']);
                                })
                                ->with(['reservations' => function ($query) use ($data) {
                                    $query->whereDate('data', $data['data'])->orderBy('start_time')->whereIn('confirmation', [1, 4]);
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
            
                                    if ($finalMinutes <= 15) {
                                        $roundedMinutes = '15';
                                    } elseif ($finalMinutes <= 30) {
                                        $roundedMinutes = '30';
                                    } elseif ($finalMinutes <= 45) {
                                        $roundedMinutes = '45';
                                    } else {
                                        $finalTime->addHour();
                                        $roundedMinutes = '00';
                                    }
            
                                    $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                                    $finalTime = Carbon::parse($finalFormatted);
                                    $horaActual = Carbon::now();
                                    if ($finalTime->lessThan($horaActual)) {
                                        $finalTime = $horaActual;
                                    }
                                    // Agregar las horas intermedias de 15 en 15 minutos
                                    while ($startTime->addMinutes(15) <= $finalTime) {
                                        $intervalos[] = $startTime->format('H:i');
                                    }
            
                                    return $intervalos;
                                })->flatten()->values()->all();
                            }
                            sort($reservations);
                                return response()->json(['reservations' => $reservations], 200);
            } 
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los profesionales"], 500);
        }
    }

    public function professionals_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $professional = $this->professionalService->professionals_branch($data['branch_id'], $data['professional_id']);
            /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->find($data['professional_id']);
           
           if ($professionals) {
                $date = Carbon::now();
                $dataUser = [];
                $dataUser['id'] = $professionals->id;
                $dataUser['usuario'] = $professionals->name;
                $dataUser['fecha'] = $date->toDateString();
                $dataUser['hora'] = $date->Format('g:i:s A');
                return response()->json(['professional_branch' => $dataUser], 200);
           }*/
            return response()->json(['professional_branch' => $professional], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    public function branch_professionals(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = $this->professionalService->branch_professionals($data['branch_id']);
            /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->get();
           */
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    public function branch_professionals_web(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })/*->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })*/->get()->map(function ($query) use ($now) {
                return [
                    'id' => $query->id,
                    'name' => $query->name,
                    'charge' => $query->charge->name,
                    'image_url' => $query->image_url . '?$' . $now,
                    'email' => $query->email
                ];
            });

            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    public function branch_professionals_cashier(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Cajero (a)');
            })->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'name' => $query->name . ' ' . $query->surname . ' ' . $query->second_surname,
                    'charge' => $query->charge->name
                ];
            });

            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    public function branch_professionals_service(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            $professionals = $this->professionalService->branch_professionals_service($data['branch_id'], $servs);
            /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->get();
           */
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    public function branch_professionals_service1(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            Log::info('$servs');
            Log::info($servs);
            $professionals = $this->professionalService->branch_professionals_service($data['branch_id'], $servs);
            /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->get();
           */
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }
    //todo ESTA DE AQUI ES OTRO METODO NUEVO DE HORARIOS DISPONIBLE

    public function branch_professionals_serviceNew(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            $professionals = $this->professionalService->branch_professionals_serviceNew($data['branch_id'], $servs);
            /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->get();
           */
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    public function get_professionals_service(Request $request)
    {
        try {
            $data = $request->validate([
                'service_id' => 'required|numeric',
                'branch_id' => 'required|numeric'

            ]);
            $professionals = $this->professionalService->get_professionals_service($data);
            /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->where('service_id', $data['service_id']);
        })->select('id', 'name','surname','second_surname')->get();*/

            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => "Professionals"], 500);
        }
    }

    public function professionals_ganancias(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'day' => 'required',
                'startDate' => 'required|date',
                'branch_id' => 'required|numeric',
                'endDate' => 'required|date'
            ]);
            $ganancias = $this->professionalService->professionals_ganancias($data);
            return response()->json(['earningByDay' => $ganancias], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Profssional no obtuvo ganancias en este período"], 500);
        }
    }

    public function professionals_ganancias_branch(Request $request)
    {
        try {
            $data = $request->validate([

                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'charge' => 'nullable'
            ]);
            if ($request->has('mes')) {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_month($data, $request->mes, $request->year)], 200, [], JSON_NUMERIC_CHECK);
            }
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_Periodo($data, $request->startDate, $request->endDate)], 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_date($data)], 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Profssional no obtuvo ganancias en este período"], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:100|email|unique:professionals',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric'
            ]);
            $professional = new Professional();
            $professional->name = $data['name'];
            //$professional->surname = $data['surname'];
            //$professional->second_surname = $data['second_surname'];
            $professional->email = $data['email'];
            $professional->phone = $data['phone'];
            $professional->charge_id = $data['charge_id'];
            $professional->user_id = $data['user_id'];
            $professional->state = 0;
            $professional->save();
            Log::info($professional->id);
            $filename = "professionals/default.jpg";
            if ($request->hasFile('image_url')) {
                $filename = $request->file('image_url')->storeAs('professionals', $professional->id . '.' . $request->file('image_url')->extension(), 'public');
            }
            $professional->image_url = $filename;
            $professional->save();

            return response()->json(['msg' => 'Profesional insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' =>  $th->getMessage() . 'Error al insertar el professional'], 500);
        }
    }

    public function update_state(Request $request)
    {
        try {

            Log::info("entra a actualizar");
            $data = $request->validate([
                'professional_id' => 'nullable|numeric',
                'state' => 'required|numeric'
            ]);
            Log::info($request);
            $professional = Professional::find($data['professional_id']);

            $professional->state = $data['state'];
            //$professional->image_url = $filename;
            $professional->save();

            return response()->json(['msg' => 'Estado del Profesional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el estado professional'], 500);
        }
    }

    public function verifi_tec_profe(Request $request)
    {
        try {

            Log::info("entra a buscar cargo");
            $data = $request->validate([
                'email' => 'required',
                'branch_id' => 'required|numeric'
            ]);
            $professionals = $this->professionalService->verifi_tec_prof($data['email'], $data['branch_id']);
            /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->get();
           */
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el estado professional'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");
            $professionals_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric',
                'user' => 'required|string',
                'state' => 'required|numeric',
                'retention' => 'required|numeric'
            ]);
            Log::info($request);
            $professional = Professional::find($professionals_data['id']);
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:professionals,email,' . $professional->id,
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 401);
            }
            $userName = User::where('name', $request->user)->where('id', '!=', $professionals_data['user_id'])->first();
            if ($userName) {
                return response()->json([
                    'msg' => 'Usuario ya existe'
                ], 400);
            }
            $user = User::find($professionals_data['user_id']);
            $user->name = $professionals_data['user'];
            $user->email = $professionals_data['email'];
            $user->save();

            if ($request->hasFile('image_url')) {
                if ($professional->image_url != 'professionals/default.jpg') {
                    $destination = public_path("storage\\" . $professional->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                $professional->image_url = $request->file('image_url')->storeAs('professionals', $professional->id . '.' . $request->file('image_url')->extension(), 'public');
            }
            $professional->name = $professionals_data['name'];
            //$professional->surname = $professionals_data['surname'];
            //$professional->second_surname = $professionals_data['second_surname'];
            $professional->email = $professionals_data['email'];
            $professional->phone = $professionals_data['phone'];
            $professional->charge_id = $professionals_data['charge_id'];
            $professional->state = $professionals_data['state'];
            $professional->retention = $professionals_data['retention'];

            //$professional->image_url = $filename;
            $professional->save();


            return response()->json(['msg' => 'Profesional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el professional'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {

            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $professional = Professional::find($professionals_data['id']);
            if ($professional->image_url != "professionals/default.jpg") {
                //$this->imageService->destroyImagen($professional->image_url);
                $destination = public_path("storage\\" . $professional->image_url);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            $user = User::find($professional->user_id);
            if ($user) {
                Professional::destroy($professionals_data['id']);
            } else {
                Professional::destroy($professionals_data['id']);
                User::destroy($user->id);
            }
            return response()->json(['msg' => 'Profesional eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar la professional'], 500);
        }
    }

    public function professionals_state(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'reservation_id' => 'required|numeric'
            ]);
            $professional = $this->professionalService->professionals_state($data['branch_id'], $data['reservation_id']);
            return response()->json(['professionals' => $professional], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    public function professional_email(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:professionals'
            ]);
            if ($validator->fails()) {
                $user = '';
                $clientName = '';
                $clientImage = '';
                $type = 'Professional';
                return response()->json(['user' => $user, 'type' => $type], 200, [], JSON_NUMERIC_CHECK);
            }
            $client = Client::with('user')->where('email', $request->email)->first();
            //$professional = Professional::with('user')->where('email', $request->email)->first();
            if ($client != null) {
                $user = $client->user->id;
                $clientName = $client->name;
                $clientImage = $client->client_image;
                $type = 'Client';
            } else {
                $user = '';
                $clientName = '';
                $clientImage = '';
                $type = 'No';
            }
            return response()->json(['user' => $user, 'clientName' => $clientName, 'clientImage' => $clientImage, 'type' => $type], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }
}
