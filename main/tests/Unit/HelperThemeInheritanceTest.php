<?php

namespace Tests\Unit;

use App\Helpers\Helper\Helper;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class HelperThemeInheritanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure default theme exists
        $defaultThemePath = resource_path('views/frontend/default');
        if (!file_exists($defaultThemePath)) {
            mkdir($defaultThemePath, 0755, true);
        }
    }

    /**
     * Test themeView falls back to parent theme when view doesn't exist
     */
    public function test_theme_view_falls_back_to_parent_theme()
    {
        // Set active theme to blue
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'blue',
        ]);
        $config->theme = 'blue';
        $config->save();

        // Mock view existence check
        View::shouldReceive('exists')
            ->with('frontend.blue.test.view')
            ->once()
            ->andReturn(false);
        
        View::shouldReceive('exists')
            ->with('frontend.default.test.view')
            ->once()
            ->andReturn(true);

        $result = Helper::themeView('test.view');
        $this->assertEquals('frontend.default.test.view', $result);
    }

    /**
     * Test themeView uses current theme when view exists
     */
    public function test_theme_view_uses_current_theme_when_exists()
    {
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'blue',
        ]);
        $config->theme = 'blue';
        $config->save();

        View::shouldReceive('exists')
            ->with('frontend.blue.test.view')
            ->once()
            ->andReturn(true);

        $result = Helper::themeView('test.view');
        $this->assertEquals('frontend.blue.test.view', $result);
    }

    /**
     * Test themeView falls back to default for default theme
     */
    public function test_theme_view_falls_back_to_default_for_default_theme()
    {
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'default',
        ]);
        $config->theme = 'default';
        $config->save();

        $result = Helper::themeView('test.view');
        $this->assertEquals('frontend.default.test.view', $result);
    }

    /**
     * Test cssLib falls back to parent theme asset
     */
    public function test_css_lib_falls_back_to_parent_theme()
    {
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'blue',
        ]);
        $config->theme = 'blue';
        $config->save();

        // Create default theme CSS file
        $defaultCssPath = public_path('asset/frontend/default/css/test.css');
        $defaultCssDir = dirname($defaultCssPath);
        if (!file_exists($defaultCssDir)) {
            mkdir($defaultCssDir, 0755, true);
        }
        file_put_contents($defaultCssPath, '/* test */');

        // Ensure blue theme doesn't have the file
        $blueCssPath = public_path('asset/frontend/blue/css/test.css');
        if (file_exists($blueCssPath)) {
            unlink($blueCssPath);
        }

        $result = Helper::cssLib('frontend', 'test.css');
        $this->assertStringContainsString('asset/frontend/default/css/test.css', $result);

        // Cleanup
        if (file_exists($defaultCssPath)) {
            unlink($defaultCssPath);
        }
    }

    /**
     * Test jsLib falls back to parent theme asset
     */
    public function test_js_lib_falls_back_to_parent_theme()
    {
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'blue',
        ]);
        $config->theme = 'blue';
        $config->save();

        // Create default theme JS file
        $defaultJsPath = public_path('asset/frontend/default/js/test.js');
        $defaultJsDir = dirname($defaultJsPath);
        if (!file_exists($defaultJsDir)) {
            mkdir($defaultJsDir, 0755, true);
        }
        file_put_contents($defaultJsPath, '// test');

        // Ensure blue theme doesn't have the file
        $blueJsPath = public_path('asset/frontend/blue/js/test.js');
        if (file_exists($blueJsPath)) {
            unlink($blueJsPath);
        }

        $result = Helper::jsLib('frontend', 'test.js');
        $this->assertStringContainsString('asset/frontend/default/js/test.js', $result);

        // Cleanup
        if (file_exists($defaultJsPath)) {
            unlink($defaultJsPath);
        }
    }

    /**
     * Test getFile falls back to parent theme
     */
    public function test_get_file_falls_back_to_parent_theme()
    {
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'blue',
        ]);
        $config->theme = 'blue';
        $config->save();

        // Create default theme image file
        $defaultImagePath = public_path('asset/frontend/default/images/test/test.png');
        $defaultImageDir = dirname($defaultImagePath);
        if (!file_exists($defaultImageDir)) {
            mkdir($defaultImageDir, 0755, true);
        }
        file_put_contents($defaultImagePath, 'test');

        // Ensure blue theme doesn't have the file
        $blueImagePath = public_path('asset/frontend/blue/images/test/test.png');
        if (file_exists($blueImagePath)) {
            unlink($blueImagePath);
        }

        $result = Helper::getFile('test', 'test.png');
        $this->assertStringContainsString('asset/frontend/default/images/test/test.png', $result);

        // Cleanup
        if (file_exists($defaultImagePath)) {
            unlink($defaultImagePath);
        }
    }
}

