<form class="admin-search" method="get" action="{{ url()->current() }}" role="search">
    @foreach($searchHidden ?? [] as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
    <label for="admin-search">{{ $searchLabel ?? 'Поиск по названию' }}</label>
    <div class="admin-search__controls">
        <input id="admin-search" type="search" name="q" value="{{ request('q') }}" maxlength="200" placeholder="{{ $searchPlaceholder ?? 'Введите название…' }}">
        <button type="submit">Найти</button>
        @if(request()->filled('q'))<a href="{{ url()->current().(!empty($searchHidden) ? '?'.http_build_query($searchHidden) : '') }}">Сбросить</a>@endif
    </div>
</form>
