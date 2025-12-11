<?php

namespace App\Http\Controllers\Api\User;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Configuration;
use App\Notifications\KycUpdateNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group KYC Verification
 *
 * Endpoints for managing Key Your Customer (KYC) verification.
 */
class KycController extends Controller
{
    /**
     * Get KYC Configuration
     *
     * Retrieve the current KYC verification status and the required form fields.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "status": 0,
     *     "status_label": "Unverified",
     *     "form_schema": [
     *       {
     *         "field_name": "National ID",
     *         "type": "file",
     *         "validation": "required"
     *       }
     *     ]
     *   }
     * }
     */
    public function index()
    {
        $user = auth()->user();
        $general = Configuration::first();

        $statusLabel = 'Unverified';
        if ($user->is_kyc_verified == 1) $statusLabel = 'Verified';
        if ($user->is_kyc_verified == 2) $statusLabel = 'Pending';
        if ($user->is_kyc_verified == 3) $statusLabel = 'Rejected';

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $user->is_kyc_verified,
                'status_label' => $statusLabel,
                'form_schema' => $general->kyc ?? [],
                'submitted_data' => $user->kyc_information
            ]
        ]);
    }

    /**
     * Submit KYC Documents
     *
     * Submit the KYC verification form with required documents.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "KYC submitted successfully"
     * }
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        if ($user->is_kyc_verified == 1) {
             return response()->json(['success' => false, 'message' => 'Already verified'], 400);
        }
        if ($user->is_kyc_verified == 2) {
             return response()->json(['success' => false, 'message' => 'Verification pending'], 400);
        }

        $general = Configuration::first();
        $validationRules = [];
        
        if ($general->kyc != null) {
            foreach ($general->kyc as $params) {
                $key = strtolower(str_replace(' ', '_', $params['field_name']));
                if ($params['type'] == 'text' || $params['type'] == 'textarea') {
                    $validationRules[$key] = $params['validation'] == 'required' ? 'required' : 'nullable';
                } else {
                    $validationRules[$key] = ($params['validation'] == 'required' ? 'required' : 'nullable') . "|image|mimes:jpg,png,jpeg|max:2048";
                }
            }
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        foreach ($data as $key => $upload) {
            if ($request->hasFile($key)) {
                $filename = Helper::saveImage($upload, Helper::filePath('user'));
                $data[$key] = ['file' => $filename, 'type' => 'file'];
            }
        }

        $user->kyc_information = $data;
        $user->is_kyc_verified = 2; // Pending
        $user->save();

        $admin = Admin::where('type','super')->first();
        if ($admin) {
             $admin->notify(new KycUpdateNotification($user));
        }

        return response()->json([
            'success' => true,
            'message' => 'KYC submitted successfully'
        ]);
    }
}
