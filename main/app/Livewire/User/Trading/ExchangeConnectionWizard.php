<?php

namespace App\Livewire\User\Trading;

use App\Livewire\Shared\FormWizard;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Validate;

class ExchangeConnectionWizard extends FormWizard
{
    public $exchange = '';
    public $apiKey = '';
    public $apiSecret = '';
    public $connectionName = '';
    public $isTestSuccessful = false;
    public $testMessage = '';

    public function steps(): array
    {
        return [
            [
                'label' => 'Select Exchange',
                'view' => 'livewire.user.trading.wizard.step1-exchange',
                'fields' => ['exchange']
            ],
            [
                'label' => 'API Credentials',
                'view' => 'livewire.user.trading.wizard.step2-credentials',
                'fields' => ['apiKey', 'apiSecret', 'connectionName']
            ],
            [
                'label' => 'Test & Connect',
                'view' => 'livewire.user.trading.wizard.step3-test',
                'fields' => [] // Final step, validation happens on action
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'exchange' => 'required|in:binance,coinbase,kraken', // Example list
            'apiKey' => 'required|string|min:10',
            'apiSecret' => 'required|string|min:10',
            'connectionName' => 'required|string|min:3|max:50',
        ];
    }

    public function testConnection()
    {
        $this->isTestSuccessful = false;
        $this->testMessage = 'Testing connection...';

        // diverse logic per exchange, mocking for pilot
        try {
             // Mock API call
             // Http::post('...')
             
             sleep(1); // Simulate network delay
             
             if ($this->apiKey === 'invalid') {
                 throw new \Exception('Invalid API Key');
             }

             $this->isTestSuccessful = true;
             $this->testMessage = 'Connection successful! Balance: 0.5 BTC';
             
             $this->dispatch('notify', [
                 'type' => 'success',
                 'message' => 'Connection test passed successfully.'
             ]);

        } catch (\Exception $e) {
            $this->isTestSuccessful = false;
            $this->testMessage = 'Connection failed: ' . $e->getMessage();
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Connection test failed.'
            ]);
        }
    }

    public function submit()
    {
        if (!$this->isTestSuccessful) {
             $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please test connection successfully before submitting.'
            ]);
            return;
        }

        // Logic to save connection
        // ExchangeConnection::create([...]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Exchange connected successfully!'
        ]);
        
        $this->dispatch('wizard-completed');
        $this->reset(['exchange', 'apiKey', 'apiSecret', 'connectionName', 'isTestSuccessful', 'testMessage', 'currentStep']);
        $this->currentStep = 1;
    }
}
