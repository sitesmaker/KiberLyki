@extends('admin.layout')
@section('title', $section === 'seo' ? 'SEO — настройки сайта' : $spec['title'].($item ? ' — редактирование' : ' — новая запись'))
@section('content')
@if($section === 'staffs')<p class="muted">Добавьте столько людей, сколько нужно. Роль в карточке — публичная подпись, она не создаёт аккаунт и не даёт прав в админке. Опубликованные карточки появятся на главной.</p>@endif
@if($section === 'seo')
<section class="card editor"><h2>Как сайт выглядит в поиске</h2><p>Заголовок и описание используются в поиске и при отправке ссылок. Для новостей и турниров можно задать отдельные значения в их настройках. Пустые поля используют значения по умолчанию.</p><p class="muted">Локальные адреса автоматически закрыты от индексации. Перед публикацией укажите основной домен сайта. Индексация и обновление поисковой выдачи зависят от поисковых систем.</p><div class="actions"><a href="{{ rtrim(config('app.frontend_url'), '/') }}/sitemap.xml" target="_blank" rel="noopener">Карта сайта ↗</a><a href="{{ rtrim(config('app.frontend_url'), '/') }}/robots.txt" target="_blank" rel="noopener">Правила индексации ↗</a></div></section>
@endif
@if($section === 'tournaments' && $item)
<div class="actions"><a class="button" href="/admin/tournaments/{{ $item->documentId }}/export">↓ Выгрузить в Excel</a><a href="/admin/tournaments/{{ $item->documentId }}">Заявки и сетка →</a></div>
@endif
<form class="card editor" method="post" enctype="multipart/form-data" action="/admin/content/{{ $section }}{{ $item ? '/'.$item->documentId : '' }}">@csrf
@foreach($spec['fields'] as $name => [$label, $type, $rule])
@php($value = old($name, $values[$name] ?? ''))
@if(in_array($type, ['image','media','gallery']))
@include('admin.media-field')
@else
<label>{{ $label }}{{ $type === 'datetime-local' ? ' (Москва)' : '' }}
@if($type === 'textarea')<textarea name="{{ $name }}" rows="{{ $name === 'content' ? 12 : 4 }}">{{ $value }}</textarea>
@elseif($type === 'staff-role')<input name="{{ $name }}" value="{{ $value }}" list="club-roles" required maxlength="150" placeholder="Выберите или напишите свою роль"><datalist id="club-roles">@foreach(['Основатель','Руководитель','Организатор','Редактор','Администратор клуба','Тренер','Волонтёр'] as $role)<option value="{{ $role }}">@endforeach</datalist>
@elseif($type === 'sort-order')<input name="{{ $name }}" value="{{ $value }}" type="number" min="0" max="9999">
@elseif($type === 'discipline')<select name="{{ $name }}" required><option value="">Выберите дисциплину</option>@foreach($disciplines as $d)<option value="{{ $d->id }}" @selected((string)$value === (string)$d->id)>{{ $d->name }}</option>@endforeach</select>
@elseif($type === 'phase')<select name="{{ $name }}">@foreach(['draft'=>'Подготовка','registration'=>'Регистрация','check_in'=>'Подтверждение участия','active'=>'Идёт турнир','finished'=>'Завершён','cancelled'=>'Отменён'] as $key=>$text)<option value="{{ $key }}" @selected($value === $key)>{{ $text }}</option>@endforeach</select>
@elseif($type === 'indexing')<select name="{{ $name }}"><option value="index" @selected($value === 'index')>Разрешить поисковым системам</option><option value="noindex" @selected($value === 'noindex')>Закрыть от индексации</option></select>
@else<input name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'datetime-local' && $value && !old($name) ? \Illuminate\Support\Carbon::parse($value)->timezone('Europe/Moscow')->format('Y-m-d\TH:i') : $value }}" @if(is_array($rule) ? in_array('required', $rule) : str_starts_with($rule,'required')) required @endif @if($type === 'number') min="1" @endif>
@endif</label>
@endif
@endforeach
@if(!in_array($section, ['disciplines', 'seo']))<label class="check"><input type="checkbox" name="published" value="1" @checked(old('published', $item?->publishedAt !== null))> Показывать на сайте</label>@endif
<div class="actions"><button>Сохранить</button>@if($section !== 'seo')<a href="/admin/content/{{ $section }}">Назад к списку</a>@endif</div></form>
@endsection
