<?php
namespace Unit\Http\Controllers;

use App\Exceptions\StarWarsDataAggregatorServiceException;
use App\Http\Controllers\StarWarsQuestionController;
use App\Services\StarWarsDataAggregatorService;
use GuzzleHttp\Client;
use Tests\TestCase;

class StarWarsQuestionControllerTest extends TestCase
{
    public function testGet(){
        $controller = app()->make(StarWarsQuestionController::class);
        $service = app()->make(StarWarsDataAggregatorService::class);
        $result = $controller->get($service);


        $this->assertJson($result->getContent());

        //This test is brittle, it will change if the question changes or if the data changes
        $this->assertJsonStringEqualsJsonString(
            '{"luke_ships":["X-wing","Imperial shuttle"],"first_movie_species":{"0":"mammal","1":"artificial","3":"sentient","4":"gastropod"},"galaxy_population":1711401432500}',
            $result->getContent()
        );


        //less brittle and basically requires our structure to exist as we expect it until we change it, then we change the test
        $json = json_decode($result->getContent());
        $this->assertObjectHasProperty('luke_ships', $json);
        $this->assertObjectHasProperty('first_movie_species', $json);
        $this->assertObjectHasProperty('galaxy_population', $json);

        //Could add more structure tests here if that is important
    }

}
