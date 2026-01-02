if (response.success) {
    if (typeof notify !== 'undefined') {
        notify()
            ->success()
            ->title('Success')
            ->message("{{$message}}")
            ->send();
    }
    return
}

if (typeof notify !== 'undefined') {
    notify()
        ->error()
        ->title('Error')
        ->message("{{$message_error}}")
        ->send();
}
