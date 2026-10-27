<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Dokumen' }}</title>
    <style>
        @yield('styles')
    </style>
</head>
<body>
@yield('content')
</body>
</html>
