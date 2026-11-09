@php($statusLabels = ['draft' => 'Скоро', 'registration' => 'Регистрация открыта', 'check_in' => 'Подтверждение участия', 'active' => 'Идёт турнир', 'finished' => 'Завершён', 'cancelled' => 'Отменён'])
@foreach($visibleTournaments as $index => $item)
@if($home ?? false)
<a href="/tournaments/{{ $item->documentId }}" class="home-tournament home-tournament--{{ $index }}"><div class="home-tournament__art" @if($item->cover) style="background-image:linear-gradient(0deg,#111c2bea,#111c2b55),url('{{ $item->cover->url }}')" @endif><span class="home-tournament__status"><i></i>{{ $statusLabels[$item->phase] }}</span><span class="home-tournament__game">{{ $item->discipline?->name }}</span><span class="home-tournament__symbol" aria-hidden="true">♜</span></div>
<div class="home-tournament__body"><div class="home-tournament__meta"><span>{{ $item->startsAt?->locale('ru')->translatedFormat('j F') }}</span><span>{{ $item->teamSize }} × {{ $item->teamSize }}</span></div><h3>{{ $item->title }}</h3><div class="home-tournament__bottom"><span>{{ $item->phase === 'registration' ? 'Участвовать' : 'Смотреть турнир' }}</span><span>↗</span></div></div></a>
@else
<a class="tournament-card" href="/tournaments/{{ $item->documentId }}">@if($item->cover)<img src="{{ $item->cover->url }}" alt="{{ $item->title }}" loading="lazy">@else<div class="tournament-card__art" aria-hidden="true">{{ $item->discipline?->name }}</div>@endif<div><span class="tag">{{ $item->discipline?->name }}</span><h2>{{ $item->title }}</h2><p>{{ $statusLabels[$item->phase] }}</p><small>{{ $item->startsAt?->format('d.m.Y H:i') }}</small></div></a>
@endif
@endforeach
