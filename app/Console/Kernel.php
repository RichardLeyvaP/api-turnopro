<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Exception;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Definir la primera tarea-Limpia la cola del dia anterior
        $schedule->call(function () {
            Log::info('Iniciando la primera tarea programada.Limpia la cola del dia anterior');

            // Crear un cliente HTTP
            $client = new Client();

            try {
                // Hacer una solicitud GET a la primera ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/cola_truncate');

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                    Log::info('La solicitud a /cola_truncate se ejecutó correctamente.');
                } else {
                    Log::error('Error al ejecutar la solicitud a /cola_truncate: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
                Log::error('Excepción al hacer la solicitud a /cola_truncate: ' . $e->getMessage());
            }
        })->dailyAt('01:00'); // Primera tarea a las 1:00 AM

        // Definir la segunda tarea, Limpiar los puestos de trabajo
        $schedule->call(function () {
            Log::info('Iniciando la segunda tarea programada.Limpiar los puestos de trabajo');

            // Crear un cliente HTTP
            $client = new Client();

            try {
                // Hacer una solicitud GET a la segunda ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/workplace-reset');

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                    Log::info('La solicitud a /workplace-reset se ejecutó correctamente.');
                } else {
                    Log::error('Error al ejecutar la solicitud a /workplace-reset: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
                Log::error('Excepción al hacer la solicitud a /workplace-reset: ' . $e->getMessage());
            }
        })->dailyAt('01:30'); // Segunda tarea a las 1:30 AM

        
        // Definir la tercera tarea-Envio de email de confirmación de reserva
        $schedule->call(function () {
            Log::info('Iniciando la tercera tarea programada.Envio de email de confirmación de reserva');

            // Crear un cliente HTTP
            $client = new Client();

            try {
                // Hacer una solicitud GET a la tercera ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/reservation-send-mail');

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                    Log::info('La solicitud a /reservation-send-mail se ejecutó correctamente.');
                } else {
                    Log::error('Error al ejecutar la solicitud a /reservation-send-mail: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
                Log::error('Excepción al hacer la solicitud a /reservation-send-mail: ' . $e->getMessage());
            }
        })->dailyAt('06:00'); // Tercera tarea a las 6:00 AM

        // Definir la cuarta tarea-Actualizar la cola del dia
       $schedule->call(function () {
            Log::info('Iniciando la tercera tarea programada.Actualizar la cola del dia');
        // Crear un cliente HTTP
         $client = new Client();
         try {
          // Hacer una solicitud GET a la tercera ruta completa de la API
          $response = $client->get('https://api2.simplifies.cl/api/reservation_tail');

        // Verificar la respuesta
        if ($response->getStatusCode() == 200) {
            Log::info('La solicitud a /reservation_tail se ejecutó correctamente.');
        } else {
            Log::error('Error al ejecutar la solicitud a /reservation_tail: ' . $response->getStatusCode());
        }
     } catch (Exception $e) {
        Log::error('Excepción al hacer la solicitud a /reservation_tail: ' . $e->getMessage());
     }
})->dailyAt('07:00'); // Cuarta tarea a las 7:00 AM


        //
        //
        //
        //
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(_DIR_.'/Commands');
        require base_path('routes/console.php');
    }
}