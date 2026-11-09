<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0b111b">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Управление') · КиберЛуки</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/admin.css">
    <script src="/admin.js" defer></script>
</head>
<body>
<aside class="admin-sidebar">
    <div class="admin-brand-row">
        <a class="brand" href="/admin"><span>CYBER</span>LUKI<small>Управление клубом</small></a>
        <button class="secondary admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-navigation">Меню ☰</button>
    </div>
    <nav class="admin-nav" id="admin-navigation" aria-label="Разделы панели">
        <a href="/admin" @if(request()->is('admin')) aria-current="page" @endif>Обзор</a>
        @if(auth()->user()?->isOrganizer())
        <a href="/admin/teams" @if(request()->is('admin/teams*')) aria-current="page" @endif>Команды и составы</a>
        <a href="/admin/content/tournaments" @if(request()->is('admin/content/tournaments*', 'admin/tournaments*')) aria-current="page" @endif>Турниры и сетки</a>
        <a href="/admin/content/disciplines" @if(request()->is('admin/content/disciplines*')) aria-current="page" @endif>Дисциплины</a>
        @endif
        @if(auth()->user()?->canManageContent())
        <hr>
        @php($contentOpen = request()->is('admin/content/articles*', 'admin/content/first-screen*', 'admin/content/about*', 'admin/content/staffs*', 'admin/content/setting*'))
        <details class="admin-content-menu" @if($contentOpen) open @endif>
            <summary @if($contentOpen) class="is-current" @endif>Содержимое сайта</summary>
            @foreach(['articles','first-screen','about','staffs','setting'] as $key)
                @php($s = config('admin.'.$key))
                <a href="/admin/content/{{ $key }}" @if(request()->is('admin/content/'.$key.'*')) aria-current="page" @endif>{{ $s['title'] }}</a>
            @endforeach
        </details>
        <a href="/admin/seo" @if(request()->is('admin/seo', 'admin/content/seo*')) aria-current="page" @endif>SEO</a>
        @endif
        @if(auth()->user()?->canManagePlayers())
            <hr><a href="/admin/users" @if(request()->is('admin/users*')) aria-current="page" @endif>{{ auth()->user()->canAssignRoles() ? 'Права пользователей' : 'Игроки и доступ' }}</a>
        @endif
        <hr><a href="/admin/account" @if(request()->is('admin/account*')) aria-current="page" @endif>Мой аккаунт</a>
    </nav>
    @auth<form class="sidebar-logout" method="post" action="/admin/logout">@csrf<button class="secondary">Выйти</button></form>@endauth
</aside>
<main>
    <header class="admin-page-header">
        <div><p class="eyebrow">ПАНЕЛЬ КЛУБА</p><h1>@yield('title', 'Управление')</h1></div>
        <a class="admin-site-link" href="{{ \App\Services\SiteUrl::origin() }}" target="_blank" rel="noopener">Открыть сайт ↗</a>
    </header>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    @yield('content')
</main>
</body>
</html>
