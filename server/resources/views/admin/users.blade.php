@extends('admin.layout')
@section('title', auth()->user()->canAssignRoles() ? 'Права пользователей' : 'Игроки и доступ')
@section('content')
<nav class="user-groups" aria-label="Группы пользователей">
@foreach(['administration'=>'Администрация сайта', 'players'=>'Игроки'] as $key => $label)
@if($key === 'players' || auth()->user()->canAssignRoles())
<a href="/admin/users?{{ http_build_query(array_filter(['group' => $key, 'q' => request('q')])) }}" @if($group === $key) aria-current="page" @endif>{{ $label }} <span>{{ $counts[$key] }}</span></a>
@endif
@endforeach
</nav>
@include('admin.search', ['searchLabel' => 'Поиск по логину, имени или почте', 'searchPlaceholder' => 'Логин, имя или email…', 'searchHidden' => ['group' => $group]])
<section class="card">
@if($group === 'players')<p>Игроки управляют своим профилем и действиями команды в личном кабинете. Доступа к админке и управлению сайтом у них нет.</p>
@else<p><strong>Администратор</strong> — полное управление. <strong>Контент-менеджер</strong> — содержимое сайта и SEO. <strong>Управляющий</strong> — команды, турниры и доступ игроков. Роли администрации назначает только администратор.</p>@endif
<table><thead><tr><th>{{ $group === 'players' ? 'Игрок' : 'Пользователь' }}</th><th>Email</th><th>{{ $group === 'players' ? 'Доступ' : 'Права в панели' }}</th></tr></thead><tbody>
@forelse($users as $u)
<tr><td>{{ $u->profile?->nickname ?? $u->username }}<small>{{ $u->username }}{{ $u->blocked ? ' · Заблокирован' : '' }}</small></td><td>{{ $u->email }}</td><td>
@if($group === 'players')<span class="muted">Только личный кабинет</span><form method="post" action="/admin/users/{{ $u->id }}/access">@csrf<input type="hidden" name="blocked" value="{{ $u->blocked ? '0' : '1' }}"><button class="{{ $u->blocked ? 'secondary' : 'danger' }}">{{ $u->blocked ? 'Разблокировать' : 'Заблокировать' }}</button></form>
@elseif($u->id === auth()->id())Ваш аккаунт (администратор)
@else<form method="post" action="/admin/users/{{ $u->id }}/role">@csrf<div class="actions"><select name="role_type" aria-label="Права {{ $u->username }}">@foreach(\App\Models\User::ADMIN_ROLES + ['authenticated'=>'Убрать из администрации'] as $key=>$label)<option value="{{ $key }}" @selected($u->accessRole() === $key)>{{ $label }}</option>@endforeach</select><button>Сохранить</button></div></form>@endif
@if(auth()->user()->canAssignRoles())
@if($group === 'administration' && $u->id !== auth()->id())
<form method="post" action="/admin/users/{{ $u->id }}/staff-access">@csrf<input type="hidden" name="blocked" value="{{ $u->blocked ? '0' : '1' }}"><button class="{{ $u->blocked ? 'secondary' : 'danger' }}">{{ $u->blocked ? 'Разблокировать сотрудника' : 'Заблокировать сотрудника' }}</button></form>
<details><summary>Удалить сотрудника</summary><form method="post" action="/admin/users/{{ $u->id }}/delete">@csrf
<p>Аккаунт исчезнет из администрации и потеряет доступ. История команд и соревнований сохранится.</p>
<label class="check"><input type="checkbox" name="confirm_delete" value="1" required> Подтверждаю удаление {{ $u->username }}</label><button class="danger">Удалить аккаунт</button></form></details>
@endif
<details @if(old('password_user_id') == $u->id && $errors->has('password')) open @endif><summary>Изменить пароль</summary><form method="post" action="/admin/users/{{ $u->id }}/password">@csrf<input type="hidden" name="password_user_id" value="{{ $u->id }}">
<p class="muted">После смены пароля потребуется повторный вход на всех устройствах.</p>
<label>Новый пароль<input type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required aria-label="Новый пароль для {{ $u->username }}"></label>
<label>Повторите пароль<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required aria-label="Повторите пароль для {{ $u->username }}"></label>
<button>Сохранить пароль</button></form></details>
@endif
</td></tr>
@empty<tr><td colspan="3">Ничего не найдено.</td></tr>@endforelse
</tbody></table>{{ $users->links('admin.pagination') }}</section>
@if($group === 'administration' && auth()->user()->canAssignRoles())
<details class="card" @if(!old('password_user_id') && $errors->hasAny(['username', 'email', 'password', 'password_confirmation'])) open @endif><summary>Создать пользователя администрации</summary>
<form method="post" action="/admin/users/create">@csrf
<label>Логин<input name="username" value="{{ old('username') }}" required maxlength="100" autocomplete="off"></label>
<label>Email для входа<input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="off"></label>
<label>Роль<select name="role_type">@foreach(\App\Models\User::ADMIN_ROLES as $key => $label)<option value="{{ $key }}" @selected(old('role_type', 'content_manager') === $key)>{{ $label }}</option>@endforeach</select></label>
<label>Пароль<input type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required></label>
<label>Повторите пароль<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required></label>
<p class="muted">От 8 до 72 символов. Передайте пользователю почту и пароль для входа.</p>
<button>Создать пользователя</button></form></details>
<details class="card" @if($errors->has('user_id')) open @endif><summary>Добавить аккаунт в администрацию</summary>
@if($availableUsers->isEmpty())<p class="muted">Других аккаунтов администрации пока нет. Создайте сотрудника выше.</p>
@else<form method="post" action="/admin/users/administration">@csrf
<p class="muted">В списке только аккаунты администрации. Выберите сотрудника и нужную роль.</p>
<label>Сотрудник администрации<select name="user_id" required><option value="">Выберите сотрудника</option>@foreach($availableUsers as $candidate)<option value="{{ $candidate->id }}" @selected((string) old('user_id') === (string) $candidate->id)>{{ $candidate->name }} · {{ $candidate->username }} · {{ $candidate->email }} · {{ $candidate->role['name'] }}</option>@endforeach</select></label>
<label>Роль<select name="role_type">@foreach(\App\Models\User::ADMIN_ROLES as $key => $label)<option value="{{ $key }}" @selected(old('role_type', 'content_manager') === $key)>{{ $label }}</option>@endforeach</select></label><button>Назначить доступ</button></form>@endif</details>
@endif
@endsection
