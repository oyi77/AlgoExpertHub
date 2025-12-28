<div class="space-y-4">
    <h3 class="text-lg font-medium leading-6 text-gray-900">Select Exchange</h3>
    <p class="text-sm text-gray-500">Choose the exchange you want to connect to.</p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <label 
            class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none {{ $exchange === 'binance' ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-300' }}"
        >
            <input type="radio" wire:model.live="exchange" value="binance" class="sr-only">
            <div class="flex flex-1">
                <div class="flex flex-col">
                    <span class="block text-sm font-medium text-gray-900">Binance</span>
                    <span class="mt-1 flex items-center text-sm text-gray-500">Global Exchange</span>
                </div>
            </div>
            <svg class="{{ $exchange === 'binance' ? 'text-indigo-600' : 'invisible' }} h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </label>

        <label 
            class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none {{ $exchange === 'coinbase' ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-300' }}"
        >
            <input type="radio" wire:model.live="exchange" value="coinbase" class="sr-only">
            <div class="flex flex-1">
                <div class="flex flex-col">
                    <span class="block text-sm font-medium text-gray-900">Coinbase</span>
                    <span class="mt-1 flex items-center text-sm text-gray-500">US & Global</span>
                </div>
            </div>
            <svg class="{{ $exchange === 'coinbase' ? 'text-indigo-600' : 'invisible' }} h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </label>
        
        <label 
            class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none {{ $exchange === 'kraken' ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-300' }}"
        >
            <input type="radio" wire:model.live="exchange" value="kraken" class="sr-only">
            <div class="flex flex-1">
                <div class="flex flex-col">
                    <span class="block text-sm font-medium text-gray-900">Kraken</span>
                    <span class="mt-1 flex items-center text-sm text-gray-500">Security First</span>
                </div>
            </div>
            <svg class="{{ $exchange === 'kraken' ? 'text-indigo-600' : 'invisible' }} h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </label>
    </div>
    @error('exchange') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
