<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Content;
use App\Models\Language;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class SectionControllerTest extends TestCase
{
    // use RefreshDatabase; // Skipping refresh database to avoid wiping data if not properly configured

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user for authentication
        $this->admin = Admin::where('email', 'admin@example.com')->first();
        if (!$this->admin) {
            $this->admin = Admin::firstOrNew(['username' => 'admin']);
            $this->admin->email = 'admin@example.com';
            $this->admin->password = Hash::make('password');
            $this->admin->save();
        }
    }

    public function test_update_section_content()
    {
        // Setup
        $name = 'test_section_' . uniqid();
        $theme = 'default';
        $contentData = ['component' => 'test'];
        $cssData = '.test { color: red; }';
        $htmlData = '<div>Test</div>';

        // Create language if not exists (needed for default ID)
        $lang = Language::where('status', 0)->first();
        if (!$lang) {
            $lang = new Language();
            $lang->name = 'English';
            $lang->code = 'en';
            $lang->status = 0;
            $lang->save();
        }

        // Mock request data
        $data = [
            'content' => $contentData,
            'css' => $cssData,
            'html' => $htmlData,
            'theme' => $theme
        ];

        // Ensure we are making a JSON request authenticated as admin
        $response = $this->actingAs($this->admin, 'admin')
                         ->putJson(route('admin.page-builder.sections.update', $name), $data);

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Section updated successfully'
            ]);

        // Assert database
        $this->assertDatabaseHas('contents', [
            'name' => $name,
            'type' => 'iteratable',
            'theme' => $theme,
        ]);

        $content = Content::where('name', $name)
            ->where('type', 'iteratable')
            ->where('theme', $theme)
            ->first();

        $this->assertNotNull($content);

        // Decode content to check structure
        $savedContent = $content->content;

        // $savedContent should be an object because of Accessor
        $this->assertIsObject($savedContent);
        $this->assertObjectHasProperty('components', $savedContent);
        $this->assertObjectHasProperty('css', $savedContent);
        $this->assertObjectHasProperty('html', $savedContent);

        // Check values
        // Note: json_decode returns object, so array input became object/array mixed depending on depth
        // But here 'component' => 'test' simple array
        $this->assertEquals($cssData, $savedContent->css);
        $this->assertEquals($htmlData, $savedContent->html);

        // Cleanup
        $content->delete();
    }
}
