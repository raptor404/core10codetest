<?php

namespace App\Http\Controllers;

use App\Services\StarWarsDataAggregatorService;

class StarWarsQuestionController extends Controller
{
    public function get(StarWarsDataAggregatorService $aggregatorService)
    {
        $output = (object)[
            'luke_ships' => $aggregatorService->getLukesShips(),
            'first_movie_species' => $aggregatorService->getFirstMovieSpecies(),
            'galaxy_population' => $aggregatorService->getGalaxyPopulation()
        ];

        return response()->json($output);
    }
}
