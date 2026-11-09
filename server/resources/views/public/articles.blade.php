<section class="container server-page"><div class="page-heading"><div><span class="eyebrow">Жизнь клуба</span><h1>Новости</h1></div></div>
<div class="articles-list">@forelse($articles as $record)@php($article = $record->payload)
<article class="article"><a href="/articles/{{ rawurlencode(($article['slug'] ?? null) ?: $record->documentId) }}">
@if(!empty($article['cover']['url']))<img src="{{ $article['cover']['url'] }}" alt="{{ $article['title'] ?? 'Новость' }}" class="article__image" loading="lazy">@endif
<div class="article__content"><h2 class="article__title">{{ $article['title'] ?? 'Новость' }}</h2><p>{{ $article['description'] ?? '' }}</p></div></a></article>
@empty<p>Новостей пока нет.</p>@endforelse</div>
@include('public.pagination', ['paginator' => $articles])
</section>
