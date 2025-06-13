<?php
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Kernel as MiddlewareKernel;

class Kernel extends MiddlewareKernel
{
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
    ];
    protected $middlewareGroups = [
        'web' => [
        ],
        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class . 's',
            'throttle:api::api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
    protected $routeMiddleware = [
        'auth'        => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth:sactum' => [
            'sanctum' => \Laravel\Sanctum\Http\Middleware\Authenticate::class . 'Sanctum',

        ],
    ];
}
