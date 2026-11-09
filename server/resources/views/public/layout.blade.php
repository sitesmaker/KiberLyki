<div class="public-layout{{ $page === 'home' ? ' public-layout--hero' : '' }}">
<div data-island="header">
<header class="site-header"><div class="container site-header__inner"><a href="/" class="logo-main"><span>Cyber</span>luki</a>
<details class="server-mobile-nav"><summary>Меню ☰</summary><nav aria-label="Мобильная навигация">@foreach(['/' => 'Главная', '/articles' => 'Новости', '/teams' => 'Команды', '/tournaments' => 'Турниры', '/cabinet' => 'Кабинет'] as $url => $label)<a href="{{ $url }}">{{ $label }}</a>@endforeach</nav></details>
<nav class="site-header__nav" aria-label="Основная навигация">@foreach(['/' => 'Главная', '/articles' => 'Новости', '/teams' => 'Команды', '/tournaments' => 'Турниры', '/cabinet' => 'Кабинет'] as $url => $label)<a href="{{ $url }}" @if(request()->getPathInfo() === $url) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav></div></header>
</div>
<main>@include('public.'.$page)</main>
<footer class="footer"><div class="container footer__grid">
<div class="footer__item"><a href="/" class="logo-main"><span>Cyber</span>luki</a><p class="footer__description">Киберспортивное сообщество<br>Великие Луки</p></div>
<div class="footer__item"><div class="footer__item-title">На платформе</div><nav aria-label="Навигация в футере">@foreach(['/' => 'Главная', '/articles' => 'Новости', '/teams' => 'Команды', '/tournaments' => 'Турниры', '/cabinet' => 'Кабинет'] as $url => $label)<a href="{{ $url }}">{{ $label }}</a>@endforeach</nav></div>
<div class="footer__item"><div class="footer__item-title">Будь на связи</div><a href="https://vk.ru/cyberluki_vl" target="_blank" rel="noopener noreferrer">Сообщество ВКонтакте ↗</a><a href="https://vk.ru/lunya60" target="_blank" rel="noopener noreferrer">Связаться с создателем ↗</a></div>
<div class="footer__item"><div class="footer__item-title">Твоя игра начинается здесь</div><p>Собери команду.<br>Выбери турнир.<br>Покажи свой уровень.</p><a href="https://vk.ru/cyberluki_vl" class="footer__community" target="_blank" rel="noopener noreferrer" aria-label="Открыть сообщество КиберЛуки ВКонтакте">VK ↗</a></div>
</div><div class="container footer__bottom"><span>КиберЛуки · Великие Луки</span><span>С уважением к игре и друг к другу.</span></div></footer>
</div>
