<?php
namespace App\Http\Controllers;

use App\Events\TestingEvent;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class TestingEventController extends Controller
{
    public function testingEvent()
    {

       

        event(new TestingEvent());
    }
    public function fetchData()
    {
        // Ruta al archivo cacert.pem
        $cacertPath = base_path('storage/app/public/licenc/cacert.pem');

        // Crear instancia de GuzzleHttp Client
        $client = new Client([
            'base_uri' => 'http://127.0.0.1:8000', // Reemplaza con la URL base que necesitas
            'verify' => $cacertPath, // Ruta al archivo cacert.pem
        ]);

        // Realizar la solicitud
        $response = $client->request('GET', '/testing-websocket'); // Reemplaza con el endpoint necesario

        // Obtener el cuerpo de la respuesta
        $data = $response->getBody()->getContents();

        // Retornar la respuesta o hacer algo con los datos
        return response()->json(json_decode($data, true));
    }
}
