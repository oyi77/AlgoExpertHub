<script>
    'use strict'

    @php
        $alertType = optional(Config::config())->alert ?? 'notify';
    @endphp

    {{-- Laravel Notify (Primary - Always enabled after migration) --}}
    @if (session()->has('notify'))
        @php
            $notify = session('notify');
        @endphp
        @if (is_array($notify))
            if (typeof notify !== 'undefined') {
                notify()
                    @if(isset($notify['type']))
                        ->{{ strtolower($notify['type']) }}()
                    @else
                        ->success()
                    @endif
                    @if(isset($notify['title']))
                        ->title('{{ addslashes($notify['title']) }}')
                    @endif
                    @if(isset($notify['message']))
                        ->message('{{ addslashes($notify['message']) }}')
                    @endif
                    @if(isset($notify['duration']))
                        ->duration({{ $notify['duration'] }})
                    @endif
                    ->send();
            }
        @endif
    @endif

    {{-- Legacy session flash messages (backward compatibility) --}}
    @if (session()->has('error'))
        if (typeof notify !== 'undefined') {
            notify()
                ->error()
                ->title('Error')
                ->message("{{ addslashes(session('error')) }}")
                ->send();
        }
    @endif

    @if (session()->has('success'))
        if (typeof notify !== 'undefined') {
            notify()
                ->success()
                ->title('Success')
                ->message("{{ addslashes(session('success')) }}")
                ->send();
        }
    @endif

    @if (session()->has('warning'))
        if (typeof notify !== 'undefined') {
            notify()
                ->warning()
                ->title('Warning')
                ->message("{{ addslashes(session('warning')) }}")
                ->send();
        }
    @endif

    @if (session()->has('info'))
        if (typeof notify !== 'undefined') {
            notify()
                ->info()
                ->title('Info')
                ->message("{{ addslashes(session('info')) }}")
                ->send();
        }
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            if (typeof notify !== 'undefined') {
                notify()
                    ->error()
                    ->title('Validation Error')
                    ->message("{{ addslashes($error) }}")
                    ->send();
            }
        @endforeach
    @endif
</script>
