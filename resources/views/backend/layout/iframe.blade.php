<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $general_setting->site_title ?? 'App' }} - Edit</title>
    <!-- Minimal CSS: Bootstrap + Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/font-awesome.min.css') }}" />
    <!-- Dripicons icon font (icons used throughout the app) -->
    <link rel="stylesheet" href="{{ asset('vendor/dripicons/webfont.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style.default.css') }}" id="theme-stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/custom-' . ($general_setting->theme ?? 'default')) }}"
        id="custom-style" />
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap-select.min.css') }}" />
    <!-- jQuery UI theme (autocomplete styling). Use CDN fallback when local theme is not present -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        /* keep iframe content compact */
        body {
            padding: 12px;
            font-family: Inter, Arial, sans-serif;
        }

        /* Use compact page padding but keep Bootstrap modal body spacing so modals look correct */
        .modal-body {
            padding: 1rem;
            /* restore standard Bootstrap modal-body padding */
        }

        /* Ensure iframe modals appear above other iframe content; these z-index values are local to the iframe */
        .modal-backdrop {
            z-index: 1040;
        }

        .modal {
            z-index: 1050;
        }

        /* Ensure jQuery UI autocomplete menu appears above modals and is usable inside iframe */
        .ui-autocomplete {
            z-index: 2000 !important;
            max-height: 240px;
            overflow-y: auto;
            /* prevent horizontal scrollbar */
            overflow-x: hidden;
        }
    </style>
    @stack('iframe-css')
</head>

<body>
    <!-- Minimal JS: jQuery then Bootstrap - load before content so inline scripts in views can run -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('vendor/popper.js/umd/popper.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap-select.min.js') }}"></script>
    <!-- Lightweight shims for optional plugins to prevent errors when plugins are omitted in the iframe -->
    <script>
        (function($) {
            try {
                // Provide a harmless no-op for the custom scrollbar plugin when it's not loaded.
                if (!$.fn.mCustomScrollbar) {
                    $.fn.mCustomScrollbar = function() {
                        return this;
                    };
                }

                // Provide a no-op for gmpc progress circle if missing.
                if (!$.fn.gmpc) {
                    $.fn.gmpc = function() {
                        return this;
                    };
                }

                // Provide a harmless no-op for jquery-validate plugin when it's not loaded.
                if (!$.fn.validate) {
                    $.fn.validate = function() {
                        return this;
                    };
                }
                // Provide a harmless no-op for jquery.cookie when it's not loaded.
                try {
                    if (!$.cookie) {
                        $.cookie = function() {
                            // minimal no-op cookie getter/setter: returns undefined for get, returns value for set
                            if (arguments.length === 1) return undefined;
                            return arguments[1];
                        };
                    }
                } catch (e) {
                    console.warn('cookie shim failed', e);
                }
            } catch (e) {
                console.warn('iframe plugin shim failed', e);
            }
        })(jQuery);
    </script>
    <script src="{{ asset('js/front.js') }}"></script>

    @yield('content')

    <!-- small helper to notify parent when ready -->
    <script>
        try {
            // notify parent window that iframe is ready (if parent expects it)
            if (window.parent && window.parent.postMessage) {
                window.parent.postMessage({
                    type: 'iframe-ready',
                    url: window.location.href
                }, '*');
            }
        } catch (e) {
            console.warn('iframe ready postMessage blocked', e);
        }
    </script>

    @stack('iframe-scripts')
    {{-- Render regular scripts pushed by views (many views use @push('scripts') ) --}}
    @stack('scripts')
</body>

</html>
