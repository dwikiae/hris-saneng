<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __($titleKey) }}</title>
</head>
<body>
    <h1>{{ __($titleKey) }}</h1>
    <p>{{ __($bodyKey) }}</p>
</body>
</html>
