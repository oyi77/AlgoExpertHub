<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Illuminate\View\View;

class SystemToolsController extends Controller
{
    /**
     * System Tools Dashboard - Overview
     */
    public function dashboard(): View
    {
        $data = [
            'title' => 'System Tools Dashboard',
        ];

        return view('algoexpert-plus::backend.system-tools.dashboard', $data);
    }
}
