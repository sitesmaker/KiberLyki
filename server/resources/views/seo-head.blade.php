<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta property="og:title" content="{{ $seo['ogTitle'] }}">
<meta property="og:description" content="{{ $seo['ogDescription'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:site_name" content="{{ $seo['siteName'] }}">
<meta property="og:locale" content="ru_RU">
<meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['ogTitle'] }}">
<meta name="twitter:description" content="{{ $seo['ogDescription'] }}">
@if($seo['image'])<meta property="og:image" content="{{ $seo['image'] }}"><meta name="twitter:image" content="{{ $seo['image'] }}">@endif
<script id="site-structured-data" type="application/ld+json">{!! json_encode($seo['structuredData'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
