<!DOCTYPE html>
<html lang="bg">

<head>
    @php
        $versionedPublicAsset = static function (string $path): string {
            $normalizedPath = ltrim($path, '/');
            $assetUrl = asset($normalizedPath);
            $absolutePath = public_path($normalizedPath);

            if (! is_file($absolutePath)) {
                return $assetUrl;
            }

            return $assetUrl.'?v='.filemtime($absolutePath);
        };
    @endphp

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ config('seo.site.theme_color', '#f9fafb') }}">

    @include('partials.seo', ['seo' => $seo])

    <link rel="icon" href="{{ $versionedPublicAsset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ $versionedPublicAsset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ $versionedPublicAsset('favicon-96x96.png') }}">
    <link rel="apple-touch-icon" href="{{ $versionedPublicAsset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ $versionedPublicAsset('site.webmanifest') }}">

    @foreach($seo['preload_images'] as $image)
        <link rel="preload" as="image" href="{{ asset(ltrim($image, '/')) }}">
    @endforeach

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&amp;display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Ads -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17854641886"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'AW-17854641886');
    </script>
</head>

<body>
    <div id="app" data-page="{{ $page }}"></div>

    <script>
        window.appConfig = {{ Illuminate\Support\Js::from($appConfig) }};
    </script>
</body>

</html>
