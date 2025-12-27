<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Helpers\Helper\Helper;
use App\Models\Admin;
use App\Notifications\DepositNotification;

class Manual extends BaseAdapter
{
    /**
     * Process manual payment.
     */
    public static function process($request, $gateway, float $amount, $deposit): array
    {
        $validation = [];
        if ($gateway->parameter->user_proof_param != null) {
            foreach ($gateway->parameter->user_proof_param as $params) {
                $params = (array) $params;
                $key = strtolower(str_replace(' ', '_', $params['field_name']));
                $validationRules = $params['validation'] == 'required' ? 'required' : 'sometimes';

                if ($params['type'] == 'text' || $params['type'] == 'textarea') {
                    $validation[$key] = $validationRules;
                } else {
                    $validation[$key] = $validationRules . "|image|mimes:jpg,png,jpeg|max:2048";
                }
            }
        }

        $data = $request->validate($validation);

        foreach ($data as $key => $upload) {
            if ($request->hasFile($key)) {
                $filename = Helper::saveImage($upload, Helper::filePath('manual_payment'));
                $data[$key] = ['file' => $filename, 'type' => 'file'];
            }
        }

        $deposit->payment_proof = $data;
        $deposit->type = 0;
        $deposit->status = 2;
        $deposit->save();

        session()->put('manual', 'yes');

        $admin = Admin::where('type', 'super')->first();
        $type = session('type');

        if ($admin) {
            $admin->notify(new DepositNotification($deposit, 'offline', $type));
        }

        return (new static())->success('Payment proof submitted successfully');
    }
}
