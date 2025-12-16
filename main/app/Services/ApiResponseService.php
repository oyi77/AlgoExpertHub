<?php

namespace App\Services;

use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseService extends BaseService
{
    /**
     * Create a standardized success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $meta
     * @return JsonResponse
     */
    public function success($data = null, string $message = 'Success', int $statusCode = Response::HTTP_OK, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
                return $data->additional([
                    'success' => true,
                    'message' => $message,
                    'meta' => array_merge([
                        'timestamp' => now()->toISOString(),
                        'version' => config('app.api_version'),
                    ], $meta),
                ])->response()->setStatusCode($statusCode);
            }

            if ($data instanceof LengthAwarePaginator) {
                $response['data'] = $data->items();
                $response['pagination'] = [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'has_more_pages' => $data->hasMorePages(),
                    'path' => $data->path(),
                    'first_page_url' => $data->url(1),
                    'last_page_url' => $data->url($data->lastPage()),
                    'next_page_url' => $data->nextPageUrl(),
                    'prev_page_url' => $data->previousPageUrl(),
                ];
            } else {
                $response['data'] = $data;
            }
        }

        $response['meta'] = array_merge([
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version'),
        ], $meta);

        return response()->json($response, $statusCode);
    }

    /**
     * Create a standardized error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @param array $meta
     * @return JsonResponse
     */
    public function error(string $message = 'Error', int $statusCode = Response::HTTP_BAD_REQUEST, array $errors = [], array $meta = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'meta' => array_merge([
                'timestamp' => now()->toISOString(),
                'version' => config('app.api_version'),
            ], $meta),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create a validation error response.
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Create a not found error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Create an unauthorized error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Create a forbidden error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Create a server error response.
     *
     * @param string $message
     * @param \Exception|null $exception
     * @return JsonResponse
     */
    public function serverError(string $message = 'Internal server error', \Exception $exception = null): JsonResponse
    {
        $meta = [];
        
        if ($exception && config('app.debug')) {
            $meta['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return $this->error($message, Response::HTTP_INTERNAL_SERVER_ERROR, [], $meta);
    }

    /**
     * Create a created response.
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Create a no content response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function noContent(string $message = 'No content'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => config('app.api_version'),
            ],
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Create a rate limit exceeded response.
     *
     * @param string $message
     * @param int $retryAfter
     * @return JsonResponse
     */
    public function rateLimitExceeded(string $message = 'Rate limit exceeded', int $retryAfter = 60): JsonResponse
    {
        $response = $this->error($message, Response::HTTP_TOO_MANY_REQUESTS);
        $response->header('Retry-After', $retryAfter);
        
        return $response;
    }

    /**
     * Create a maintenance mode response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function maintenanceMode(string $message = 'Service temporarily unavailable'): JsonResponse
    {
        return $this->error($message, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * Transform Laravel validation errors to standardized format.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return array
     */
    public function formatValidationErrors($validator): array
    {
        $errors = [];
        
        foreach ($validator->errors()->messages() as $field => $messages) {
            $errors[$field] = [
                'field' => $field,
                'messages' => $messages,
                'value' => request()->input($field),
            ];
        }
        
        return $errors;
    }

    /**
     * Add response headers for API versioning and performance.
     *
     * @param JsonResponse $response
     * @param array $additionalHeaders
     * @return JsonResponse
     */
    public function addStandardHeaders(JsonResponse $response, array $additionalHeaders = []): JsonResponse
    {
        $headers = array_merge([
            'X-API-Version' => config('app.api_version'),
            'X-Response-Time' => microtime(true) - LARAVEL_START,
            'X-Request-ID' => request()->header('X-Request-ID', uniqid()),
        ], $additionalHeaders);

        foreach ($headers as $name => $value) {
            $response->header($name, $value);
        }

        return $response;
    }
}