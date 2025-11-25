<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Exception Handler
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            $status = 500;
            $message = 'Internal Server Error';

            if ($exception instanceof HttpException) {
                $status = $exception->getStatusCode();
                $message = $exception->getMessage() ?: 'HTTP Error';
            } elseif ($exception instanceof ModelNotFoundException) {
                $status = 404;
                $message = 'Resource not found';
            } elseif ($exception instanceof ValidationException) {
                $status = 422;
                $message = $exception->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => env('APP_DEBUG') ? $exception->getMessage() : null
            ], $status);
        }

        return parent::render($request, $exception);
    }
}
