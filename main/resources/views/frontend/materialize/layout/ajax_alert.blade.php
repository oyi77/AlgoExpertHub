if (response.success) {
    toastr.success("{{$message}}", {
        positionClass: "toast-top-right"
    })
    return
}

toastr.error("{{$message_error}}", {
    positionClass: "toast-top-right"
})