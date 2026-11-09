<!doctype html>
<html lang="ru">
<head><meta name="robots" content="noindex, nofollow"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0b111b"><title>Вход · КиберЛуки</title><link rel="icon" type="image/svg+xml" href="/favicon.svg"><link rel="stylesheet" href="/admin.css"></head>
<body class="login"><section class="card">
    <a class="brand" href="{{ \App\Services\SiteUrl::origin() }}"><span>CYBER</span>LUKI</a>
    <p class="eyebrow">ПАНЕЛЬ КЛУБА</p><h1>Управление клубом</h1>
    <p>Войдите с аккаунтом администратора, контент-менеджера или управляющего.</p>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post">@csrf
        <label>Email<input name="email" type="email" required autocomplete="username" value="{{ old('email') }}"></label>
        <label>Пароль<input name="password" type="password" required autocomplete="current-password"></label>
        <button>Войти в панель</button>
    </form>
</section></body></html>
