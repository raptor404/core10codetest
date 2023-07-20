# StarWars API Data Aggregator API

## Intro
This is a simple api which outputs the result of 3 questions to the SWAPI. It can be reached at /api/swa/questions

The first time the api gets hit, it will take a bit to gather the information, but the results are cached after the first request to prevent clobbering a non-changing resource.

## Setup
Regular Laravel app, setup as normal, no migrations or db, needs a cache driver configured, default of file in env.example

composer install 

cp .env.example .env

php artisan key:generate

serve wherever you like. Request /api/swa/questions to get the result

## Limitations
- This is a sample problem and therefore does not have 100% test coverage for all edge cases. 
- It intentionally fails ungracefully as the fallback conditions were not defined in the problem.
- There are no additional laravel hardening steps taken and no caching policies set beyond the defaults. 
- The data gets cached and is calculated once rather than live time as we do not want to be poor stewards of the swapi free resources
- Unit tests are constructed to test as much of the system under test as possible (given time constraint) rather than framework / language construct
- Uses only a single api endpoint / single rest verb as no structure was specified and therefore no information architecture regarding perf or data relevance
