<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TradingBotWizardValidationService
{
    /**
     * Validate a wizard step
     */
    public function validateStep(Request $request, int $step, array $wizardData): array
    {
        switch ($step) {
            case 1:
                return $this->validateStep1($request);
            case 2:
                return $this->validateStep2($request);
            case 3:
                return $this->validateStep3($request);
            case 4:
                return $this->validateStep4($request, $wizardData);
            default:
                return [
                    'valid' => false,
                    'errors' => ['step' => 'Invalid step number'],
                ];
        }
    }

    /**
     * Validate step 1: Exchange Connection
     */
    protected function validateStep1(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'exchange_connection_id' => 'required|exists:execution_connections,id',
        ], [
            'exchange_connection_id.required' => 'Please select an exchange connection',
            'exchange_connection_id.exists' => 'Selected exchange connection does not exist',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        // Verify connection belongs to user and is active
        $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::find($request->exchange_connection_id);
        
        if (!$connection) {
            return [
                'valid' => false,
                'errors' => ['exchange_connection_id' => 'Exchange connection not found'],
            ];
        }

        // Check ownership (user or admin-owned)
        $isUserOwned = $connection->user_id === auth()->id() && !$connection->is_admin_owned;
        $isAdminOwned = $connection->is_admin_owned;
        
        if (!$isUserOwned && !$isAdminOwned) {
            return [
                'valid' => false,
                'errors' => ['exchange_connection_id' => 'You do not have access to this connection'],
            ];
        }

        // Check if connection is active (warning but not blocking)
        if (!$connection->is_active) {
            Log::warning('Wizard: User selected inactive connection', [
                'connection_id' => $connection->id,
                'user_id' => auth()->id(),
            ]);
        }

        return [
            'valid' => true,
            'data' => [
                'exchange_connection_id' => $request->exchange_connection_id,
            ],
        ];
    }

    /**
     * Validate step 2: Trading Preset
     */
    protected function validateStep2(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'trading_preset_id' => 'required|exists:trading_presets,id',
        ], [
            'trading_preset_id.required' => 'Please select a trading preset',
            'trading_preset_id.exists' => 'Selected trading preset does not exist',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        // Verify preset exists and is accessible
        $preset = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::find($request->trading_preset_id);
        
        if (!$preset) {
            return [
                'valid' => false,
                'errors' => ['trading_preset_id' => 'Trading preset not found'],
            ];
        }

        // Check if preset is user-owned or admin-owned (public)
        $isUserOwned = $preset->user_id === auth()->id();
        $isPublic = $preset->is_admin_owned || $preset->visibility === 'public';
        
        if (!$isUserOwned && !$isPublic) {
            return [
                'valid' => false,
                'errors' => ['trading_preset_id' => 'You do not have access to this preset'],
            ];
        }

        return [
            'valid' => true,
            'data' => [
                'trading_preset_id' => $request->trading_preset_id,
            ],
        ];
    }

    /**
     * Validate step 3: Filter Strategy (optional) and AI Profile (optional)
     */
    protected function validateStep3(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
        ], [
            'filter_strategy_id.exists' => 'Selected filter strategy does not exist',
            'ai_model_profile_id.exists' => 'Selected AI model profile does not exist',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        $data = [];

        // Validate filter strategy if provided
        if ($request->has('filter_strategy_id') && $request->filter_strategy_id) {
            $filterStrategy = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::find($request->filter_strategy_id);
            
            if (!$filterStrategy) {
                return [
                    'valid' => false,
                    'errors' => ['filter_strategy_id' => 'Filter strategy not found'],
                ];
            }

            // Check access
            $isUserOwned = $filterStrategy->user_id === auth()->id();
            $isPublic = $filterStrategy->is_admin_owned || $filterStrategy->visibility === 'public';
            
            if (!$isUserOwned && !$isPublic) {
                return [
                    'valid' => false,
                    'errors' => ['filter_strategy_id' => 'You do not have access to this filter strategy'],
                ];
            }

            $data['filter_strategy_id'] = $request->filter_strategy_id;
        }

        // Validate AI profile if provided
        if ($request->has('ai_model_profile_id') && $request->ai_model_profile_id) {
            $aiProfile = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::find($request->ai_model_profile_id);
            
            if (!$aiProfile) {
                return [
                    'valid' => false,
                    'errors' => ['ai_model_profile_id' => 'AI model profile not found'],
                ];
            }

            // Check access
            $isUserOwned = $aiProfile->user_id === auth()->id();
            $isPublic = $aiProfile->is_admin_owned || $aiProfile->visibility === 'public';
            
            if (!$isUserOwned && !$isPublic) {
                return [
                    'valid' => false,
                    'errors' => ['ai_model_profile_id' => 'You do not have access to this AI profile'],
                ];
            }

            $data['ai_model_profile_id'] = $request->ai_model_profile_id;
        }

        return [
            'valid' => true,
            'data' => $data,
        ];
    }

    /**
     * Validate step 4: Review and Bot Name
     */
    protected function validateStep4(Request $request, array $wizardData): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_paper_trading' => 'nullable|boolean',
            'trading_mode' => 'nullable|in:SIGNAL_BASED,MARKET_STREAM_BASED',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        $data = [];

        if ($request->has('name')) {
            $data['name'] = $request->name;
        }

        if ($request->has('description')) {
            $data['description'] = $request->description;
        }

        if ($request->has('is_paper_trading')) {
            $data['is_paper_trading'] = $request->boolean('is_paper_trading', true);
        }

        if ($request->has('trading_mode')) {
            $data['trading_mode'] = $request->trading_mode;
        }

        return [
            'valid' => true,
            'data' => $data,
        ];
    }

    /**
     * Validate all previous steps are completed
     */
    public function validatePreviousSteps(array $wizardData, int $currentStep): array
    {
        // Step 1 is required for all subsequent steps
        if ($currentStep > 1 && empty($wizardData['exchange_connection_id'])) {
            return [
                'valid' => false,
                'required_step' => 1,
                'message' => 'Please select an exchange connection first',
            ];
        }

        // Step 2 is required for steps 3 and 4
        if ($currentStep > 2 && empty($wizardData['trading_preset_id'])) {
            return [
                'valid' => false,
                'required_step' => 2,
                'message' => 'Please select a trading preset first',
            ];
        }

        return [
            'valid' => true,
        ];
    }

    /**
     * Validate all steps are complete before creating bot
     */
    public function validateAllSteps(array $wizardData): array
    {
        // Required fields
        if (empty($wizardData['exchange_connection_id'])) {
            return [
                'valid' => false,
                'required_step' => 1,
                'message' => 'Exchange connection is required',
            ];
        }

        if (empty($wizardData['trading_preset_id'])) {
            return [
                'valid' => false,
                'required_step' => 2,
                'message' => 'Trading preset is required',
            ];
        }

        // Verify connections still exist and are accessible
        $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::find($wizardData['exchange_connection_id']);
        if (!$connection) {
            return [
                'valid' => false,
                'required_step' => 1,
                'message' => 'Selected exchange connection no longer exists',
            ];
        }

        $preset = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::find($wizardData['trading_preset_id']);
        if (!$preset) {
            return [
                'valid' => false,
                'required_step' => 2,
                'message' => 'Selected trading preset no longer exists',
            ];
        }

        // Optional fields validation
        if (!empty($wizardData['filter_strategy_id'])) {
            $filterStrategy = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::find($wizardData['filter_strategy_id']);
            if (!$filterStrategy) {
                return [
                    'valid' => false,
                    'required_step' => 3,
                    'message' => 'Selected filter strategy no longer exists',
                ];
            }
        }

        if (!empty($wizardData['ai_model_profile_id'])) {
            $aiProfile = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::find($wizardData['ai_model_profile_id']);
            if (!$aiProfile) {
                return [
                    'valid' => false,
                    'required_step' => 3,
                    'message' => 'Selected AI model profile no longer exists',
                ];
            }
        }

        return [
            'valid' => true,
        ];
    }
}
