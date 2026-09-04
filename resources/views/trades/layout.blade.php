<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
@include('seo.sab.partials._head')
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-slate-950 text-slate-100">
@include('trades.partials.header')
<main class="max-w-7xl mx-auto px-3 pt-4 pb-8 sm:px-4">
  @yield('content')
</main>
@yield('scripts')
</body>
</html>
