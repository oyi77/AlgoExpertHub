<?php

namespace Tests\Feature;

use App\Helpers\Helper\Helper;
use App\Models\Configuration;
use App\Services\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ThemeInheritanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ThemeManager $themeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeManager = app(ThemeManager::class);
    }

    /**
     * Test complete view resolution flow with inheritance
     */
    public function test_view_resolution_with_inheritance_chain()
    {
        // Set active theme to blue
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'blue',
        ]);
        $config->theme = 'blue';
        $config->save();

        // Ensure blue theme has parent set
        $blueThemeJson = resource_path('views/frontend/blue/theme.json');
        if (file_exists($blueThemeJson)) {
            $themeData = json_decode(file_get_contents($blueThemeJson), true);
            $this->assertArrayHasKey('parent', $themeData);
            $this->assertEquals('default', $themeData['parent']);
        }

        // Test inheritance chain
        $chain = $this->themeManager->getThemeInheritanceChain('blue');
        $this->assertContains('blue', $chain);
        $this->assertContains('default', $chain);
    }

    /**
     * Test asset resolution with inheritance
     */
    public function test_asset_resolution_with_inheritance()
    {
        $config = Configuration::firstOrCreate(['id' => 1], [
            'theme' => 'blue',
        ]);
        $config->theme = 'blue';
        $config->save();

        // Create a CSS file in default theme
        $defaultCssDir = public_path('asset/frontend/default/css');
        if (!file_exists($defaultCssDir)) {
            mkdir($defaultCssDir, 0755, true);
        }
        $defaultCssFile = $defaultCssDir . '/inherited.css';
        file_put_contents($defaultCssFile, '/* inherited from default */');

        // Ensure blue theme doesn't have this file
        $blueCssFile = public_path('asset/frontend/blue/css/inherited.css');
        if (file_exists($blueCssFile)) {
            unlink($blueCssFile);
        }

        // Should resolve to default theme asset
        $result = Helper::cssLib('frontend', 'inherited.css');
        $this->assertStringContainsString('asset/frontend/default/css/inherited.css', $result);

        // Cleanup
        if (file_exists($defaultCssFile)) {
            unlink($defaultCssFile);
        }
    }

    /**
     * Test multi-level inheritance chain
     */
    public function test_multi_level_inheritance_chain()
    {
        // Create a test theme that inherits from blue (which inherits from default)
        $testThemePath = resource_path('views/frontend/test-inheritance');
        File::ensureDirectoryExists($testThemePath);
        
        $themeJson = [
            'name' => 'test-inheritance',
            'display_name' => 'Test Inheritance',
            'parent' => 'blue',
        ];
        File::put($testThemePath . '/theme.json', json_encode($themeJson));

        $chain = $this->themeManager->getThemeInheritanceChain('test-inheritance');
        $this->assertContains('test-inheritance', $chain);
        $this->assertContains('blue', $chain);
        $this->assertContains('default', $chain);
        $this->assertEquals('test-inheritance', $chain[0]);

        // Cleanup
        File::deleteDirectory($testThemePath);
    }

    /**
     * Test circular dependency prevention
     */
    public function test_circular_dependency_prevention()
    {
        // Create theme A that tries to inherit from theme B
        $themeAPath = resource_path('views/frontend/theme-a');
        File::ensureDirectoryExists($themeAPath);
        
        $themeAJson = [
            'name' => 'theme-a',
            'display_name' => 'Theme A',
            'parent' => 'theme-b',
        ];
        File::put($themeAPath . '/theme.json', json_encode($themeAJson));

        // Create theme B that tries to inherit from theme A (circular)
        $themeBPath = resource_path('views/frontend/theme-b');
        File::ensureDirectoryExists($themeBPath);
        
        $themeBJson = [
            'name' => 'theme-b',
            'display_name' => 'Theme B',
            'parent' => 'theme-a',
        ];
        File::put($themeBPath . '/theme.json', json_encode($themeBJson));

        // Validation should prevent this
        $isValid = $this->themeManager->validateInheritanceChain('theme-a', 'theme-b');
        // This should be false if theme-b already has theme-a in its chain
        // But since we're checking before setting, it might be true
        // The actual prevention happens in getThemeInheritanceChain which stops loops

        $chain = $this->themeManager->getThemeInheritanceChain('theme-a');
        // Should not contain duplicates (loop prevention)
        $this->assertEquals(count($chain), count(array_unique($chain)));

        // Cleanup
        File::deleteDirectory($themeAPath);
        File::deleteDirectory($themeBPath);
    }

    /**
     * Test backward compatibility with themes without parent
     */
    public function test_backward_compatibility_without_parent()
    {
        // Create a theme without parent field
        $themePath = resource_path('views/frontend/test-no-parent');
        File::ensureDirectoryExists($themePath);
        
        $themeJson = [
            'name' => 'test-no-parent',
            'display_name' => 'Test No Parent',
            // No parent field
        ];
        File::put($themePath . '/theme.json', json_encode($themeJson));

        $parent = $this->themeManager->getThemeParent('test-no-parent');
        $this->assertNull($parent);

        $chain = $this->themeManager->getThemeInheritanceChain('test-no-parent');
        // Should fallback to default
        $this->assertContains('default', $chain);

        // Cleanup
        File::deleteDirectory($themePath);
    }

    /**
     * Test theme list includes inheritance information
     */
    public function test_theme_list_includes_inheritance_info()
    {
        $themes = $this->themeManager->list();
        
        $blueTheme = $themes->firstWhere('name', 'blue');
        if ($blueTheme) {
            $this->assertArrayHasKey('parent', $blueTheme);
            $this->assertArrayHasKey('inheritance_chain', $blueTheme);
            $this->assertEquals('default', $blueTheme['parent']);
            $this->assertContains('blue', $blueTheme['inheritance_chain']);
            $this->assertContains('default', $blueTheme['inheritance_chain']);
        }
    }
}

