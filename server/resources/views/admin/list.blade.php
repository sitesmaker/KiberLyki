@extends('admin.layout')
@section('title', $spec['title'])
@section('content')
@include('admin.search')
<div class="actions"><a class="button" href="/admin/content/{{ $section }}/new">{{ $section === 'staffs' ? 'Добавить человека' : (($spec['single'] ?? false) ? 'Редактировать раздел' : 'Добавить') }}</a></div>
<section class="card"><table><thead><tr><th>{{ $section === 'staffs' ? 'Человек' : 'Название' }}</th>@if($section === 'staffs')<th>Роль в клубе</th>@endif<th>Состояние</th><th>Действия</th></tr></thead><tbody>
@forelse($items as $item)
@php($p = $item instanceof \App\Models\Content ? $item->payload : $item->toArray())
<tr><td>{{ $p['title'] ?? $p['name'] ?? trim(($p['firstName'] ?? '').' '.($p['lastName'] ?? '')) }}</td>@if($section === 'staffs')<td>{{ $p['specialization'] ?? '—' }}</td>@endif<td>{{ $section === 'disciplines' ? 'Доступна' : ($item->publishedAt ? 'Опубликовано' : 'Черновик') }}</td><td><a href="/admin/content/{{ $section }}/{{ $item->documentId }}/edit">Редактировать</a> @if($section === 'tournaments') · <a href="/admin/tournaments/{{ $item->documentId }}">Заявки и сетка →</a>@elseif($section === 'staffs' && $item->publishedAt) · <a href="{{ \App\Services\SiteUrl::origin() }}/club/{{ $item->documentId }}" target="_blank" rel="noopener">История на сайте ↗</a>@endif</td></tr>
@empty<tr><td colspan="{{ $section === 'staffs' ? 4 : 3 }}">Ничего не найдено. Измените поиск или добавьте запись.</td></tr>@endforelse</tbody></table>{{ $items->links('admin.pagination') }}</section>
@endsection
