<button 
    type="button" 
    class="{{ $isActive ? 'bg-indigo-600' : 'bg-gray-200' }} relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" 
    role="switch" 
    aria-checked="{{ $isActive ? 'true' : 'false' }}"
    wire:click="toggle"
    wire:loading.attr="disabled"
    wire:target="toggle"
>
    <span class="sr-only">Use setting</span>
    <span 
        aria-hidden="true" 
        class="{{ $isActive ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
    >
        <!-- Loading spinner -->
        <svg 
            wire:loading 
            wire:target="toggle"
            class="animate-spin h-5 w-5 text-indigo-600" 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </span>
</button>
