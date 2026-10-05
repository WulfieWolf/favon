<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $resolvedTitle = \App\Support\PageTitle::resolve($title ?? null);
    $browserTitle = filled($resolvedTitle)
        ? $resolvedTitle.' - '.config('app.name', 'Camperwolf')
        : config('app.name', 'Camperwolf');
    $resolvedSocialTitle = $socialTitle ?? $browserTitle;
    $resolvedDescription = $metaDescription ?? __('ui.social.default_description');
    $resolvedSocialImage = $socialImage ?? asset('images/camperwolf-placeholder.png');
    $resolvedCanonicalUrl = $canonicalUrl ?? request()->url();
@endphp

<title>{{ $browserTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
<link rel="canonical" href="{{ $resolvedCanonicalUrl }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="Camperwolf.de">
<meta property="og:title" content="{{ $resolvedSocialTitle }}">
<meta property="og:description" content="{{ $resolvedDescription }}">
<meta property="og:image" content="{{ $resolvedSocialImage }}">
<meta property="og:url" content="{{ $resolvedCanonicalUrl }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $resolvedSocialTitle }}">
<meta name="twitter:description" content="{{ $resolvedDescription }}">
<meta name="twitter:image" content="{{ $resolvedSocialImage }}">

<link rel="icon" href="/favicon.svg" type="image/svg+xml">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
@stack('styles')
