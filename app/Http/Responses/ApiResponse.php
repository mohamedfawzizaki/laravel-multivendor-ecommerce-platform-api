<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse 
{
    /**
     * Send a JSON success response.
     *
     * @param mixed $data The response data (array or object).
     * @param string $message A success message.
     * @param int $status HTTP status code (default: 200).
     * @param array $meta Additional metadata (optional).
     * @return JsonResponse JSON formatted success response.
     */
    public static function success($data = [], string $message = 'Success Operation', int $status = 200, array $meta = []): JsonResponse
    {
        // Construct the response array with status, message, and data.
        $response = [
            'status'  => true,   // Indicates the request was successful.
            'message' => $message, // Descriptive success message.
            'data'    => $data,    // Actual response data.
            'errors'  => []
        ];

        // Add meta information if provided.
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        // Return the JSON response with the specified HTTP status code.
        return response()->json($response, $status);
    }

    /**
     * Send a JSON error response.
     *
     * @param string $message Error message to be returned.
     * @param int $status HTTP status code (default: 400).
     * @param mixed $errors List of validation or processing errors.
     * @param array $meta Additional metadata (optional).
     * @return JsonResponse JSON formatted error response.
     */
    public static function error(string $message = 'Error', int $status = 400, mixed $errors = [], array $meta = []): JsonResponse
    {
        // Construct the response array with status, message, and errors.
        $response = [
            'status'  => false,   // Indicates the request failed.
            'message' => $message, // Descriptive error message.
            'errors'  => $errors,  // Details about the error (array, object, or string).
            'data'    => []
        ];

        // Add meta information if provided.
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        // Return the JSON response with the specified HTTP status code.
        return response()->json($response, $status);
    }

    /**
     * Send an unauthorized access response (HTTP 401).
     *
     * @param string $message Custom unauthorized message (default: 'Unauthorized Access').
     * @return JsonResponse JSON formatted response indicating unauthorized access.
     */
    public static function unauthenticated(string $message = 'Unauthorized Access.'): JsonResponse
    {
        // Call the error method with a 401 unauthenticated status.
        return self::error($message, 401);
    }

    /**
     * Send a forbidden access response (HTTP 403).
     *
     * @param string $message Custom forbidden message (default: 'Forbidden').
     * @return JsonResponse JSON formatted response indicating forbidden access.
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        // Call the error method with a 403 Forbidden status.
        return self::error($message, 403);
    }

    /**
     * Send a resource not found response (HTTP 404).
     *
     * @param string $message Custom not found message (default: 'Resource Not Found').
     * @return JsonResponse JSON formatted response indicating resource not found.
     */
    public static function notFound(string $message = 'Resource Not Found'): JsonResponse
    {
        // Call the error method with a 404 Not Found status.
        return self::error($message, 404);
    }
}