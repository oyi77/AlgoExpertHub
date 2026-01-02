if (response.success) {
    if (typeof notify !== 'undefined') {
        try {
            notify()
                .success()
                .title('Success')
                .message("{{$message}}")
                .send();
        } catch(e) {
            console.error('Error showing notification:', e);
            alert("{{$message}}");
        }
    } else {
        alert("{{$message}}");
    }
    return;
}

if (typeof notify !== 'undefined') {
    try {
        notify()
            .error()
            .title('Error')
            .message("{{$message_error}}")
            .send();
    } catch(e) {
        console.error('Error showing notification:', e);
        alert("{{$message_error}}");
    }
} else {
    alert("{{$message_error}}");
}
