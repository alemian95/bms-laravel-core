<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = localStorage.getItem('appearance') || '{{ $appearance ?? "system" }}';

                if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.style.backgroundColor = 'oklch(0.145 0 0)';
                    document.documentElement.style.colorScheme = 'dark';
                } else {
                    document.documentElement.style.backgroundColor = 'oklch(1 0 0)';
                    document.documentElement.style.colorScheme = 'light';
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: var(--background, oklch(1 0 0));
            }

            html.dark {
                background-color: var(--background, oklch(0.145 0 0));
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @viteReactRefresh

        @vite(['resources/css/app.css'])

        <link rel="stylesheet" href="/theme.css">

        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
