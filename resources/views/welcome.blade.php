<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>The Ability Gambit</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        
        @unless(app()->environment('testing'))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endunless
    </head>
    <body class="bg-gray-100 font-sans antialiased">
        <noscript>
            <div class="flex items-center justify-center min-h-screen">
                <div class="text-center p-8 bg-white shadow-xl rounded-2xl">
                    <h1 class="text-2xl font-bold text-red-600">JavaScript Required</h1>
                    <p class="text-gray-600">Please enable JavaScript to play The Ability Gambit.</p>
                </div>
            </div>
        </noscript>
        <div id="app">
            <chess-board></chess-board>
        </div>
    </body>
</html>
