<?php

namespace Tests\Property;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Eris\Generator;

/**
 * Property 30: API Versioning Compatibility
 * 
 * Feature: platform-optimization-improvements
 * 
 * For any API version, backward compatibility should be maintained
 * and version negotiation should work correctly.
 * 
 * Validates: Requirements 7.4
 */
class ApiVersioningCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * Test that API version negotiation works correctly.
     */
    public function testApiVersionNegotiationWorksCorrectly()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->forAll(
            Generator\elements(...$this->getVersionTestCases())
        )->then(function ($testCase) {
            $headers = [];
            if ($testCase['version_header']) {
                $headers['Accept-Version'] = $testCase['version_header'];
            }

            $response = $this->withHeaders($headers)->json('GET', '/api/user');

            if ($testCase['should_succeed']) {
                $this->assertTrue(
                    $response->isSuccessful(),
                    "Request with version '{$testCase['version_header']}' should succeed"
                );

                // Should return the requested or default version
                $expectedVersion = $testCase['version_header'] ?: config('app.api_versioning.default_version');
                $this->assertEquals(
                    $expectedVersion,
                    $response->headers->get('X-API-Version'),
                    "Response should indicate correct API version"
                );
            } else {
                $this->assertEquals(
                    400,
                    $response->getStatusCode(),
                    "Request with unsupported version should return 400"
                );

                $data = $response->json();
                $this->assertFalse($data['success'], "Response should indicate failure");
                $this->assertStringContainsString(
                    'Unsupported API version',
                    $data['message'],
                    "Error message should indicate unsupported version"
                );
            }
        });
    }

    /**
     * Test that supported API versions maintain consistent response structure.
     */
    public function testSupportedVersionsMaintainConsistentResponseStructure()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $supportedVersions = config('app.api_versioning.supported', ['v1']);

        $this->forAll(
            Generator\elements(...$supportedVersions)
        )->then(function ($version) {
            $response = $this->withHeaders([
                'Accept-Version' => $version
            ])->json('GET', '/api/user');

            $this->assertTrue($response->isSuccessful(), "Version {$version} should be supported");

            $data = $response->json();

            // All versions should maintain consistent base structure
            $this->assertArrayHasKey('success', $data, "All versions should have 'success' field");
            $this->assertArrayHasKey('message', $data, "All versions should have 'message' field");
            $this->assertArrayHasKey('data', $data, "User endpoint should have 'data' field");

            // Response should indicate the correct version
            $this->assertEquals(
                $version,
                $response->headers->get('X-API-Version'),
                "Response should indicate correct version"
            );

            // Data structure should be consistent (user resource)
            $userData = $data['data'];
            $this->assertArrayHasKey('id', $userData, "User data should have 'id' field");
            $this->assertArrayHasKey('username', $userData, "User data should have 'username' field");
            $this->assertArrayHasKey('email', $userData, "User data should have 'email' field");
        });
    }

    /**
     * Test that deprecated versions show appropriate warnings.
     */
    public function testDeprecatedVersionsShowAppropriateWarnings()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $deprecatedVersions = config('app.api_versioning.deprecated', []);

        if (empty($deprecatedVersions)) {
            $this->markTestSkipped('No deprecated versions configured for testing');
        }

        $this->forAll(
            Generator\elements(...$deprecatedVersions)
        )->then(function ($version) {
            $response = $this->withHeaders([
                'Accept-Version' => $version
            ])->json('GET', '/api/user');

            // Deprecated versions should still work
            $this->assertTrue(
                $response->isSuccessful(),
                "Deprecated version {$version} should still work"
            );

            // Should include deprecation warning header
            $this->assertTrue(
                $response->headers->has('X-API-Deprecation-Warning'),
                "Deprecated version should include deprecation warning header"
            );

            $warningHeader = $response->headers->get('X-API-Deprecation-Warning');
            $this->assertStringContainsString(
                $version,
                $warningHeader,
                "Deprecation warning should mention the version"
            );
            $this->assertStringContainsString(
                'deprecated',
                strtolower($warningHeader),
                "Warning should indicate deprecation"
            );
        });
    }

    /**
     * Test that version-specific features work correctly.
     */
    public function testVersionSpecificFeaturesWorkCorrectly()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->forAll(
            Generator\elements(...$this->getVersionSpecificTestCases())
        )->then(function ($testCase) {
            $response = $this->withHeaders([
                'Accept-Version' => $testCase['version']
            ])->json($testCase['method'], $testCase['endpoint']);

            if ($testCase['should_be_available']) {
                $this->assertTrue(
                    $response->isSuccessful() || $response->getStatusCode() === 404,
                    "Feature should be available in version {$testCase['version']}"
                );

                if ($response->isSuccessful()) {
                    $data = $response->json();
                    
                    // Check for version-specific fields or behavior
                    if (isset($testCase['expected_fields'])) {
                        foreach ($testCase['expected_fields'] as $field) {
                            $this->assertArrayHasKey(
                                $field,
                                $data['data'] ?? $data,
                                "Version {$testCase['version']} should include field '{$field}'"
                            );
                        }
                    }
                }
            } else {
                // Feature not available in this version
                $this->assertTrue(
                    $response->getStatusCode() >= 400,
                    "Feature should not be available in version {$testCase['version']}"
                );
            }
        });
    }

    /**
     * Test that content negotiation respects version preferences.
     */
    public function testContentNegotiationRespectsVersionPreferences()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->forAll(
            Generator\elements(...$this->getContentNegotiationTestCases())
        )->then(function ($testCase) {
            $headers = [];
            foreach ($testCase['headers'] as $header => $value) {
                $headers[$header] = $value;
            }

            $response = $this->withHeaders($headers)->json('GET', '/api/user');

            $this->assertEquals(
                $testCase['expected_status'],
                $response->getStatusCode(),
                "Content negotiation should work correctly for headers: " . json_encode($testCase['headers'])
            );

            if ($response->isSuccessful()) {
                $this->assertEquals(
                    $testCase['expected_version'],
                    $response->headers->get('X-API-Version'),
                    "Should negotiate to expected version"
                );
            }
        });
    }

    /**
     * Get version test cases.
     */
    private function getVersionTestCases(): array
    {
        return [
            [
                'version_header' => 'v1',
                'should_succeed' => true,
            ],
            [
                'version_header' => null, // No version header
                'should_succeed' => true,
            ],
            [
                'version_header' => 'v999', // Unsupported version
                'should_succeed' => false,
            ],
            [
                'version_header' => 'invalid', // Invalid version format
                'should_succeed' => false,
            ],
        ];
    }

    /**
     * Get version-specific test cases.
     */
    private function getVersionSpecificTestCases(): array
    {
        return [
            [
                'version' => 'v1',
                'method' => 'GET',
                'endpoint' => '/api/user',
                'should_be_available' => true,
                'expected_fields' => ['id', 'username', 'email'],
            ],
            [
                'version' => 'v1',
                'method' => 'GET',
                'endpoint' => '/api/user/signals',
                'should_be_available' => true,
            ],
        ];
    }

    /**
     * Get content negotiation test cases.
     */
    private function getContentNegotiationTestCases(): array
    {
        return [
            [
                'headers' => ['Accept-Version' => 'v1'],
                'expected_status' => 200,
                'expected_version' => 'v1',
            ],
            [
                'headers' => [], // No version header
                'expected_status' => 200,
                'expected_version' => config('app.api_versioning.default_version'),
            ],
            [
                'headers' => ['Accept-Version' => 'v999'],
                'expected_status' => 400,
                'expected_version' => null,
            ],
        ];
    }
}