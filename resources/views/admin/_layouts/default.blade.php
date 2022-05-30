<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    {!! get_metas() !!}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {!! get_favicon() !!}
    @empty($pageTitle = Template::getTitle())
        <title>{{setting('site_name')}}</title>
    @else
        <title>{{ $pageTitle }}@lang('igniter::admin.site_title_separator'){{setting('site_name')}}</title>
    @endempty
    @styles
</head>
<body class="page {{ $this->bodyClass }}">
@if(AdminAuth::isLogged())
    <x-igniter.admin::header>
        {!! $this->widgets['mainmenu']->render() !!}
    </x-igniter.admin::header>
    <x-igniter.admin::aside :navItems="AdminMenu::getVisibleNavItems()"/>
@endif
<div class="page-wrapper">
    <div class="page-content">
        {!! Template::getBlock('body') !!}
    </div>
</div>
<div id="notification">
    @partial('igniter.admin::flash')
</div>
@if(AdminAuth::isLogged())
    @partial('igniter.admin::status_form')
@endif
@scripts
</body>
</html>
