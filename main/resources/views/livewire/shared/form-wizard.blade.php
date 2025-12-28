<div>
    <!-- Stepper Header -->
    <div class="mb-8 border-b border-gray-200 pb-5">
        <nav aria-label="Progress">
            <ol role="list" class="space-y-4 md:flex md:space-y-0 md:space-x-8">
                @foreach($steps as $index => $step)
                    @php $stepNum = $index + 1; @endphp
                    <li class="md:flex-1">
                        @if($stepNum < $currentStep)
                            <!-- Completed Step -->
                            <a href="#" wire:click.prevent="goToStep({{ $stepNum }})" class="group pl-4 py-2 flex flex-col border-l-4 border-indigo-600 hover:border-indigo-800 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4">
                                <span class="text-xs text-indigo-600 font-semibold tracking-wide uppercase group-hover:text-indigo-800">Step {{ $stepNum }}</span>
                                <span class="text-sm font-medium">{{ $step['label'] }}</span>
                            </a>
                        @elseif($stepNum === $currentStep)
                            <!-- Current Step -->
                            <a href="#" class="pl-4 py-2 flex flex-col border-l-4 border-indigo-600 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4" aria-current="step">
                                <span class="text-xs text-indigo-600 font-semibold tracking-wide uppercase">Step {{ $stepNum }}</span>
                                <span class="text-sm font-medium">{{ $step['label'] }}</span>
                            </a>
                        @else
                            <!-- Upcoming Step -->
                            <a href="#" class="group pl-4 py-2 flex flex-col border-l-4 border-gray-200 hover:border-gray-300 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4">
                                <span class="text-xs text-gray-500 font-semibold tracking-wide uppercase group-hover:text-gray-700">Step {{ $stepNum }}</span>
                                <span class="text-sm font-medium">{{ $step['label'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>

    <!-- Step Content -->
    <div class="mt-8">
        @foreach($steps as $index => $step)
            @if($currentStep === ($index + 1))
                <div wire:key="step-{{ $index + 1 }}">
                    @if(isset($step['view']))
                        @include($step['view'])
                    @else
                        <!-- Dynamic slot/content for child view override -->
                        @yield('step-' . ($index + 1))
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    <!-- Navigation Footer -->
    <div class="mt-8 pt-5 border-t border-gray-200">
        <div class="flex justify-between">
            <button 
                type="button" 
                wire:click="previousStep" 
                @if($currentStep === 1) disabled @endif
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Previous
            </button>
            
            @if($currentStep < count($steps))
                <button 
                    type="button" 
                    wire:click="nextStep"
                    class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Next
                </button>
            @else
                <button 
                    type="button" 
                    wire:click="submit"
                    class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                >
                    Submit
                </button>
            @endif
        </div>
    </div>
</div>
