<?php

namespace App\Http\Controllers;

use App\Services\FlightsService;

class FlightsController
{
    public function index()
    {
        //chamando o Service de voos
        $flightsService = new FlightsService();
        return response()->json($flightsService->getFlights(), 200);
    }
}
