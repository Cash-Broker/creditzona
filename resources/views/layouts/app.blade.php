<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <div id="app"></div>

    <script>
        window.laravelRoutes = {
            home: "{{ route('home') }}",
            consumer: "{{ route('service.consumer') }}",
            mortgage: "{{ route('service.mortgage') }}",
            refinance: "{{ route('service.refinance') }}",
            faq: "{{ route('faq') }}",
            contact: "{{ route('contact') }}",
        };
    </script>

</body>

</html>
