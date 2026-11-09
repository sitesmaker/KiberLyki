@if($paginator->hasPages())
<nav class="admin-pagination" aria-label="Страницы списка">
    <span class="muted">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} из {{ $paginator->total() }}</span>
    <ul class="pagination">
        <li>@if($paginator->onFirstPage())<span aria-disabled="true" aria-label="Предыдущая страница">←</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Предыдущая страница">←</a>@endif</li>
        @foreach($elements as $element)
            @if(is_string($element))<li><span>{{ $element }}</span></li>@else
                @foreach($element as $page => $url)<li>@if($page == $paginator->currentPage())<span aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Страница {{ $page }}">{{ $page }}</a>@endif</li>@endforeach
            @endif
        @endforeach
        <li>@if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Следующая страница">→</a>@else<span aria-disabled="true" aria-label="Следующая страница">→</span>@endif</li>
    </ul>
</nav>
@endif
