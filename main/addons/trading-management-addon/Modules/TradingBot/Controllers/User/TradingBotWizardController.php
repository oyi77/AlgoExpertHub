<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\TradingBot\Controllers\User;

use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWizardValidationService;
use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class TradingBotWizardController extends Controller
{
    protected TradingBotService $botService;
    protected TradingBotWizardValidationService $validationService;

    public function __construct(
        TradingBotService $botService,
        TradingBotWizardValidationService $validationService
    ) {
        $this->botService = $botService;
        $this->validationService = $validationService;
    }

    /**
     * Start the wizard - redirect to step 1
     */
    public function index(): RedirectResponse
    {
        // Clear any existing wizard data
        session()->forget('trading_bot_wizard');
        
        return redirect()->route('user.trading-management.trading-bots.wizard.step', ['step' => 1]);
    }

    /**
     * Show wizard step
     */
    public function step(int $step): View|RedirectResponse
    {
        // Validate step number
        if ($step < 1 || $step > 4) {
            return redirect()->route('user.trading-management.trading-bots.wizard')
                ->with('notify', NotificationHelper::error('Invalid wizard step', 'Error'));
        }

        // Get wizard data from session
        $wizardData = session('trading_bot_wizard', []);

        // Check if previous steps are completed (except for step 1)
        if ($step > 1) {
            $previousStepValid = $this->validationService->validatePreviousSteps($wizardData, $step);
            if (!$previousStepValid['valid']) {
                return redirect()
                    ->route('user.trading-management.trading-bots.wizard.step', ['step' => $previousStepValid['required_step']])
                    ->with('notify', NotificationHelper::error($previousStepValid['message'] ?? 'Please complete previous steps first', 'Error'));
            }
        }

        // Prepare data for the step
        $data = $this->prepareStepData($step, $wizardData);

        return view("trading-management::user.trading-bots.wizard.step{$step}", $data);
    }

    /**
     * Process wizard step
     */
    public function processStep(Request $request, int $step): RedirectResponse
    {
        // Validate step number
        if ($step < 1 || $step > 4) {
            return redirect()->route('user.trading-management.trading-bots.wizard')
                ->with('notify', NotificationHelper::error('Invalid wizard step', 'Error'));
        }

        // Get wizard data from session
        $wizardData = session('trading_bot_wizard', []);

        // Validate step data
        $validation = $this->validationService->validateStep($request, $step, $wizardData);
        
        if (!$validation['valid']) {
            return redirect()
                ->route('user.trading-management.trading-bots.wizard.step', ['step' => $step])
                ->withErrors($validation['errors'] ?? [])
                ->withInput();
        }

        // Store step data in session
        $wizardData = array_merge($wizardData, $validation['data']);
        session(['trading_bot_wizard' => $wizardData]);

        // Move to next step or complete
        if ($step < 4) {
            return redirect()
                ->route('user.trading-management.trading-bots.wizard.step', ['step' => $step + 1])
                ->with('notify', NotificationHelper::success('Step ' . $step . ' completed successfully', 'Success'));
        } else {
            // Step 4 is the review step, redirect to complete
            return redirect()->route('user.trading-management.trading-bots.wizard.complete');
        }
    }

    /**
     * Complete wizard and create bot
     */
    public function complete(Request $request): RedirectResponse
    {
        $wizardData = session('trading_bot_wizard', []);

        // Validate all steps are complete
        $validation = $this->validationService->validateAllSteps($wizardData);
        
        if (!$validation['valid']) {
            return redirect()
                ->route('user.trading-management.trading-bots.wizard.step', ['step' => $validation['required_step']])
                ->with('notify', NotificationHelper::error($validation['message'] ?? 'Please complete all wizard steps', 'Error'));
        }

        try {
            // Prepare bot data from wizard
            $botData = $this->prepareBotData($wizardData);

            // Create the bot
            $bot = $this->botService->create($botData);

            // Clear wizard data
            session()->forget('trading_bot_wizard');

            Log::info('Trading bot created via wizard', [
                'bot_id' => $bot->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('user.trading-management.trading-bots.show', $bot->id)
                ->with('notify', NotificationHelper::success('Trading bot created successfully!', 'Success'));
        } catch (\Exception $e) {
            Log::error('Failed to create trading bot via wizard', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('user.trading-management.trading-bots.wizard.step', ['step' => 4])
                ->with('notify', NotificationHelper::error('Failed to create trading bot: ' . $e->getMessage(), 'Error'))
                ->withInput();
        }
    }

    /**
     * Go back to previous step
     */
    public function back(int $step): RedirectResponse
    {
        if ($step <= 1) {
            return redirect()->route('user.trading-management.trading-bots.wizard');
        }

        return redirect()->route('user.trading-management.trading-bots.wizard.step', ['step' => $step - 1]);
    }

    /**
     * Cancel wizard
     */
    public function cancel(): RedirectResponse
    {
        session()->forget('trading_bot_wizard');
        
        return redirect()
            ->route('user.trading-management.trading-bots.index')
            ->with('notify', NotificationHelper::info('Wizard cancelled', 'Info'));
    }

    /**
     * Prepare data for wizard step
     */
    protected function prepareStepData(int $step, array $wizardData): array
    {
        $data = [
            'title' => 'Create Trading Bot - Step ' . $step,
            'step' => $step,
            'totalSteps' => 4,
            'wizardData' => $wizardData,
        ];

        switch ($step) {
            case 1:
                // Step 1: Exchange Connection
                $data['connections'] = $this->botService->getAvailableConnections();
                $data['selectedConnection'] = $wizardData['exchange_connection_id'] ?? null;
                break;

            case 2:
                // Step 2: Trading Preset
                $data['presets'] = $this->botService->getAvailablePresets();
                $data['selectedPreset'] = $wizardData['trading_preset_id'] ?? null;
                break;

            case 3:
                // Step 3: Filter Strategy (optional)
                $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
                $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();
                $data['selectedFilterStrategy'] = $wizardData['filter_strategy_id'] ?? null;
                $data['selectedAiProfile'] = $wizardData['ai_model_profile_id'] ?? null;
                break;

            case 4:
                // Step 4: Review
                $data['connection'] = null;
                $data['preset'] = null;
                $data['filterStrategy'] = null;
                $data['aiProfile'] = null;

                if (!empty($wizardData['exchange_connection_id'])) {
                    $data['connection'] = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::find($wizardData['exchange_connection_id']);
                }
                if (!empty($wizardData['trading_preset_id'])) {
                    $data['preset'] = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::find($wizardData['trading_preset_id']);
                }
                if (!empty($wizardData['filter_strategy_id'])) {
                    $data['filterStrategy'] = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::find($wizardData['filter_strategy_id']);
                }
                if (!empty($wizardData['ai_model_profile_id'])) {
                    $data['aiProfile'] = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::find($wizardData['ai_model_profile_id']);
                }
                break;
        }

        return $data;
    }

    /**
     * Prepare bot data from wizard data
     */
    protected function prepareBotData(array $wizardData): array
    {
        return [
            'name' => $wizardData['name'] ?? 'My Trading Bot',
            'description' => $wizardData['description'] ?? null,
            'exchange_connection_id' => $wizardData['exchange_connection_id'],
            'trading_preset_id' => $wizardData['trading_preset_id'],
            'filter_strategy_id' => $wizardData['filter_strategy_id'] ?? null,
            'ai_model_profile_id' => $wizardData['ai_model_profile_id'] ?? null,
            'trading_mode' => $wizardData['trading_mode'] ?? 'SIGNAL_BASED',
            'is_paper_trading' => $wizardData['is_paper_trading'] ?? true,
        ];
    }
}
