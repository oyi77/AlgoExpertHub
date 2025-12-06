<script>
    'use strict'

    @php
        $alertType = optional(Config::config())->alert ?? 'sweetalert';
    @endphp

    @if ($alertType === 'toast')
        @if (session()->has('error'))
            if (typeof toastr !== 'undefined') {
                toastr.error("{{ session('error') }}", {
                    positionClass: "toast-top-right"
                });
            }
        @endif

        @if (session()->has('success'))
            if (typeof toastr !== 'undefined') {
                toastr.success("{{ session('success') }}", {
                    positionClass: "toast-top-right"
                });
            }
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                if (typeof toastr !== 'undefined') {
                    toastr.error("{{ $error }}", {
                        positionClass: "toast-top-right"
                    });
                }
            @endforeach
        @endif
    @elseif ($alertType === 'izi')
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
