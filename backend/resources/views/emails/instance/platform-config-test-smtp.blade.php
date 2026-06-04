<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('settings.test_smtp.subject') }}</title>
</head>
<body>
    <h1>{{ __('settings.test_smtp.subject') }}</h1>
    <p>{{ __('settings.test_smtp.body') }}</p>
</body>
</html>
