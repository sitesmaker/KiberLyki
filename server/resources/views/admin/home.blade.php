@extends('admin.layout')
@section('title', 'Обзор клуба')
@section('content')
@if(auth()->user()->isOrganizer())
<div class="stats"><a class="card" href="/admin/teams"><strong>{{ $teams }}</strong>Команд</a><a class="card" href="/admin/content/tournaments"><strong>{{ $tournaments }}</strong>Турниров</a><a class="card" href="/admin/content/tournaments"><strong>{{ $pending }}</strong>Заявок ждут решения</a></div>
<section class="card"><h2>Команды, составы, соревнования</h2><p>Создайте команду и добавьте игроков. Подготовьте турнир, рассмотрите заявки и сгенерируйте сетку. Победители переходят в следующий раунд автоматически.</p><div class="actions"><a class="button" href="/admin/teams">Управлять командами</a><a class="button secondary" href="/admin/content/tournaments">Открыть турниры</a></div></section>
@endif
@if(auth()->user()->canManageContent())
<section class="card"><h2>Содержимое сайта и SEO</h2><p>Публикуйте новости, рассказывайте о клубе и его людях, настраивайте страницы для поиска.</p><div class="actions"><a class="button" href="/admin/content/articles">Новости</a><a class="button secondary" href="/admin/seo">SEO страниц</a><a href="/admin/content/staffs">Команда клуба</a></div></section>
@endif
@endsection
