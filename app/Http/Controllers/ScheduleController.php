<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use GuzzleHttp\Client; // Importar Guzzle para hacer peticiones HTTP
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        // Validación de la entrada
        $this->validate($request, [
            'professional_id' => 'required|integer',
            'date_range' => 'required',
            'time_range' => 'required',
            'interval' => 'required|integer|min:1'
        ]);

        // Hacer una petición HTTP al microservicio de usuarios para validar el professional_id
        try {
            $response = $this->client->get("users/role/professional/id/{$request->professional_id}");
            $professionalData = json_decode($response->getBody()->getContents(), true);

            // Si la validación es exitosa, continuar con la creación de horarios
            $dates = explode(' - ', $request->date_range);
            $startDate = Carbon::parse($dates[0]);
            $endDate = Carbon::parse($dates[1]);

            $times = explode(' - ', $request->time_range);
            $startTime = Carbon::parse($times[0]);
            $endTime = Carbon::parse($times[1]);

            $interval = (int) $request->interval;

            // Lista de horarios que ya están reservados
            $conflictingSlots = [];

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                for ($time = $startTime->copy(); $time->lt($endTime); $time->addMinutes($interval)) {
                    $endTimeSlot = $time->copy()->addMinutes($interval);

                    // Asegurarse de que el horario final no exceda el rango final
                    if ($endTimeSlot->gt($endTime)) {
                        $endTimeSlot = $endTime;
                    }

                    // Validar si ya existe un horario que se solape
                    $existingTimetable = DB::table('schedules')
                        ->where('professional_id', $request->professional_id)
                        ->where('available_date', $date->toDateString())
                        ->where(function ($query) use ($time, $endTimeSlot) {
                            $query->where(function ($query) use ($time, $endTimeSlot) {
                                $query->where('start_time', '<', $endTimeSlot)
                                    ->where('end_time', '>', $time);
                            });
                        })
                        ->exists();

                    if ($existingTimetable) {
                        // Agregar horarios en conflicto a la lista
                        $conflictingSlots[] = [
                            'date' => $date->toDateString(),
                            'start_time' => $time->format('H:i:s'),
                            'end_time' => $endTimeSlot->format('H:i:s')
                        ];
                    } else {
                        // Si no hay conflicto, creamos el horario
                        Schedule::create([
                            'professional_id' => $request->professional_id,
                            'available_date' => $date->toDateString(),
                            'start_time' => $time->format('H:i:s'),
                            'end_time' => $endTimeSlot->format('H:i:s'),
                            'is_booked' => false,
                        ]);
                    }
                }
            }

            if (!empty($conflictingSlots)) {
                return response()->json([
                    'message' => 'Algunos horarios ya están reservados.',
                    'conflicting_slots' => $conflictingSlots
                ], 200); // Código 200 con el mensaje de conflicto
            }

            return response()->json([
                'message' => 'Horarios creados correctamente.'
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
