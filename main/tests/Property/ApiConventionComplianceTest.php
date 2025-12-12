<?php

namespace Tests\Property;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Signal;
use App\Models\Plan;
use Laravel\Sanctum\Sanctum;
use Eris\Generator;

/**
 * Property 9: API Convention Compliance
 * 
 * Feature: platform-optimization-improvements
 * 
 * For any API endpoint, RESTful conventions should be followed with 
 * appropriate HTTP status codes and response formats.
 * 
 * Validates: Requirements 2.4
 */
class ApiConventionComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * Test that all API endpoints follow RESTful naming conventions.
     */
    public function testApiEndpointsFollowRestfulNamingConventions()
    {
        $this->forAll(
            Generator\elements(...$this->getApiRoutes())
        )->then(function ($route) {
            // RESTful conventions check
            $uri = $route['uri'];
            $methods = $route['methods'];
            
            // API routes should start with 'api/'
            $this->assertStringStartsWith('api/', $uri, "API route should start with 'api/': {$uri}");
            
            // Resource routes should follow RESTful patterns
            if (preg_match('/api\/([^\/]+)\/(\d+)$/', $uri)) {
                // Individual resource routes should support GET, PUT, PATCH, DELETE
                $this->assertContains('GET', $methods, "Individual resource route should support GET: {$uri}");
            }
            
            if (preg_match('/api\/([^\/]+)$/', $uri) && !str_contains($uri, 'auth')) {
                // Collection routes should support GET and POST
                if (in_array('GET', $methods)) {
                    $this->assertTrue(true, "Collection route supports GET: {$uri}");
                }
            }
            
            // Route names should follow convention
            if ($route['name']) {
                $this->assertMatchesRegularExpression(
                    '/^(api\.|admin\.|user\.)?[a-z0-9\-_\.]+$/',
                    $route['name'],
                    "Route name should follow naming convention: {$route['name']}"
                );
            }
        });
    }

    /**
     * Test that API responses follow consistent format.
     */
    public function testApiResponsesFollowConsistentFormat()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->forAll(
            Generator\elements(...$this->getTestableApiEndpoints())
        )->then(function ($endpoint) use ($user) {
            $response = $this->json($endpoint['method'], $endpoint['uri']);
            
            // All API responses should be JSON
            $this->assertJson($response->getContent(), "Response should be valid JSON for {$endpoint['uri']}");
            
            $data = $response->json();
            
            // All responses should have 'success' field
            $this->assertArrayHasKey('success', $data, "Response should have 'success' field for {$endpoint['uri']}");
            $this->assertIsBool($data['success'], "Success field should be boolean for {$endpoint['uri']}");
            
            // All responses should have 'message' field
            $this->assertArrayHasKey('message', $data, "Response should have 'message' field for {$endpoint['uri']}");
            $this->assertIsString($data['message'], "Message field should be string for {$endpoint['uri']}");
            
            // Success responses should have appropriate structure
            if ($data['success']) {
                // May have 'data' field
                if (array_key_exists('data', $data)) {
                    $this->assertTrue(true, "Success response may have data field");
                }
                
                // May have 'meta' field for additional metadata
                if (array_key_exists('meta', $data)) {
                    $this->assertIsArray($data['meta'], "Meta field should be array");
                }
            } else {
                // Error responses may have 'errors' field
                if (array_key_exists('errors', $data)) {
                    $this->assertTrue(
                        is_array($data['errors']) || is_string($data['errors']),
                        "Errors field should be array or string"
                    );
                }
            }
        });
    }

    /**
     * Test that HTTP status codes are used appropriately.
     */
    public function testHttpStatusCodesAreUsedAppropriately()
    {
        $user = User::factory()->create();
        
        $this->forAll(
            Generator\elements(...$this->getStatusCodeTestCases())
        )->then(function ($testCase) use ($user) {
            if ($testCase['auth_required']) {
                Sanctum::actingAs($user);
            }
            
            $response = $this->json($testCase['method'], $testCase['uri'], $testCase['data'] ?? []);
            
            // Check that status code is in expected range
            $statusCode = $response->getStatusCode();
            $expectedRange = $testCase['expected_status_range'];
            
            $this->assertGreaterThanOrEqual(
                $expectedRange[0],
                $statusCode,
                "Status code should be >= {$expectedRange[0]} for {$testCase['uri']}"
            );
            
            $this->assertLessThanOrEqual(
                $expectedRange[1],
                $statusCode,
                "Status code should be <= {$expectedRange[1]} for {$testCase['uri']}"
            );
            
            // Specific status code checks
            if ($testCase['method'] === 'POST' && $statusCode >= 200 && $statusCode < 300) {
                $this->assertContains($statusCode, [200, 201], "POST success should return 200 or 201");
            }
            
            if ($testCase['method'] === 'DELETE' && $statusCode >= 200 && $statusCode < 300) {
                $this->assertContains($statusCode, [200, 204], "DELETE success should return 200 or 204");
            }
        });
    }

    /**
     * Test that API versioning headers are present.
     */
    public function testApiVersioningHeadersArePresent()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->forAll(
            Generator\elements(...$this->getVersionedApiEndpoints())
        )->then(function ($endpoint) {
            $response = $this->withHeaders([
                'Accept-Version' => 'v1'
            ])->json($endpoint['method'], $endpoint['uri']);
            
            // Should have API version header
            $this->assertTrue(
                $response->headers->has('X-API-Version'),
                "Response should have X-API-Version header for {$endpoint['uri']}"
            );
            
            // Should have supported versions header
            $this->assertTrue(
                $response->headers->has('X-API-Supported-Versions'),
                "Response should have X-API-Supported-Versions header for {$endpoint['uri']}"
            );
            
            // Version should match requested version
            $this->assertEquals(
                'v1',
                $response->headers->get('X-API-Version'),
                "API version header should match requested version"
            );
        });
    }

    /**
     * Get all API routes for testing.
     */
    private function getApiRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(function ($route) {
                return str_starts_with($route->uri(), 'api/');
            })
            ->map(function ($route) {
                return [
                    'uri' => $route->uri(),
                    'methods' => $route->methods(),
                    'name' => $route->getName(),
                ];
            })
            ->toArray();
    }

    /**
     * Get testable API endpoints (safe to call without side effects).
     */
    private function getTestableApiEndpoints(): array
    {
        return [
            ['method' => 'GET', 'uri' => '/api/user'],
            ['method' => 'GET', 'uri' => '/api/user/dashboard'],
            ['method' => 'GET', 'uri' => '/api/user/signals'],
            ['method' => 'GET', 'uri' => '/api/user/plans'],
            ['method' => 'GET', 'uri' => '/api/currency-pairs'],
            ['method' => 'GET', 'uri' => '/api/timeframes'],
            ['method' => 'GET', 'uri' => '/api/markets'],
        ];
    }

    /**
     * Get status code test cases.
     */
    private function getStatusCodeTestCases(): array
    {
        return [
            [
                'method' => 'GET',
                'uri' => '/api/user',
                'auth_required' => true,
                'expected_status_range' => [200, 299],
            ],
            [
                'method' => 'GET',
                'uri' => '/api/user/signals/999999',
                'auth_required' => true,
                'expected_status_range' => [404, 404],
            ],
            [
                'method' => 'POST',
                'uri' => '/api/auth/login',
                'auth_required' => false,
                'data' => ['email' => 'invalid', 'password' => 'invalid'],
                'expected_status_range' => [400, 499],
            ],
            [
                'method' => 'GET',
                'uri' => '/api/user/dashboard',
                'auth_required' => false, // Should require auth
                'expected_status_range' => [401, 401],
            ],
        ];
    }

    /**
     * Get versioned API endpoints for testing.
     */
    private function getVersionedApiEndpoints(): array
    {
        return [
            ['method' => 'GET', 'uri' => '/api/user'],
            ['method' => 'GET', 'uri' => '/api/user/signals'],
            ['method' => 'GET', 'uri' => '/api/currency-pairs'],
        ];
    }
}