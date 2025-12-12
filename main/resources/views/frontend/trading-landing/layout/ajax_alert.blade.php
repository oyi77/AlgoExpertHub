@if (optional(Config::config())->alert ?? 'sweetalert' === 'toast')
    @if($message)
            toastr.success("{{$message}}", {
                positionClass: "toast-top-right"
            })
    @endif
    @if($message_error)
            toastr.error("{{$message_error}}", {
                positionClass: "toast-top-right"
            })
    @endif
@elseif (optional(Config::config())->alert ?? 'sweetalert' === 'izi')
    @if($message)
            iziToast.success({
                position: 'topRight',
                message: "{{$message}}",
            });
    @endif
    @if($message_error)
            iziToast.error({
                position: 'topRight',
                message: "{{$message_error}}",
            });
    @endif
@else
    @if($message)
            Swal.fire({
                icon: 'success',
                title: "{{$message}}"
            })
    @endif
    @if($message_error)
            Swal.fire({
                icon: 'error',
                title: "{{$message_error}}"
            })
    @endif
@endif

