<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('igniter::main.not_found.page_label')</title>
    <link rel="shortcut icon" href="{{ asset('vendor/igniter/admin/images/favicon.svg') }}" type="image/ico">
    <link href="{{ asset('vendor/igniter/admin/css/static.css') }}" rel="stylesheet">
</head>
<body>
<article>
    <h1>@lang('igniter::main.not_found.page_label')</h1>
    <p class="lead">@lang('igniter::main.not_found.page_message')</p>
</article>
</body>
</html>
