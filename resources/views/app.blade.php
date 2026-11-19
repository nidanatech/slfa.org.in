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
                'contact' => ['Contact SLFA', 'Get in touch with the Sri Lankan Friendship Association.'],
                'join' => ['Join SLFA', 'Learn how to apply for membership with the Sri Lankan Friendship Association.'],
                'membership' => ['Membership', 'Find out who can apply to SLFA, how applications are reviewed, and how member profiles work.'],
            ];
            $seo = $publicPages[$name] ?? null;
            $post = null;
            $event = null;
            if ($name === 'news.show' && ($post = $route?->parameter('news'))) {
                $seo = [$post->meta_title ?: $post->title, $post->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags($post->body)), 155)];
            }
            if ($name === 'events.show' && ($event = $route?->parameter('event'))) {
                $seo = [$event->meta_title ?: $event->title, $event->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags($event->description ?: 'SLFA event in South India. View the programme, venue and admission details.')), 155)];
            }
            $indexable = $seo !== null && app()->environment('production');
            $title = $seo[0] ?? 'SLFA';
            $description = $seo[1] ?? 'Sri Lankan Friendship Association community and membership.';
            $canonical = url()->current();
            $publicImages = [
                'home' => 'images/south_india_srilanka_satellite.png',
                'about' => 'images/Sri-Lanka-Culture.webp',
                'happenings' => 'images/kandyan-srilankandance.jpg',
                'contact' => 'images/slfa-logo.webp',
                'membership' => 'images/slfa-logo.webp',
            ];
            $imagePath = $post?->og_image_path ?: $post?->cover_image_path ?: $event?->og_image_path ?: $event?->cover_image_path;
            $image = $imagePath
                ? (str_starts_with($imagePath, '/') ? asset(ltrim($imagePath, '/')) : asset('storage/'.$imagePath))
                : asset($publicImages[$name] ?? 'images/slfa-logo.webp');
            $pageTitle = $title.' | SLFA';
            $organizationSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'Sri Lankan Friendship Association of South India',
                'url' => route('home'),
                'logo' => asset('images/slfa-logo.webp'),
                'sameAs' => ['https://slfa.org.in/'],
            ];
            $contentSchema = null;
            if ($name === 'news.show' && $post) {
                $contentSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'NewsArticle',
                    'headline' => $post->title,
                    'description' => $description,
                    'image' => $image,
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'dateModified' => $post->updated_at?->toIso8601String(),
                    'publisher' => $organizationSchema,
                    'url' => $canonical,
                ];
            }
            if ($name === 'events.show' && $event) {
                $contentSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Event',
                    'name' => $event->title,
                    'description' => $description,
                    'image' => $image,
                    'startDate' => $event->start_at?->toIso8601String(),
                    'endDate' => $event->end_at?->toIso8601String(),
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'location' => [
                        '@type' => 'Place',
                        'name' => $event->venue_name ?: $event->location,
                        'address' => $event->venue_address ?: $event->location,
                    ],
                    'organizer' => $organizationSchema,
                    'sponsor' => $event->sponsors->map(fn ($sponsor) => array_filter([
                        '@type' => 'Organization',
                        'name' => $sponsor->name,
                        'url' => $sponsor->website_url,
                    ]))->all(),
                    'url' => $canonical,
                ];
                if ($event->adult_price !== null && $event->start_at?->isFuture()) {
                    $contentSchema['offers'] = ['@type' => 'Offer', 'price' => $event->adult_price, 'priceCurrency' => 'INR', 'url' => $canonical, 'availability' => $event->admissionsOpen() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut', 'validThrough' => $event->admission_closes_at?->toIso8601String()];
                }
            }
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ $pageTitle }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Abhaya+Libre:wght@400;500;600;700;800&family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

        <meta name="description" content="{{ $description }}">
        <meta name="author" content="SLFA">
        <meta name="robots" content="{{ $indexable ? 'index, follow' : 'noindex, nofollow' }}">
        @if($indexable)<link rel="canonical" href="{{ $canonical }}">@endif

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="{{ $post ? 'article' : 'website' }}">
        <meta property="og:site_name" content="SLFA">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ $image }}">
        <meta property="og:image:alt" content="{{ $title }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ $canonical }}">
        <meta property="twitter:title" content="{{ $pageTitle }}">
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

        <link rel="icon" type="image/png" href="/favicon.png">
        <link rel="apple-touch-icon" href="/images/favvicon.png">

        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
