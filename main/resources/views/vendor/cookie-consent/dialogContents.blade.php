<div class="js-cookie-consent cookie-consent fixed bottom-0 inset-x-0 pb-4 px-4 z-[99999]">
    <div class="max-w-7xl mx-auto">
        <div class="cookie-consent-box p-4 rounded-xl bg-gradient-to-r from-yellow-50 to-yellow-100 border border-yellow-200 shadow-2xl">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex-1 items-center hidden md:flex">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <p class="text-gray-800 cookie-consent__message font-medium">
                            {!! trans('cookie-consent::texts.message') !!}
                        </p>
                    </div>
                </div>
                <div class="flex-shrink-0 w-full sm:w-auto">
                    <button class="js-cookie-consent-agree cookie-consent__agree cursor-pointer flex items-center justify-center px-6 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                        {{ trans('cookie-consent::texts.agree') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
