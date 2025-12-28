<div class="space-y-4">
    <h3 class="text-lg font-medium leading-6 text-gray-900">API Credentials</h3>
    <p class="text-sm text-gray-500">Enter your {{ ucfirst($exchange) }} API keys.</p>

    <div>
        <label for="connectionName" class="block text-sm font-medium text-gray-700">Connection Name</label>
        <div class="mt-1">
            <input type="text" wire:model.live.debounce.300ms="connectionName" id="connectionName" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="My {{ ucfirst($exchange) }} Account">
        </div>
        @error('connectionName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="apiKey" class="block text-sm font-medium text-gray-700">API Key</label>
        <div class="mt-1">
            <input type="text" wire:model.live.debounce.300ms="apiKey" id="apiKey" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
        </div>
        @error('apiKey') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="apiSecret" class="block text-sm font-medium text-gray-700">API Secret</label>
        <div class="mt-1">
            <input type="password" wire:model.live.debounce.300ms="apiSecret" id="apiSecret" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
        </div>
        @error('apiSecret') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
