<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class HorizonController extends Controller
{
    /**
     * Show embedded Horizon dashboard
     */
    public function index()
    {
        // Check if Horizon route exists
        if (!Route::has('horizon.index')) {
            abort(404, 'Horizon is not available');
        }

        $horizonUrl = route('horizon.index');
        
        return view('algoexpert-plus::backend.horizon.embedded', [
            'horizonUrl' => $horizonUrl,
        ]);
    }
}
