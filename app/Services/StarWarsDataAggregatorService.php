<?php

namespace App\Services;

use App\Exceptions\StarWarsDataAggregatorServiceException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class StarWarsDataAggregatorService
{
    protected int $populationAccumulator;
    protected Client $guzzleClient;

    public function __construct(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
        $this->populationAccumulator = 0;
    }

    public function get($url): string
    {
        return $this->guzzleClient->get($url)->getBody()->getContents();
    }

    /**
     * @return array
     * @throws StarWarsDataAggregatorServiceException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLukesShips(): array
    {
        try {
            $lukesShips = [];
            $cachedLukeShips = Cache::get('swda-luke-ships', null);

            if ($cachedLukeShips !== null) {
                $lukesShips = $cachedLukeShips;
            } else {
                //TODO fetch url from config
                $resultData = $this->get('https://swapi.dev/api/people/1/');

                $shipList = $this->getAttributeFromJson('starships', $resultData);

                foreach ($shipList as $ship) {
                    $shipResultData = $this->get($ship);
                    $lukesShips[] = $this->getAttributeFromJson('name', $shipResultData);
                }

                Cache::set('swda-luke-ships', $lukesShips);
            }

            return $lukesShips;
        } catch (\Exception $exception) {
            $this->throwServiceException('getLukesShips', $exception->getMessage());
        }
    }

    public static function getAttributeFromJson(string $name, string $payload)
    {
        $json = json_decode($payload);
        return $json->{$name} ?? null;
    }

    public function getFirstMovieSpecies(): array
    {
        $species = [];
        try {
            $cachedSpecies = Cache::get('swda-first-movie-species', null);

            if ($cachedSpecies !== null) {
                $species = $cachedSpecies;
            } else {
                //TODO fetch url from config
                $resultData = $this->get('https://swapi.dev/api/films/1/');
                $movieSpeciesList = $this->getAttributeFromJson('species', $resultData);

                foreach ($movieSpeciesList as $item) {
                    $itemResultData = $this->get($item);
                    $species[] = $this->getAttributeFromJson('classification', $itemResultData);
                }
                $species = array_unique($species);
                Cache::set('swda-first-movie-species', $species);
            }

            return $species;
        } catch (\Exception $exception) {
            $this->throwServiceException('getFirstMovieSpecies', $exception->getMessage());
        }
    }

    public function getGalaxyPopulation(): int
    {
        try {
            $cachedPopulationValue = Cache::get('swda-galaxy-population', null);

            if ($cachedPopulationValue !== null) {
                $this->populationAccumulator = $cachedPopulationValue;
            } else {
                //TODO fetch url from config
                $this->getPlanetPageResultRecursive('https://swapi.dev/api/planets?search=');
                Cache::set('swda-galaxy-population', $this->populationAccumulator);
            }

            return $this->populationAccumulator;
        } catch (\Exception $exception) {
            $this->throwServiceException('getGalaxyPopulation', $exception->getMessage());
        }
    }

    /**
     * @param $url
     * @return void
     * @throws StarWarsDataAggregatorServiceException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * This function calls itself to hit each subsequent page in the result set.
     * If this starts to run us out of memory, there is a leak between calls, look into cleaning refs
     * Could be refactored to us the result count to do a for loop as needed
     *
     */
    protected function getPlanetPageResultRecursive(string $url): void
    {
        try {
            $resultData = $this->get($url);
            $results = $this->getAttributeFromJson('results', $resultData);
            $nextPage = $this->getAttributeFromJson('next', $resultData);

            foreach ($results as $result) {
                if ($result->population !== null) {
                    $this->populationAccumulator = $this->populationAccumulator + (int)$result->population;
                }
            }

            if ($nextPage !== null) {
                $results = null;
                $resultData = null;
                $this->getPlanetPageResultRecursive($nextPage);
            }
        } catch (ClientException $clientException) {
            $this->throwServiceException('getGalaxyPopulation', $clientException->getMessage());
        } catch (\Exception $exception) {
            $this->throwServiceException('getGalaxyPopulation', $exception->getMessage());
        }
    }

    /**
     * @param string $functionName
     * @param string|null $message
     * @return void
     * @throws StarWarsDataAggregatorServiceException
     * Control the error flow out of the service to suppress excess info from going to client and to allow central logging
     */
    protected function throwServiceException(string $functionName, ?string $message): void
    {
        Log::error($functionName . ' failed, exception was: ' . $message);
        throw new StarWarsDataAggregatorServiceException($functionName . ' failed, exception was: ' . $message);
    }
}
