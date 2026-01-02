<script>
    'use strict'

    @php
        $alertType = optional(Config::config())->alert ?? 'notify';
    @endphp

    {{-- Laravel Notify (Primary) --}}
    @if ($alertType === 'notify' || $alertType === 'toast')
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
            } else if (typeof toastr !== 'undefined') {
                toastr.error("{{ session('error') }}", {
                    positionClass: "toast-top-right"
                });
            }
        @endif

        @if (session()->has('success'))
            if (typeof notify !== 'undefined') {
                notify()
                    ->success()
                    ->title('Success')
                    ->message("{{ addslashes(session('success')) }}")
                    ->send();
            } else if (typeof toastr !== 'undefined') {
                toastr.success("{{ session('success') }}", {
                    positionClass: "toast-top-right"
                });
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
                } else if (typeof toastr !== 'undefined') {
                    toastr.error("{{ $error }}", {
                        positionClass: "toast-top-right"
                    });
                }
            @endforeach
        @endif
    @elseif ($alertType === 'izi')
        {{-- Legacy iziToast support --}}
        @if (session()->has('error'))
            if (typeof iziToast !== 'undefined') {
                iziToast.error({
                    title: 'Error',
                    message: "{{ session('error') }}",
                    position: 'topRight'
                });
            }
        @endif

        @if (session()->has('success'))
            if (typeof iziToast !== 'undefined') {
                iziToast.success({
                    title: 'Success',
                    message: "{{ session('success') }}",
                    position: 'topRight'
                });
            }
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({
                        title: 'Error',
                        message: "{{ $error }}",
                        position: 'topRight'
                    });
                }
            @endforeach
        @endif
    @else
        {{-- Legacy SweetAlert support --}}
        @if (session()->has('error'))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "{{ session('error') }}"
                });
            }
        @endif

        @if (session()->has('success'))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: "{{ session('success') }}"
                });
            }
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: "{{ $error }}"
                    });
                }
            @endforeach
        @endif
    @endif
</script>
