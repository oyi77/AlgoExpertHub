<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ManualGatewayService;

/**
 * @group Admin APIs
 * Payment gateway management endpoints
 */
class GatewayController extends Controller
{
    protected $gateway;

    public function __construct(ManualGatewayService $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * List Gateways
     * 
     * Get all payment gateways
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam type integer Filter by type (0=offline, 1=online). Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $gateways = Gateway::when($request->type !== null, function($q) use ($request) {
            $q->where('type', $request->type);
        })->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $gateways
        ]);
    }

    /**
     * Create Gateway
     * 
     * Create a new payment gateway
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam name string required Gateway name. Example: paypal
     * @bodyParam type integer required Gateway type (0=offline, 1=online). Example: 1
     * @bodyParam parameter json required Gateway parameters. Example: {"api_key": "xxx"}
     * @bodyParam rate decimal required Conversion rate. Example: 1.0
     * @bodyParam charge decimal required Gateway charge. Example: 0.00
     * @bodyParam currency string required Currency. Example: USD
     * @response 201 {
     *   "success": true,
     *   "message": "Gateway created successfully"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:gateways,name',
            'type' => 'required|in:0,1',
            'parameter' => 'required|json',
            'rate' => 'required|numeric',
            'charge' => 'required|numeric',
            'currency' => 'required|string',
        ]);

        Gateway::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Gateway created successfully'
        ], 201);
    }

    /**
     * Get Gateway
     * 
     * Get gateway details
     * 
     * @param Gateway $gateway
     * @return JsonResponse
     * @authenticated
     * @urlParam gateway integer required Gateway ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show(Gateway $gateway): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $gateway
        ]);
    }

    /**
     * Update Gateway
     * 
     * Update gateway information
     * 
     * @param Request $request
     * @param Gateway $gateway
     * @return JsonResponse
     * @authenticated
     * @urlParam gateway integer required Gateway ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Gateway updated successfully"
     * }
     */
    public function update(Request $request, Gateway $gateway): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|unique:gateways,name,' . $gateway->id,
            'type' => 'sometimes|in:0,1',
            'parameter' => 'sometimes|json',
            'rate' => 'sometimes|numeric',
            'charge' => 'sometimes|numeric',
            'currency' => 'sometimes|string',
        ]);

        $gateway->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Gateway updated successfully'
        ]);
    }

    /**
     * Delete Gateway
     * 
     * Delete a payment gateway
     * 
     * @param Gateway $gateway
     * @return JsonResponse
     * @authenticated
     * @urlParam gateway integer required Gateway ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Gateway deleted successfully"
     * }
     */
    public function destroy(Gateway $gateway): JsonResponse
    {
        $gateway->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gateway deleted successfully'
        ]);
    }

    /**
     * Toggle Gateway Status
     * 
     * Activate or deactivate a gateway
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Gateway ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Gateway status updated successfully"
     * }
     */
    public function toggleStatus($id): JsonResponse
    {
        $gateway = Gateway::findOrFail($id);
        $gateway->status = $gateway->status ? 0 : 1;
        $gateway->save();

        return response()->json([
            'success' => true,
            'message' => 'Gateway status updated successfully'
        ]);
    }

    /**
     * Get online gateways
     */
    public function getOnlineGateways(): JsonResponse
    {
        $gateways = Gateway::where('type', 1)->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $gateways
        ]);
    }

    /**
     * Get offline gateways
     */
    public function getOfflineGateways(): JsonResponse
    {
        $gateways = Gateway::where('type', 0)->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $gateways
        ]);
    }

    /**
     * Update online gateway
     */
    public function updateOnlineGateway(Request $request, $id): JsonResponse
    {
        $gateway = Gateway::where('type', 1)->findOrFail($id);

        $request->validate([
            'parameter' => 'sometimes|json',
            'rate' => 'sometimes|numeric',
            'charge' => 'sometimes|numeric',
            'currency' => 'sometimes|string',
            'status' => 'sometimes|boolean',
        ]);

        $gateway->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Online gateway updated successfully',
            'data' => $gateway->fresh()
        ]);
    }

    /**
     * Update Gourl gateway
     */
    public function updateGourlGateway(Request $request): JsonResponse
    {
        $gateway = Gateway::where('name', 'gourl')->firstOrFail();

        $request->validate([
            'parameter' => 'required|json',
        ]);

        $gateway->update(['parameter' => $request->parameter]);

        return response()->json([
            'success' => true,
            'message' => 'Gourl gateway updated successfully',
            'data' => $gateway->fresh()
        ]);
    }

    /**
     * Create offline gateway
     */
    public function createOfflineGateway(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:gateways,name',
            'parameter' => 'required|json',
            'rate' => 'required|numeric',
            'charge' => 'required|numeric',
            'currency' => 'required|string',
        ]);

        $gateway = Gateway::create(array_merge($request->all(), ['type' => 0]));

        return response()->json([
            'success' => true,
            'message' => 'Offline gateway created successfully',
            'data' => $gateway
        ], 201);
    }

    /**
     * Update offline gateway
     */
    public function updateOfflineGateway(Request $request, $id): JsonResponse
    {
        $gateway = Gateway::where('type', 0)->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|unique:gateways,name,' . $gateway->id,
            'parameter' => 'sometimes|json',
            'rate' => 'sometimes|numeric',
            'charge' => 'sometimes|numeric',
            'currency' => 'sometimes|string',
            'status' => 'sometimes|boolean',
        ]);

        $gateway->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Offline gateway updated successfully',
            'data' => $gateway->fresh()
        ]);
    }
}
