<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use GuzzleHttp\Client; // Importar Guzzle para hacer peticiones HTTP

class ScheduleController extends Controller
{
    protected $client;

    // Constructor para inicializar el cliente HTTP
    public function __construct()
    {
        // Inicia el cliente HTTP con la base URL del microservicio de usuarios
        $this->client = new Client([
            'base_uri' => env('USERS_SERVICE_URL') // Define la URL base en el .env, por ejemplo, http://users-service/api/
        ]);
    }

    // Crear un nuevo horario
    public function store(Request $request)
    {
        $this->validate($request, [
            'professional_id' => 'required|integer',
            'available_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        // Hacer una petición HTTP al microservicio de usuarios para validar el professional_id
        try {
            $response = $this->client->get("users/role/professional/id/{$request->professional_id}");
            $professionalData = json_decode($response->getBody()->getContents(), true);

            // Si la validación es exitosa, crear el horario
            $schedule = Schedule::create([
                'professional_id' => $request->professional_id,
                'available_date' => $request->available_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_booked' => false,
            ]);

            return response()->json([
                'message' => 'Horario creado correctamente.',
                'data' => $schedule,
            ], 201);
        } catch (\Exception $e) {
            // Manejar errores, por ejemplo, si el profesional no existe
            return response()->json([
                'error' => 'Error al validar el profesional. ' . $e->getMessage()
            ], 400);
        }
    }

    // Otros métodos del controlador permanecen igual
}
