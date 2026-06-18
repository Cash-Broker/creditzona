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
</head>

<body>
    <div id="app" data-page="{{ $page }}">
        {{-- Server-rendered fallback: гарантира, че разяснението за консултантския
             статут присъства в суровия HTML (за crawler и ръчен преглед на ad
             destination), преди Vue SPA да поеме рендирането. Огледало на
             resources/js/data/serviceDisclosure.js — при промяна обновете и двете. --}}
        <noscript>
            <section style="max-width:64rem;margin:1.5rem auto;padding:1.25rem;border:1px solid #b7dbe3;border-radius:16px;background:#e8f4f7;color:#083943;font-family:system-ui,-apple-system,sans-serif;line-height:1.6;">
                <p style="font-weight:700;margin:0 0 .5rem;">CreditZona е финансов консултант, а не кредитор</p>
                <p style="margin:0;">CreditZona предоставя единствено финансово консултиране и съдействие — не предлага собствени кредитни продукти и не е кредитодател. Не отпускаме кредити и не вземаме решения за одобрение; решението се взема изцяло от съответния кредитор. Конкретните условия по кредита (лихва, ГПР, срок, такси) се определят от кредитора, а не от нас.</p>
            </section>
        </noscript>
    </div>

    <script>
        window.appConfig = {{ Illuminate\Support\Js::from($appConfig) }};
    </script>
</body>

</html>
