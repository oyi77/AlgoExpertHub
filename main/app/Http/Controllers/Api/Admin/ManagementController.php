<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Page;
use App\Models\Configuration;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Admin Management
 *
 * Endpoints for managing system settings, themes, and frontend content.
 */
class ManagementController extends Controller
{
    /**
     * Get Pages
     *
     * Retrieve all frontend pages.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function getPages()
    {
        $pages = Page::orderBy('order', 'ASC')->get();

        return response()->json([
            'success' => true,
            'data' => $pages
        ]);
    }

    /**
     * Create Page
     *
     * Create a new frontend page.
     *
     * @bodyParam name string required Page name. Example: About Us
     * @bodyParam slug string required Page slug. Example: about-us
     * @bodyParam content string Page content.
     * @bodyParam status boolean Page status. Example: true
     * @response 201 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function createPage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'nullable|string',
            'status' => 'boolean',
            'is_dropdown' => 'boolean',
            'order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $page = Page::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $page
        ], 201);
    }

    /**
     * Update Page
     *
     * Update an existing page.
     *
     * @urlParam id int required Page ID. Example: 1
     * @bodyParam name string Page name.
     * @bodyParam content string Page content.
     * @bodyParam status boolean Page status.
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function updatePage(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:pages,slug,' . $id,
            'content' => 'nullable|string',
            'status' => 'boolean',
            'is_dropdown' => 'boolean',
            'order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $page->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $page
        ]);
    }

    /**
     * Delete Page
     *
     * Delete a page.
     *
     * @urlParam id int required Page ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Page deleted successfully"
     * }
     */
    public function deletePage($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully'
        ]);
    }

    /**
     * Get Frontend Sections
     *
     * Retrieve all frontend content sections.
     *
     * @queryParam theme string Theme name. Example: default
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function getSections(Request $request)
    {
        $theme = $request->get('theme', Helper::config()->theme ?? 'default');

        $sections = Content::where('theme', $theme)
            ->select('name', 'type')
            ->groupBy('name', 'type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    /**
     * Get Section Content
     *
     * Retrieve content for a specific section.
     *
     * @urlParam name string required Section name. Example: banner
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function getSectionContent($name)
    {
        $theme = Helper::config()->theme ?? 'default';

        $fixedContent = Content::where('name', $name)
            ->where('theme', $theme)
            ->where('type', 'non_iteratable')
            ->first();

        $elements = Content::where('name', $name)
            ->where('theme', $theme)
            ->where('type', 'iteratable')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'fixed' => $fixedContent,
                'elements' => $elements
            ]
        ]);
    }

    /**
     * Update Section Content
     *
     * Update content for a section.
     *
     * @urlParam name string required Section name. Example: banner
     * @bodyParam content object Section content data.
     * @response 200 {
     *   "success": true,
     *   "message": "Section updated successfully"
     * }
     */
    public function updateSectionContent(Request $request, $name)
    {
        $theme = Helper::config()->theme ?? 'default';

        $content = Content::where('name', $name)
            ->where('theme', $theme)
            ->where('type', 'non_iteratable')
            ->first();

        if (!$content) {
            $content = new Content();
            $content->name = $name;
            $content->theme = $theme;
            $content->type = 'non_iteratable';
        }

        $content->content = $request->input('content', []);
        $content->save();

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $content
        ]);
    }

    /**
     * Get System Settings
     *
     * Retrieve current system configuration.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function getSettings()
    {
        $config = Configuration::first();

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Update System Settings
     *
     * Update system configuration.
     *
     * @bodyParam site_name string Site name.
     * @bodyParam currency string Currency code.
     * @bodyParam theme string Active theme.
     * @response 200 {
     *   "success": true,
     *   "message": "Settings updated successfully"
     * }
     */
    public function updateSettings(Request $request)
    {
        $config = Configuration::first();

        if (!$config) {
            $config = new Configuration();
        }

        // Only update allowed fields
        $allowedFields = [
            'site_name', 'site_logo', 'site_favicon', 'currency', 'currency_symbol',
            'theme', 'backend_theme', 'pagination', 'decimal_precision',
            'email_method', 'email_sent_from', 'smtp_host', 'smtp_port'
        ];

        foreach ($allowedFields as $field) {
            if ($request->has($field)) {
                $config->$field = $request->input($field);
            }
        }

        $config->save();

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $config
        ]);
    }
}
