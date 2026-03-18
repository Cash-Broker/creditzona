@php
    $jsonLdFlags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
@endphp

<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">

@if(! empty($seo['keywords']))
    <meta name="keywords" content="{{ implode(', ', $seo['keywords']) }}">
@endif

@foreach($seo['open_graph'] as $property => $content)
    <meta property="{{ $property }}" content="{{ $content }}">
@endforeach

@foreach($seo['twitter'] as $name => $content)
    <meta name="{{ $name }}" content="{{ $content }}">
@endforeach

@foreach($seo['json_ld'] as $schema)
    <script type="application/ld+json" data-seo-schema="true">
        {!! json_encode($schema, $jsonLdFlags) !!}
    </script>
@endforeach
