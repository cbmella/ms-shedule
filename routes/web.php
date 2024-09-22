<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('schedules', 'ScheduleController@store'); // Crear un nuevo horario
    $router->get('schedules/{professional_id}', 'ScheduleController@index'); // Ver los horarios de un profesional
    $router->get('schedules/show/{id}', 'ScheduleController@show'); // Ver un horario específico
    $router->put('schedules/{id}', 'ScheduleController@update'); // Actualizar un horario
    $router->delete('schedules/{id}', 'ScheduleController@destroy'); // Eliminar un horario
});
