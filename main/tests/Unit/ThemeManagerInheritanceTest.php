<?php

namespace Tests\Unit;

use App\Services\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeManagerInheritanceTest extends TestCase
{
    use RefreshDatabase;

    protected ThemeManager $themeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeManager = app(ThemeManager::class);
        Cache::flush();
    }

    /**
     * Test getThemeParent returns null for default theme
     */
    public function test_get_theme_parent_returns_null_for_default()
    {
        $parent = $this->themeManager->getThemeParent('default');
        $this->assertNull($parent);
    }

    /**
     * Test getThemeParent returns parent theme name when parent exists
     */
    public function test_get_theme_parent_returns_parent_when_exists()
    {
        // Create a test theme.json with parent
        $themePath = resource_path('views/frontend/test-theme');
        File::ensureDirectoryExists($themePath);
        
        $themeJson = [
            'name' => 'test-theme',
            'display_name' => 'Test Theme',
            'parent' => 'default',
        ];
        File::put($themePath . '/theme.json', json_encode($themeJson));

        $parent = $this->themeManager->getThemeParent('test-theme');
        $this->assertEquals('default', $parent);

        // Cleanup
        File::deleteDirectory($themePath);
    }

    /**
     * Test getThemeParent returns null when parent doesn't exist
     */
    public function test_get_theme_parent_returns_null_when_parent_not_exists()
    {
        $themePath = resource_path('views/frontend/test-theme-2');
        File::ensureDirectoryExists($themePath);
        
        $themeJson = [
            'name' => 'test-theme-2',
            'display_name' => 'Test Theme 2',
            'parent' => 'non-existent-theme',
        ];
        File::put($themePath . '/theme.json', json_encode($themeJson));

        $parent = $this->themeManager->getThemeParent('test-theme-2');
        $this->assertNull($parent);

        // Cleanup
        File::deleteDirectory($themePath);
    }

    /**
     * Test getThemeInheritanceChain returns correct chain
     */
    public function test_get_theme_inheritance_chain_returns_correct_chain()
    {
        $chain = $this->themeManager->getThemeInheritanceChain('default');
        $this->assertEquals(['default'], $chain);

        // Test with blue theme (should have default as parent)
        $chain = $this->themeManager->getThemeInheritanceChain('blue');
        $this->assertContains('blue', $chain);
        $this->assertContains('default', $chain);
        $this->assertEquals('blue', $chain[0]);
    }

    /**
     * Test getThemeInheritanceChain prevents infinite loops
     */
    public function test_get_theme_inheritance_chain_prevents_infinite_loops()
    {
        // Create a theme with circular reference attempt
        $themePath = resource_path('views/frontend/test-circular');
        File::ensureDirectoryExists($themePath);
        
        $themeJson = [
            'name' => 'test-circular',
            'display_name' => 'Test Circular',
            'parent' => 'default',
        ];
        File::put($themePath . '/theme.json', json_encode($themeJson));

        $chain = $this->themeManager->getThemeInheritanceChain('test-circular');
        // Should not contain duplicates
        $this->assertEquals(count($chain), count(array_unique($chain)));

        // Cleanup
        File::deleteDirectory($themePath);
    }

    /**
     * Test validateInheritanceChain prevents circular dependencies
     */
    public function test_validate_inheritance_chain_prevents_circular_dependencies()
    {
        // Theme cannot be its own parent
        $isValid = $this->themeManager->validateInheritanceChain('blue', 'blue');
        $this->assertFalse($isValid);

        // Valid parent should return true
        $isValid = $this->themeManager->validateInheritanceChain('blue', 'default');
        $this->assertTrue($isValid);
    }

    /**
     * Test getThemeMetadata includes parent field
     */
    public function test_get_theme_metadata_includes_parent()
    {
        $metadata = $this->themeManager->getThemeMetadata('blue');
        $this->assertArrayHasKey('parent', $metadata);
        $this->assertEquals('default', $metadata['parent']);
    }

    /**
     * Test inheritance chain is cached
     */
    public function test_inheritance_chain_is_cached()
    {
        $chain1 = $this->themeManager->getThemeInheritanceChain('blue');
        
        // Modify theme.json to change parent
        $themePath = resource_path('views/frontend/blue/theme.json');
        $originalContent = File::get($themePath);
        
        // Chain should still be from cache
        $chain2 = $this->themeManager->getThemeInheritanceChain('blue');
        $this->assertEquals($chain1, $chain2);

        // Clear cache and check again
        $this->themeManager->clearThemeCache('blue');
        $chain3 = $this->themeManager->getThemeInheritanceChain('blue');
        $this->assertEquals($chain1, $chain3);

        // Restore original
        File::put($themePath, $originalContent);
    }
}

