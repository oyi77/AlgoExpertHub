<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report(Throwable $e)
    {
        // Skip reporting Page Builder database connection errors (not critical)
        if ($e instanceof \Error && 
            str_contains($e->getMessage() ?? '', 'Call to a member function select() on null') &&
            (str_contains($e->getFile() ?? '', 'phpagebuilder') || str_contains($e->getFile() ?? '', 'pagebuilder'))) {
            \Log::warning('Page Builder database connection not initialized', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return; // Don't report as error, just log warning
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        // Handle Page Builder database connection errors gracefully (only log, don't interfere)
        if ($e instanceof \Error && 
            str_contains($e->getMessage() ?? '', 'Call to a member function select() on null') &&
            (str_contains($e->getFile() ?? '', 'phpagebuilder') || str_contains($e->getFile() ?? '', 'pagebuilder'))) {
            \Log::warning('Page Builder database connection not initialized', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);
            // Let it fall through to normal error handling - don't interfere with routing
        }

        // Handle all exceptions (including 500 errors from database, etc.)
        $statusCode = 500;
        
        if ($this->isHttpException($e)) {
            $statusCode = $e->getStatusCode();
        } elseif ($e instanceof \Illuminate\Database\QueryException || 
                  $e instanceof \PDOException ||
                  $e instanceof \ErrorException) {
            // Database errors, missing tables, etc. - show friendly 500 page
            $statusCode = 500;
        }
        
        // Only show friendly error pages for 500 errors
        if ($statusCode === 500) {
            try {
                // Check if View service is available before trying to render views
                if (!app()->bound('view')) {
                    // If View service is not available, return simple text response
                    return response("Ummm, i think we make a mistake, here. Please Tell Us! Just in case we are forget ):", $statusCode);
                }
                
                // Safely check if request is from admin panel
                $isAdmin = $request->is('admin/*');
                if (!$isAdmin) {
                    try {
                        if (app()->bound('auth') && app('auth')->guard('admin')) {
                            $isAdmin = Auth::guard('admin')->check();
                        }
                    } catch (\Exception $authEx) {
                        // If auth check fails, just use URL pattern
                        $isAdmin = false;
                    }
                }
                
                // Safely check if request is from user panel
                $isUser = false;
                if (!$isAdmin) {
                    try {
                        if (app()->bound('auth')) {
                            $isUser = Auth::check();
                        }
                    } catch (\Exception $authEx) {
                        // If auth check fails, assume not user
                        $isUser = false;
                    }
                }

                // Use panel-specific error views
                if ($isAdmin) {
                    $view = "errors.500-admin";
                    $viewPath = resource_path("views/{$view}.blade.php");
                    if (file_exists($viewPath)) {
                        try {
                            return response()->view($view, ['exception' => $e], $statusCode);
                        } catch (\Exception $viewEx) {
                            // If view rendering fails, fall back to default
                        }
                    }
                } elseif ($isUser) {
                    $view = "errors.500-user";
                    $viewPath = resource_path("views/{$view}.blade.php");
                    if (file_exists($viewPath)) {
                        try {
                            return response()->view($view, ['exception' => $e], $statusCode);
                        } catch (\Exception $viewEx) {
                            // If view rendering fails, fall back to default
                        }
                    }
                } else {
                    // Public/guest error page
                    $view = "errors.500";
                    $viewPath = resource_path("views/{$view}.blade.php");
                    if (file_exists($viewPath)) {
                        try {
                            return response()->view($view, ['exception' => $e], $statusCode);
                        } catch (\Exception $viewEx) {
                            // If view rendering fails, fall back to default
                        }
                    }
                }
            } catch (\Exception $ex) {
                // If there's any error, fall back to default error handling
                // Don't log to avoid infinite loops or breaking normal pages
            }
        } else {
            // Handle other HTTP exceptions (404, 403, etc.)
            try {
                // Check if View service is available before trying to render views
                if (!app()->bound('view')) {
                    // If View service is not available, return simple text response
                    return response("Error {$statusCode}", $statusCode);
                }
                
                // Safely check if request is from admin panel
                $isAdmin = $request->is('admin/*');
                if (!$isAdmin) {
                    try {
                        if (app()->bound('auth') && app('auth')->guard('admin')) {
                            $isAdmin = Auth::guard('admin')->check();
                        }
                    } catch (\Exception $authEx) {
                        // If auth check fails, just use URL pattern
                        $isAdmin = false;
                    }
                }
                
                // Safely check if request is from user panel
                $isUser = false;
                if (!$isAdmin) {
                    try {
                        if (app()->bound('auth')) {
                            $isUser = Auth::check();
                        }
                    } catch (\Exception $authEx) {
                        // If auth check fails, assume not user
                        $isUser = false;
                    }
                }

                // Use panel-specific error views
                if ($isAdmin) {
                    $view = "errors.{$statusCode}-admin";
                    $viewPath = resource_path("views/{$view}.blade.php");
                    if (file_exists($viewPath)) {
                        try {
                            return response()->view($view, ['exception' => $e], $statusCode);
                        } catch (\Exception $viewEx) {
                            // If view rendering fails, fall back to default
                        }
                    }
                } elseif ($isUser) {
                    $view = "errors.{$statusCode}-user";
                    $viewPath = resource_path("views/{$view}.blade.php");
                    if (file_exists($viewPath)) {
                        try {
                            return response()->view($view, ['exception' => $e], $statusCode);
                        } catch (\Exception $viewEx) {
                            // If view rendering fails, fall back to default
                        }
                    }
                }
            } catch (\Exception $ex) {
                // If there's any error, fall back to default error handling
                // Don't log to avoid infinite loops or breaking normal pages
            }
        }

        return parent::render($request, $e);
    }
}
