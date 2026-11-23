<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base" content="{{ request()->getBaseUrl() }}">
    <title inertia>{{ config('app.name', 'Luxeshop Hospitality POS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/inertia.js'])
    @inertiaHead
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    @inertia
</body>
</html>

