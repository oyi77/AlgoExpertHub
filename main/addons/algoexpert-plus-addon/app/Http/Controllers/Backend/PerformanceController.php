<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    /**
     * Performance optimization page
     * Includes the existing performance view from backend.setting.performance
     */
    public function index(): View
    {
        $data = [
            'title' => 'Performance Settings',
        ];

        return view('algoexpert-plus::backend.system-tools.performance', $data);
    }
}
