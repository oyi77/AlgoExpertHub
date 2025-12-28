@if($isOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none">
        <!-- Backdrop -->
        <div 
            class="fixed inset-0 bg-black opacity-50 transition-opacity" 
            @if($closeOnBackdrop) wire:click="close" @endif
        ></div>

        <!-- Modal Content -->
        <div class="relative w-full mx-auto my-6 z-50 {{ $size === 'sm' ? 'max-w-sm' : ($size === 'lg' ? 'max-w-lg' : ($size === 'xl' ? 'max-w-xl' : 'max-w-md')) }}">
            <div class="relative flex flex-col w-full bg-white border-0 rounded-lg shadow-lg outline-none focus:outline-none">
                <!-- Header -->
                <div class="flex items-start justify-between p-5 border-b border-solid border-gray-200 rounded-t">
                    <h3 class="text-3xl font-semibold">
                        {{ $title }}
                    </h3>
                    <button 
                        class="p-1 ml-auto bg-transparent border-0 text-black opacity-5 float-right text-3xl leading-none font-semibold outline-none focus:outline-none" 
                        wire:click="close"
                    >
                        <span class="bg-transparent text-black opacity-5 h-6 w-6 text-2xl block outline-none focus:outline-none">
                            ×
                        </span>
                    </button>
                </div>

                <!-- Body -->
                <div class="relative p-6 flex-auto">
                    @if($content)
                        {!! $content !!}
                    @else
                        {{ $slot }}
                    @endif
                </div>

                <!-- Footer -->
                @if($showFooter)
                    <div class="flex items-center justify-end p-6 border-t border-solid border-gray-200 rounded-b">
                        <button 
                            class="text-red-500 background-transparent font-bold uppercase px-6 py-2 text-sm outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" 
                            type="button" 
                            wire:click="close"
                        >
                            Close
                        </button>
                        <button 
                            class="bg-emerald-500 text-white active:bg-emerald-600 font-bold uppercase text-sm px-6 py-3 rounded shadow hover:shadow-lg outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" 
                            type="button" 
                            wire:click="confirm"
                        >
                            Confirm
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
