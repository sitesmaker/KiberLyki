@extends('admin.layout')
@section('title', 'SEO страниц')
@section('content')
<form method="get" action="/admin/seo" class="card seo-page-select" data-seo-select>
    <label for="seo-page">Какую страницу настроить?</label>
    <select name="page" id="seo-page">
        @foreach(collect($targets)->groupBy('group', preserveKeys: true) as $group => $pages)
            <optgroup label="{{ $group }}">@foreach($pages as $key => $page)<option value="{{ $key }}" @selected($selected === $key)>{{ $page['label'] }}</option>@endforeach</optgroup>
        @endforeach
    </select>
    <noscript><button>Открыть страницу</button></noscript>
</form>
<div class="seo-editor-layout">
    <form method="post" action="/admin/seo" enctype="multipart/form-data" class="card seo-page-editor" data-seo-editor>
        @csrf<input type="hidden" name="target" value="{{ $selected }}">
        <h2>{{ $target['label'] }}</h2>
        <p class="muted">Пустое поле — заполнить автоматически из содержимого страницы.</p>
        <label>Title — заголовок в поиске
            <input name="title" value="{{ old('title', $values['title'] ?? '') }}" placeholder="{{ $automatic['title'] }}" maxlength="200" data-preview-title>
            <small><span data-title-count></span> · обычно удобно 50–60 символов</small>
        </label>
        <label>Description — описание в поиске
            <textarea name="description" rows="4" maxlength="500" placeholder="{{ $automatic['description'] }}" data-preview-description>{{ old('description', $values['description'] ?? '') }}</textarea>
            <small><span data-description-count></span> · обычно удобно 120–160 символов</small>
        </label>
        <details class="seo-extra" @if($errors->hasAny(['ogTitle','ogDescription','image'])) open @endif>
            <summary>Превью ссылки в соцсетях · Open Graph</summary>
            <p class="muted">По умолчанию используются Title, Description и обложка страницы. Здесь можно задать другое превью.</p>
            <label>Заголовок ссылки<input name="ogTitle" maxlength="200" value="{{ old('ogTitle', $values['ogTitle'] ?? '') }}" placeholder="Как Title" data-preview-og-title></label>
            <label>Описание ссылки<textarea name="ogDescription" rows="3" maxlength="500" placeholder="Как Description" data-preview-og-description>{{ old('ogDescription', $values['ogDescription'] ?? '') }}</textarea></label>
            @include('admin.media-field', ['name' => 'image', 'label' => 'Изображение ссылки', 'type' => 'image', 'section' => 'seo-page', 'item' => null])
        </details>
        <details class="seo-extra">
            <summary>Дополнительно</summary>
            <label>Показывать страницу в поиске<select name="indexing"><option value="index" @selected(old('indexing', $values['indexing'] ?? 'index') === 'index')>Да</option><option value="noindex" @selected(old('indexing', $values['indexing'] ?? 'index') === 'noindex')>Нет</option></select></label>
            <small>Закрытая страница также исключается из карты сайта. Настройка всего сайта имеет приоритет.</small>
            <p class="muted">Основной адрес (canonical) формируется автоматически:<br>{{ $automatic['canonical'] }}</p>
        </details>
        <button type="submit">Сохранить SEO страницы</button>
    </form>
    <aside class="seo-preview" data-seo-preview data-title="{{ $automatic['title'] }}" data-description="{{ $automatic['description'] }}" data-image="{{ $automatic['image'] }}">
        <section class="card"><p class="eyebrow">ПРИМЕР В ПОИСКЕ</p><p class="seo-preview__url">{{ $automatic['canonical'] }}</p><h3 data-search-title></h3><p data-search-description></p></section>
        <section class="card"><p class="eyebrow">ПРЕВЬЮ ССЫЛКИ</p><div class="seo-social-image" data-social-image></div><h3 data-social-title></h3><p data-social-description></p></section>
        <small>Предварительный вид. Поисковики и соцсети могут оформлять ссылку иначе.</small>
    </aside>
</div>
<details class="card seo-global-settings" @if($errors->hasAny(['siteName','siteUrl'])) open @endif>
    <summary>Общие настройки сайта</summary>
    <form method="post" action="/admin/seo/settings">@csrf
        <label>Название сайта<input name="siteName" maxlength="100" required value="{{ old('siteName', $settings['siteName']) }}"></label>
        <p class="muted">Домен определяется автоматически: <strong>{{ $settings['siteUrl'] }}</strong><br>При переносе сайта адреса SEO и карты сайта обновятся вместе с доменом.</p>
        <label>Индексация всего сайта<select name="indexing"><option value="index" @selected($settings['indexing'] === 'index')>Разрешить</option><option value="noindex" @selected($settings['indexing'] === 'noindex')>Закрыть</option></select></label>
        <small>Локальный адрес автоматически закрыт от индексации.</small>
        <div class="actions"><button>Сохранить общие настройки</button><a href="/sitemap.xml" target="_blank" rel="noopener">Карта сайта ↗</a></div>
    </form>
</details>
@endsection
