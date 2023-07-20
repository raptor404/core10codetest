<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (StarWarsDataAggregatorServiceException $e, Request $request) {
            //TODO add final logging if needed, exception logged at the service level
            return response()->json([
                'error'=>'500',
                'message'=>'Could not get data for '.$request->route()->getName(). '. Exception logged as #'. $e->getLogId(),
            ]);
        });
    }
}
