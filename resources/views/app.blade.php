<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'SLFA - Sri Lankan Forum Association') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Abhaya+Libre:wght@400;500;600;700;800&family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@300;400;500;600&family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

        <meta name="description" content="Sri Lankan Forum Association (SLFA) - A forum to congregate the people of Sri Lankan origin residing in South India for mutual acquaintance, cultural preservation, and professional relationships.">
        <meta name="keywords" content="SLFA, Sri Lanka, South India, Forum, Association, Sri Lankan Forum Association, Culture, Community, Networking">
        <meta name="author" content="SLFA">
        <meta name="robots" content="index, follow">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="https://slfa.org.in/">
        <meta property="og:title" content="SLFA - Sri Lankan Forum Association">
        <meta property="og:description" content="A forum to congregate the people of Sri Lankan origin residing in South India for mutual acquaintance and cultural preservation.">
        <meta property="og:image" content="{{ asset('storage/legacy/logo.png') }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="https://slfa.org.in/">
        <meta property="twitter:title" content="SLFA - Sri Lankan Forum Association">
        <meta property="twitter:description" content="A forum to congregate the people of Sri Lankan origin residing in South India for mutual acquaintance and cultural preservation.">
        <meta property="twitter:image" content="{{ asset('storage/legacy/logo.png') }}">

        <!-- Google Analytics (GA4) -->
        @if(config('services.google.analytics_id'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ config('services.google.analytics_id') }}');
            </script>
        @endif

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx'])

        <link rel="icon" type="image/png" href="/images/logo/favicon.png">

        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
