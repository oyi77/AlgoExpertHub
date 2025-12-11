<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;

/**
 * @group Frontend Content
 *
 * Endpoints for retrieving dynamic frontend content (sections).
 */
class ContentController extends Controller
{
    /**
     * Get Section Content
     *
     * Retrieve the content for a specific frontend section (e.g., 'banner', 'about', 'testimonial').
     *
     * @urlParam name string required The name of the section. Example: banner
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "fixed": {
     *       "heading": "Welcome to AI Trade Pulse",
     *       "sub_heading": "Automated Trading with AI"
     *     },
     *     "elements": [
     *       {
     *         "icon": "fa-chart",
     *         "title": "Analytics"
     *       }
     *     ],
     *     "media_base_url": "https://aitradepulse.com/asset/frontend/default/images/banner/"
     *   }
     * }
     */
    public function index($name)
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

        // Construct media base URL
        // From Helper::filePath: 'asset/frontend/' . $theme . '/images/' . $folder_name;
        $mediaPath = 'asset/frontend/' . $theme . '/images/' . $name . '/';
        $mediaBaseUrl = asset($mediaPath);

        return response()->json([
            'success' => true,
            'data' => [
                'fixed' => $fixedContent ? $fixedContent->content : (object)[],
                'elements' => $elements->map(function ($item) {
                    return $item->content;
                }),
                'media_base_url' => $mediaBaseUrl
            ]
        ]);
    }
}
