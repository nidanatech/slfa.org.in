<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $route = request()->route();
            $name = $route?->getName();
            $publicPages = [
                'home' => ['SriLankan Friendship Association of South India', 'Discover SLFA, its community, events and membership in South India.'],
                'about' => ['About SLFA', 'Learn about the Sri Lankan Friendship Association, its purpose and leadership.'],
                'happenings' => ['News and Events', 'Explore SLFA community news, upcoming gatherings, and past events in South India.'],
                'news' => ['News and activities', 'Read the latest news and community activities from the Sri Lankan Friendship Association.'],
                'events' => ['Events', 'Explore upcoming and past Sri Lankan Friendship Association events.'],
                'gallery' => ['Gallery', 'Browse photos from Sri Lankan Friendship Association events and activities.'],
                'contact' => ['Contact SLFA', 'Get in touch with the Sri Lankan Friendship Association.'],
                'join' => ['Join SLFA', 'Learn how to apply for membership with the Sri Lankan Friendship Association.'],
                'membership' => ['Membership', 'Find out who can apply to SLFA, how applications are reviewed, and how member profiles work.'],
            ];
            $seo = $publicPages[$name] ?? null;
            if ($name === 'news.show' && ($post = $route?->parameter('news'))) {
                $seo = [$post->title, \Illuminate\Support\Str::limit(strip_tags($post->body), 155)];
            }
            if ($name === 'events.show' && ($event = $route?->parameter('event'))) {
                $seo = [$event->title, \Illuminate\Support\Str::limit(strip_tags($event->description), 155)];
            }
            $indexable = $seo !== null && app()->environment('production');
            $title = $seo[0] ?? 'SLFA';
            $description = $seo[1] ?? 'Sri Lankan Friendship Association community and membership.';
            $canonical = url()->current();
            $image = asset('images/hero-ocean-bg-slfa.jpg');
            $organizationSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'SriLankan Friendship Association of South India',
                'url' => route('home'),
            ];
            $contentSchema = null;
            if ($name === 'news.show' && isset($post)) {
                $contentSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $post->title,
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'url' => $canonical,
                ];
            }
            if ($name === 'events.show' && isset($event)) {
                $contentSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Event',
                    'name' => $event->title,
                    'startDate' => $event->start_at?->toIso8601String(),
                    'endDate' => $event->end_at?->toIso8601String(),
                    'location' => $event->location,
                    'url' => $canonical,
                ];
            }
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ $title }} | SLFA</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Abhaya+Libre:wght@400;500;600;700;800&family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

        <meta name="description" content="{{ $description }}">
        <meta name="author" content="SLFA">
        <meta name="robots" content="{{ $indexable ? 'index, follow' : 'noindex, nofollow' }}">
        @if($indexable)<link rel="canonical" href="{{ $canonical }}">@endif

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:title" content="{{ $title }} | SLFA">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ $image }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ $canonical }}">
        <meta property="twitter:title" content="{{ $title }} | SLFA">
        <meta property="twitter:description" content="{{ $description }}">
        <meta property="twitter:image" content="{{ $image }}">
        @if($name === 'home')
            <script type="application/ld+json">@json($organizationSchema)</script>
        @endif
        @if($contentSchema)<script type="application/ld+json">@json($contentSchema)</script>@endif

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

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
