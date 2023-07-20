<?php

namespace services;

use App\Exceptions\StarWarsDataAggregatorServiceException;
use App\Services\StarWarsDataAggregatorService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class StarWarsDataAggregatorServiceTest extends TestCase
{
    public function testGetLukesShips()
    {
        Cache::flush(); // clear this out to make sure we are always in the non cache case

        $service = \Mockery::mock(
            'App\Services\StarWarsDataAggregatorService[get,getAttributeFromJson]',
            [app(Client::class)]
        )->makePartial();


        $jsonStringLuke = '{"starships":["https://swapi.dev/api/starships/12/"]}';
        $jsonStringShips = '{"name":"X-wing"}';

        $service->expects('get')->with('https://swapi.dev/api/people/1/')->andReturn($jsonStringLuke);
        $service->expects('getAttributeFromJson')->with('starships', $jsonStringLuke)->andReturn(['https://swapi.dev/api/starships/12/']);
        $service->expects('get')->with('https://swapi.dev/api/starships/12/')->andReturn($jsonStringShips);
        $service->expects('getAttributeFromJson')->with('name', $jsonStringShips)->andReturn('X-wing');

        $result = $service->getLukesShips();

        $this->assertIsArray($result);
    }


    // TODO do one of these for the other methods, but it is essentially the same process...

    public function testGetLukesShipsException()
    {
        $this->expectException(\Exception::class);
        $this->expectException(StarWarsDataAggregatorServiceException::class);

        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andThrow(\Exception::class);
        });

        $service = app(StarWarsDataAggregatorService::class);

        $service->getLukesShips();
    }

    public function testGetAttributeFromJson()
    {
        $service = app(StarWarsDataAggregatorService::class);
        //key exists
        $name = 'test';
        $payload = '{"test":"Rest"}';
        $this->assertEquals('Rest', $service->getAttributeFromJson($name, $payload));

        //key does not exist
        $payload = '{"mest":"Rest"}';
        $this->assertNotEquals('Rest', $service->getAttributeFromJson($name, $payload));
    }

    public function testGetFirstMovieSpecies()
    {
        Cache::flush(); // clear this out to make sure we are always in the non cache case

        $service = \Mockery::mock(
            'App\Services\StarWarsDataAggregatorService[get,getAttributeFromJson]',
            [app(Client::class)]
        )->makePartial();

        $jsonStringMovie = '{"species":["https://swapi.dev/api/species/1/","https://swapi.dev/api/species/2/"]}';
        $jsonStringSpecies = '{"classification":"mammal"}';
        $jsonStringSpecies1 = '{"classification":"artificial"}';

        $service->shouldAllowMockingProtectedMethods();

        $service->expects('get')->with('https://swapi.dev/api/films/1/')->andReturn($jsonStringMovie);
        $service->expects('getAttributeFromJson')->with('species', $jsonStringMovie)->andReturn(['https://swapi.dev/api/species/1/', "https://swapi.dev/api/species/2/"]);

        $service->expects('get')->with('https://swapi.dev/api/species/1/')->andReturn($jsonStringSpecies);
        $service->expects('getAttributeFromJson')->with('classification', $jsonStringSpecies)->andReturn("mammal");

        $service->expects('get')->with('https://swapi.dev/api/species/2/')->andReturn($jsonStringSpecies1);
        $service->expects('getAttributeFromJson')->with('classification', $jsonStringSpecies1)->andReturn("artificial");

        $result = $service->getFirstMovieSpecies();

        $this->assertIsArray($result);

        //TODO: this is always true when mocked up, add the extra case to test this line
        $this->assertEquals(array_unique($result), $result);
    }

    public function testGetGalaxyPopulation()
    {
        Cache::flush(); // clear this out to make sure we are always in the non cache case

        $service = \Mockery::mock(
            'App\Services\StarWarsDataAggregatorService[getPlanetPageResultRecursive]',
            [app(Client::class)]
        )->makePartial();

        $service->shouldAllowMockingProtectedMethods();
        //when we mock the function that does the work, we should have a 0 value for pop
        $service->expects('getPlanetPageResultRecursive')->with('https://swapi.dev/api/planets?search=');


        $result = $service->getGalaxyPopulation();
        $this->assertEquals(0, $result);
    }

    public function testgetPlanetPageResultRecursiveOneAndDone()
    {
        Cache::flush(); // clear this out to make sure we are always in the non cache case

        $service = \Mockery::mock(
            'App\Services\StarWarsDataAggregatorService[get,getAttributeFromJson]',
            [app(Client::class)]
        )->makePartial();

        $service->shouldAllowMockingProtectedMethods();

        $jsonStringPlanetSearch = '{"next":"null",results":[{"population":"200000"},{"population":"2000000000"}]}';
        $planetResultArray = [
            (object)["population" => "200000"],
            (object)["population" => "2000000000"],
        ];
        //$jsonStringPlanetSearch = '{"next": "https://swapi.dev/api/planets/?search=&page=2",}';

        $service->shouldAllowMockingProtectedMethods();

        $service->expects('get')->with('https://swapi.dev/api/planets?search=')->andReturn($jsonStringPlanetSearch);

        $service->expects('getAttributeFromJson')->with('results', $jsonStringPlanetSearch)->andReturn($planetResultArray);
        $service->expects('getAttributeFromJson')->with('next', $jsonStringPlanetSearch)->andReturn(null);


        $result = $service->getGalaxyPopulation();
        $this->assertEquals((200000 + 2000000000), $result);
    }

    public function testgetPlanetPageResultRecursiveTwice()
    {
        Cache::flush(); // clear this out to make sure we are always in the non cache case

        $service = \Mockery::mock(
            'App\Services\StarWarsDataAggregatorService[get,getAttributeFromJson]',
            [app(Client::class)]
        )->makePartial();

        $service->shouldAllowMockingProtectedMethods();

        $jsonStringPlanetSearch0 = '{"next":"https://swapi.dev/api/planets/?search=&page=2",results":[{"population":"200000"},{"population":"2000000000"}]}';
        $jsonStringPlanetSearch1 = '{"next":"null",results":[{"population":"1"},{"population":"1"}]}';
        $planetResultArray = [
            (object)["population" => "200000"],
            (object)["population" => "2000000000"],
        ];

        $planetResultArray1 = [
            (object)["population" => "1"],
            (object)["population" => "1"],
        ];
        //$jsonStringPlanetSearch = '{"next": "https://swapi.dev/api/planets/?search=&page=2",}';

        $service->shouldAllowMockingProtectedMethods();

        $service->expects('get')->with('https://swapi.dev/api/planets?search=')->andReturn($jsonStringPlanetSearch0);
        $service->expects('get')->with('https://swapi.dev/api/planets/?search=&page=2')->andReturn($jsonStringPlanetSearch1);

        $service->expects('getAttributeFromJson')->with('results', $jsonStringPlanetSearch0)->andReturn($planetResultArray);
        $service->expects('getAttributeFromJson')->with('next', $jsonStringPlanetSearch0)->andReturn('https://swapi.dev/api/planets/?search=&page=2');

        $service->expects('getAttributeFromJson')->with('results', $jsonStringPlanetSearch1)->andReturn($planetResultArray1);
        $service->expects('getAttributeFromJson')->with('next', $jsonStringPlanetSearch1)->andReturn(null);

        $result = $service->getGalaxyPopulation();
        $this->assertEquals((200000 + 2000000000 + 1 + 1), $result);
    }
}
