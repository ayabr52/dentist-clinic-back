<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Register custom response macros for JSON responses.
         *
         * This service provider defines two macros for handling JSON responses:
         *
         * 1. `success`: Returns a JSON response indicating a successful operation.
         *    - Parameters:
         *      - mixed $data (optional): The data to include in the response.
         *      - string $message (optional): A success message. Defaults to 'Success'.
         *      - int $status (optional): The HTTP status code. Defaults to 200.
         *
         * 2. `error`: Returns a JSON response indicating an error or failure.
         *    - Parameters:
         *      - string $message (optional): An error message. Defaults to 'Error'.
         *      - int $status (optional): The HTTP status code. Defaults to 400.
         *      - mixed $errors (optional): Additional error details.
         */
        // Macro for success responses
        Response::macro('success', function ($data = null, string $message = 'Success', int $status = 200) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ], $status);
        });

        // Macro for error responses
        Response::macro('error', function (string $message = 'Error', int $status = 400, $errors = null) {
            $response = [
                'success' => false,
                'message' => $message,
            ];

            if (! is_null($errors)) {
                $response['errors'] = $errors;
            }

            return response()->json($response, $status);
        });
    }
}
