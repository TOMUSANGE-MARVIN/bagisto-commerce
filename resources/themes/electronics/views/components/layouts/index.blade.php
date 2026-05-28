@props([
    'hasHeader'  => true,
    'hasFeature' => true,
    'hasFooter'  => true,
])

<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ core()->getCurrentLocale()->direction }}"
>
    <head>

        {!! view_render_event('bagisto.shop.layout.head.before') !!}

        <title>{{ $title ?? '' }}</title>

        <meta charset="UTF-8">

        <meta
            http-equiv="X-UA-Compatible"
            content="IE=edge"
        >
        <meta
            http-equiv="content-language"
            content="{{ app()->getLocale() }}"
        >

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="base-url"
            content="{{ url()->to('/') }}"
        >
        <meta
            name="currency"
            content="{{ core()->getCurrentCurrency()->toJson() }}"
        >
        <meta 
            name="generator" 
            content="Bagisto"
        >

        @stack('meta')

        <link
            rel="icon"
            sizes="16x16"
            href="{{ core()->getCurrentChannel()->favicon_url ?? bagisto_asset('images/favicon.ico') }}"
        />

        @bagistoVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'])

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
            crossorigin
        />

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        />

        <link
            rel="preload" as="style"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
        />

        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
        />

        @stack('styles')

        <style>
            {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
        </style>

        <!-- Electronics Theme Global Styles -->
        <style>
            :root {
                --elec-dark:    #0f172a;
                --elec-navy:    #1e3a5f;
                --elec-accent:  #f59e0b;
                --elec-accent2: #3b82f6;
            }

            /* ── Announcement bar ─────────────────────── */
            .elec-announce {
                background: var(--elec-dark);
                color: #e2e8f0;
                text-align: center;
                font-size: 13px;
                padding: 7px 16px;
                letter-spacing: .3px;
            }
            .elec-announce a { color: var(--elec-accent); text-decoration: none; }
            .elec-announce strong { color: var(--elec-accent); }

            /* ── Main header wrapper ──────────────────── */
            header.shadow-gray {
                background: var(--elec-navy) !important;
                box-shadow: 0 2px 8px rgba(0,0,0,.4) !important;
                border-bottom: 2px solid var(--elec-accent2) !important;
            }

            /* Nav links & icon colours inside header */
            header.shadow-gray a,
            header.shadow-gray span,
            header.shadow-gray button,
            header.shadow-gray label {
                color: #e2e8f0 !important;
            }
            header.shadow-gray a:hover { color: var(--elec-accent) !important; }

            /* Category nav items */
            header.shadow-gray nav a,
            header.shadow-gray [class*="nav"] a {
                color: #cbd5e1 !important;
            }

            /* Search bar styling */
            header.shadow-gray input[type="text"],
            header.shadow-gray input[type="search"] {
                background: rgba(255,255,255,.1) !important;
                border: 1px solid rgba(255,255,255,.2) !important;
                color: #fff !important;
            }
            header.shadow-gray input::placeholder { color: #94a3b8 !important; }

            /* SVG icons in header */
            header.shadow-gray svg path,
            header.shadow-gray svg circle,
            header.shadow-gray svg line,
            header.shadow-gray svg polyline,
            header.shadow-gray svg rect {
                stroke: #e2e8f0 !important;
            }

            /* Badge on cart/wishlist */
            header.shadow-gray .badge,
            header.shadow-gray [class*="badge"] {
                background: var(--elec-accent) !important;
                color: #0f172a !important;
            }

            /* ── Body background ──────────────────────── */
            body { background: #f8fafc; }

            /* ── Hero carousel overlay text ───────────── */
            .carousel-text-overlay h1,
            .carousel-text-overlay p {
                text-shadow: 0 2px 8px rgba(0,0,0,.6);
            }
        </style>

        @if(core()->getConfigData('general.content.speculation_rules.enabled'))
            <script type="speculationrules">
                @json(core()->getSpeculationRules(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            </script>
        @endif

        {!! view_render_event('bagisto.shop.layout.head.after') !!}

    </head>

    <body>
        {!! view_render_event('bagisto.shop.layout.body.before') !!}

        <a
            href="#main"
            class="skip-to-main-content-link"
        >
            Skip to main content
        </a>

        <!-- Built With Bagisto -->
        <div id="app">
            <!-- Flash Message Blade Component -->
            <x-shop::flash-group />

            <!-- Confirm Modal Blade Component -->
            <x-shop::modal.confirm />

            <!-- Electronics Announcement Bar -->
            @if ($hasHeader)
                <div class="elec-announce">
                    🔥 <strong>MEGA DEALS:</strong> Up to 40% OFF on Phones, Laptops &amp; Accessories &nbsp;|&nbsp;
                    🚚 Free Shipping on orders over $50 &nbsp;|&nbsp;
                    💳 <a href="#">EMI Available — 0% Interest</a>
                </div>
            @endif

            <!-- Page Header Blade Component -->
            @if ($hasHeader)
                <x-shop::layouts.header />
            @endif

            @if(
                core()->getConfigData('general.gdpr.settings.enabled')
                && core()->getConfigData('general.gdpr.cookie.enabled')
            )
                <x-shop::layouts.cookie />
            @endif

            {!! view_render_event('bagisto.shop.layout.content.before') !!}

            <!-- Page Content Blade Component -->
            <main id="main" class="bg-white">
                {{ $slot }}
            </main>

            {!! view_render_event('bagisto.shop.layout.content.after') !!}


            <!-- Page Services Blade Component -->
            @if ($hasFeature)
                <x-shop::layouts.services />
            @endif

            <!-- Page Footer Blade Component -->
            @if ($hasFooter)
                <x-shop::layouts.footer />
            @endif
        </div>

        {!! view_render_event('bagisto.shop.layout.body.after') !!}

        @stack('scripts')

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.before') !!}
        <script>
            /**
             * Mount the application as soon as the DOM is ready instead of waiting
             * for the `load` event. All `Vue` components are registered through
             * deferred `type="module"` scripts, which always finish executing
             * before `DOMContentLoaded` fires, so every component is available
             * by the time `app.mount()` runs. Mounting on `DOMContentLoaded`
             * avoids blocking the storefront behind every image/font download.
             */
            function mountApp() {
                app.mount("#app");
            }

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", mountApp);
            } else {
                mountApp();
            }
        </script>

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.after') !!}

        <script type="text/javascript">
            {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
        </script>
    </body>
</html>
