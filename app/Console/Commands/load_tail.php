<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class load_tail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load_tail';
    // protected $signature = 'app:load_tail {hr_ini,hr_fin}'; de esta forma se pasan parametros
    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Carga automaticamente todos los dias de la tabla Reservaciones y inserta en la tabla Cola los que estan reservados para el dia de hoy ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Funcion ejecutandose cada 10");
        //$hr_ini = $this->argument(key:'hr_ini'); //asi se leen las variables
        dd('Aqui poner la logica de crear la cola ');
        //dd($hr_ini );
     /*   $response = app()->call('GET', route('cola_truncate'));
        $this->info($response);*/
        Log::info("Funcion ejecutandose cada 10 FINNN");
    }
}

