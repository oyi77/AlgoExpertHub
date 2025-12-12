<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Models\Gateway;

class DepositController extends Controller
{
    public function deposit()
    {
        $data['gateways'] = Gateway::where('status', 1)->latest()->get();

        $data['title'] = "Payment Methods";

        $data['type'] = 'deposit';

        return view(Helper::themeView("user.gateway.gateways"))->with($data);
    }
}
