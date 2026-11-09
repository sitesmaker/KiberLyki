@extends('admin.layout')
@section('title', 'Мой аккаунт')
@section('content')
<section class="card">
<h2>{{ auth()->user()->name }}</h2><p>{{ auth()->user()->email }} · {{ auth()->user()->role['name'] }}</p>
<h2>Сменить пароль</h2>
<form method="post" action="/admin/account/password">@csrf
<label>Текущий пароль<input type="password" name="current_password" required autocomplete="current-password"></label>
<label>Новый пароль<input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></label>
<label>Повторите новый пароль<input type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password"></label>
<p class="muted">От 8 до 72 символов. После сохранения войдите заново с новым паролем.</p>
<button>Сменить пароль</button>
</form></section>
@endsection
