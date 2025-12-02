<script>
    'use strict'

    @if (session()->has('error'))
        toastr.error("{{ session('error') }}", {
            positionClass: "toast-top-right"
        })
    @endif

    @if (session()->has('success'))
        toastr.success("{{ session('success') }}", {
            positionClass: "toast-top-right"
        })
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            toastr.error("{{ $error }}", {
                positionClass: "toast-top-right"
            })
        @endforeach
    @endif
</script>
