{{-- Rendered by App\Modules\Landing\Support\SeoMeta into the <head> of public pages. --}}
<meta name="robots" content="{{ $indexable ? 'index, follow' : 'noindex, nofollow' }}">
@if ($description)
<meta name="description" content="{{ $description }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:site_name" content="{{ $site }}">
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
@if ($description)
<meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
@if ($publishedAt)
<meta property="article:published_time" content="{{ $publishedAt }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="geo.region" content="{{ $geo['region'] }}">
<meta name="geo.placename" content="{{ $geo['placename'] }}">
@if (filled($geo['latitude']) && filled($geo['longitude']))
<meta name="geo.position" content="{{ $geo['latitude'] }};{{ $geo['longitude'] }}">
<meta name="ICBM" content="{{ $geo['latitude'] }}, {{ $geo['longitude'] }}">
@endif
@if ($schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
