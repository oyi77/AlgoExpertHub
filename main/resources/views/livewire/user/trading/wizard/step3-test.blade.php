<div class="space-y-4">
    <h3 class="text-lg font-medium leading-6 text-gray-900">Test Connection</h3>
    <p class="text-sm text-gray-500">Verify your connection settings before saving.</p>

    <div class="rounded-md bg-gray-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <h4 class="text-sm font-bold text-gray-800">Connection Summary</h4>
                <ul class="mt-2 text-sm text-gray-700 list-disc list-inside">
                    <li>Exchange: <span class="font-medium">{{ ucfirst($exchange) }}</span></li>
                    <li>Connection Name: <span class="font-medium">{{ $connectionName }}</span></li>
                    <li>API Key: <span class="font-medium">{{ substr($apiKey, 0, 4) }}...{{ substr($apiKey, -4) }}</span></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button 
            type="button" 
            wire:click="testConnection" 
            wire:loading.attr="disabled"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
            <svg wire:loading wire:target="testConnection" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Test Connection
        </button>
    </div>

    @if($testMessage)
        <div class="mt-4 rounded-md p-4 {{ $isTestSuccessful ? 'bg-green-50' : 'bg-red-50' }}">
            <div class="flex">
                <div class="flex-shrink-0">
                    @if($isTestSuccessful)
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium {{ $isTestSuccessful ? 'text-green-800' : 'text-red-800' }}">
                        {{ $testMessage }}
                    </h3>
                </div>
            </div>
        </div>
    @endif
</div>
