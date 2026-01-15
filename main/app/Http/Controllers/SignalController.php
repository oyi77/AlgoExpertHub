<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Services\SignalService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;

class SignalController extends Controller
{
    protected SignalService $signalService;

    public function __construct(SignalService $signalService)
    {
        $this->signalService = $signalService;
    }

    public function allSignals(Request $request): View
    {
        $data['title'] = 'All Signals';
        
        $result = $this->signalService->allSignals(['search' => $request->search]);
        $data['signals'] = $result['data']['signals'];

        return view(Helper::themeView('user.signals'))->with($data);
    }

    public function details(int $id): View
    {
        $data['title'] = 'Signal Description';

        $result = $this->signalService->details($id);

        if ($result['type'] === 'error') {
            abort($result['code'] ?? 404);
        }

        $data['signal'] = $result['data']['signal'];

        return view(Helper::themeView('user.signal_details'))->with($data);
    }

    public function betaIndex(Request $request)
    {
        $data['title'] = 'All Signals';
        $data['search'] = $request->search ?? '';

        $result = $this->signalService->allSignals(['search' => $request->search]);
        $data['signals'] = $result['data']['signals'];

        return Inertia::render('User/SignalCenter', $data);
    }

    public function betaDetails(int $id)
    {
        $data['title'] = 'Signal Description';

        $result = $this->signalService->details($id);

        if ($result['type'] === 'error') {
            abort($result['code'] ?? 404);
        }

        $data['signal'] = $result['data']['signal'];

        return Inertia::render('User/SignalDetails', $data);
    }
}
