#!/bin/bash

# Batch create unit test scaffolds for all services without tests

SERVICES=(
    "CacheManager"
    "EmailVerification"
    "GlobalConfigurationService"
    "ThemeManager"
    "SignalModificationService"
    "DataLoadingOptimizationService"
    "AdminProfileService"
    "AdminUserService"
    "PerformanceOptimizationService"
    "DatabaseBackupService"
    "UserService"
    "DepositService"
    "WithdrawService"
    "TicketService"
    "TransactionService"
    "ReferralService"
    "LanguageService"
    "TemplateService"
    "PageService"
    "ContentService"
    "MediaService"
    "AnalyticsService"
    "AuditService"
    "NotificationService"
    "ReportService"
)

cd /opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/main

for service in "${SERVICES[@]}"; do
    test_file="tests/Unit/Services/${service}Test.php"
    
    if [ ! -f "$test_file" ]; then
        cat > "$test_file" << EOF
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\\${service};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for ${service}
 */
class ${service}Test extends TestCase
{
    use RefreshDatabase;

    private ${service} \$service;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->service = app(${service}::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        \$this->assertInstanceOf(${service}::class, \$this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        \$this->assertTrue(method_exists(\$this->service, '__construct'));
    }
}
EOF
        echo "Created $test_file"
    fi
done

echo "Test scaffold generation complete!"
