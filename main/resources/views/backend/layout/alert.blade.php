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
    @endif
</script>
