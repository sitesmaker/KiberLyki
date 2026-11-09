<section class="container server-page"><div class="page-heading"><div><span class="eyebrow">Соревнования</span><h1>Турниры</h1></div><p>Капитан регистрирует команду целиком и выбирает состав.</p></div>
<div data-island="tournaments"><script type="application/json" data-props>{!! json_encode(['tournaments' => $tournaments, 'home' => false, 'game' => (string) request('game', '')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@include('public.tournament-filter', ['home' => false])
@php($visibleTournaments = $tournaments->filter(fn ($t) => !request('game') || $t->discipline?->name === request('game')))
<div class="cards-grid">@include('public.tournament-cards', ['home' => false])</div>@if($visibleTournaments->isEmpty())<p>По этой игре пока нет опубликованных турниров.</p>@endif
</div></section>
