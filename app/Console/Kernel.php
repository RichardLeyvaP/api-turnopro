<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
//use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Exception;

class Kernel extends ConsoleKernel
{
    /**
     * El código global para todas las tareas programadas.
     *
     * @var string
     */
    protected $codigoGlobal = 'P{\nkNgP9hjm/L*~Sks25h^C30_|17';

    /**
     * Define the application's command schedule.
     */
    
    protected function schedule(Schedule $schedule): void
    {
        //Log::info('Iniciando las tareas programadas-Kernel-New');
        // Definir la primera tarea-Limpia la cola del dia anterior
        $schedule->call(function () {
           // Log::info('Iniciando la primera tarea programada.Limpia la cola del dia anterior');
            
            // Crear un cliente HTTP
            $client = new Client();
            // Acceder al código global a través de $this->codigoGlobal
            $codigoGlobal = $this->codigoGlobal;

            try {
                // Hacer una solicitud GET a la primera ruta completa de la API
                $response = $client->get("https://api2.simplifies.cl/api/cola_truncate?codigo=" . urlencode($codigoGlobal));

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                   // Log::info('La solicitud a /cola_truncate se ejecutó correctamente.');
                } else {
                   // Log::error('Error al ejecutar la solicitud a /cola_truncate: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
               // Log::error('Excepción al hacer la solicitud a /cola_truncate: ' . $e->getMessage());
            }
        })->dailyAt('01:00'); // Primera tarea a las 1:00 AM

        // Definir la segunda tarea, Limpiar los puestos de trabajo
        $schedule->call(function () {
           // Log::info('Iniciando la segunda tarea programada.Limpiar los puestos de trabajo');

            // Crear un cliente HTTP
            $client = new Client();
            // Acceder al código global a través de $this->codigoGlobal
            $codigoGlobal = $this->codigoGlobal;

            try {
                // Hacer una solicitud GET a la segunda ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/workplace-reset?codigo=' . urlencode($codigoGlobal));

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                   // Log::info('La solicitud a /workplace-reset se ejecutó correctamente.');
                } else {
                   // Log::error('Error al ejecutar la solicitud a /workplace-reset: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
              //  Log::error('Excepción al hacer la solicitud a /workplace-reset: ' . $e->getMessage());
            }
        })->dailyAt('01:30'); // Segunda tarea a las 1:30 AM

        // Definir la segunda/despues tarea, Limpiar las notificaciones
        $schedule->call(function () {
           // Log::info('Iniciando la otra despues de la segunda tarea programada.Limpiar las notificaciones');

            // Crear un cliente HTTP
            $client = new Client();
             // Acceder al código global a través de $this->codigoGlobal
             $codigoGlobal = $this->codigoGlobal;

            try {
                // Hacer una solicitud GET a la segunda ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/notification-truncate?codigo=' . urlencode($codigoGlobal));

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                   // Log::info('La solicitud a /notification-truncate se ejecutó correctamente.');
                } else {
                    //Log::error('Error al ejecutar la solicitud a /notification-truncate: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
              //  Log::error('Excepción al hacer la solicitud a /notification-truncate: ' . $e->getMessage());
            }
        })->dailyAt('02:00'); // Segunda tarea a las 1:30 AM


        // Definir la tercera tarea-Envio de email de confirmación de reserva
        $schedule->call(function () {
           // Log::info('Iniciando la tercera tarea programada.Envio de email de confirmación de reserva');
            $codigoGlobal = $this->codigoGlobal;
            // Crear un cliente HTTP
            $client = new Client();

            try {
                // Hacer una solicitud GET a la tercera ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/reservation-send-mail?codigo=' . urlencode($codigoGlobal));

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                  //  Log::info('La solicitud a /reservation-send-mail se ejecutó correctamente.');
                } else {
                  //  Log::error('Error al ejecutar la solicitud a /reservation-send-mail: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
               // Log::error('Excepción al hacer la solicitud a /reservation-send-mail: ' . $e->getMessage());
            }
        })->dailyAt('06:00'); // Tercera tarea a las 6:00 AM

        // Definir la cuarta tarea-Actualizar la cola del dia
        $schedule->call(function () {
            $codigoGlobal = $this->codigoGlobal;
         //   Log::info('Iniciando la tercera tarea programada.Actualizar la cola del dia');
            // Crear un cliente HTTP
            $client = new Client();
            try {
                // Hacer una solicitud GET a la tercera ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/reservation_tail_task?codigo=' . urlencode($codigoGlobal));

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                   // Log::info('La solicitud a /reservation_tail se ejecutó correctamente.');
                } else {
                  //  Log::error('Error al ejecutar la solicitud a /reservation_tail: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
              //  Log::error('Excepción al hacer la solicitud a /reservation_tail: ' . $e->getMessage());
            }
        })->dailyAt('07:00'); // Cuarta tarea a las 7:00 AM

        // Definir la cuarta tarea-Actualizar la cola del dia
        $schedule->call(function () {
          $codigoGlobal = $this->codigoGlobal;
       //   Log::info('Iniciando la tercera tarea programada.Actualizar la cola del dia');
          // Crear un cliente HTTP
          $client = new Client();
          try {
              // Hacer una solicitud GET a la tercera ruta completa de la API
              $response = $client->get('https://api2.simplifies.cl/api/whatsapp-notification-remember?codigo=' . urlencode($codigoGlobal));

              // Verificar la respuesta
              if ($response->getStatusCode() == 200) {
                 // Log::info('La solicitud a /reservation_tail se ejecutó correctamente.');
              } else {
                //  Log::error('Error al ejecutar la solicitud a /reservation_tail: ' . $response->getStatusCode());
              }
          } catch (Exception $e) {
            //  Log::error('Excepción al hacer la solicitud a /reservation_tail: ' . $e->getMessage());
          }
      })->dailyAt('07:30'); // Cuarta tarea a las 7:00 AM

        // Definir Cierrede caja automaticos
        $schedule->call(function () {
           // Log::info('Iniciando tarea programada.Realizar cierre de caja del dia anterior');
            // Crear un cliente HTTP
            $client = new Client(); 
            // Acceder al código global a través de $this->codigoGlobal
            $codigoGlobal = $this->codigoGlobal;
            try {
                // Hacer una solicitud GET a la tercera ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/test-box-close?codigo=' . urlencode($codigoGlobal));

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                   // Log::info('La solicitud a /test-box-close se ejecutó correctamente.');
                } else {
                  //  Log::error('Error al ejecutar la solicitud a /test-box-close: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
              //  Log::error('Excepción al hacer la solicitud a /test-box-close: ' . $e->getMessage());
            }
        })->dailyAt('02:00'); // tarea a las 02:00 AM
        $schedule->call(function () {
            $codigoGlobal = $this->codigoGlobal;
            // Log::info('Iniciando la quinta tarea programada.correo de cierre de caja mensual');
            // Crear un cliente HTTP
            $client = new Client();
            try {
                // Hacer una solicitud GET a la tercera ruta completa de la API
                $response = $client->get('https://api2.simplifies.cl/api/payment-utomatically?codigo=' . urlencode($codigoGlobal));

                // Verificar la respuesta
                if ($response->getStatusCode() == 200) {
                    // Log::info('La solicitud a /closebox-month se ejecutó correctamente.');
                } else {
                    // Log::error('Error al ejecutar la solicitud a /closebox-month: ' . $response->getStatusCode());
                }
            } catch (Exception $e) {
                // Log::error('Excepción al hacer la solicitud a /closebox-month: ' . $e->getMessage());
            }
        })->lastDayOfMonth()->at('23:00');

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
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
