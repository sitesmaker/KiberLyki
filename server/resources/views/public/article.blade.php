<article class="article-page container">
<a href="/articles">← Все новости</a><header><span class="eyebrow">Новости</span><h1>{{ $article['title'] ?? 'Новость' }}</h1><p>{{ $article['description'] ?? '' }}</p><small>{{ \Illuminate\Support\Carbon::parse($article['publishedAt'])->format('d.m.Y') }}@if(!empty($article['author'])) · {{ $article['author'] }}@endif</small></header>
@if(!empty($article['cover']['url']))<img class="article-page__cover" src="{{ $article['cover']['url'] }}" alt="{{ $article['title'] ?? 'Новость' }}">@endif
<div class="rich-text">{{ $article['content'] ?? $article['description'] ?? '' }}</div>
@if(!empty($article['gallery']))<div class="article-gallery">@foreach($article['gallery'] as $image)<img src="{{ $image['url'] }}" alt="{{ $image['alternativeText'] ?? $article['title'] ?? 'Фото новости' }}" loading="lazy">@endforeach</div>@endif
</article>
