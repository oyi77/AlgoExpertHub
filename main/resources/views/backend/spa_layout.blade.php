<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin SPA | AlgoExpertHub</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @viteReactRefresh
    @vite(['resources/js/admin-spa/main.tsx'])
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div id="admin-spa-root"></div>
    
    <script>
        window.site_config = {
            base_url: "{{ url('/') }}",
            api_url: "{{ url('/api') }}",
            user: @json(auth()->guard('admin')->user())
        };
    </script>
</body>
</html>
