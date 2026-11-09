import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { FiArrowUpRight, FiArrowRight, FiUsers, FiGitMerge, FiAward } from 'react-icons/fi';
import { FaVk } from 'react-icons/fa';
import FullScreen from '../components/FullScreen/FullScreen';
import Staff from '../components/Staff/Staff';
import { getTournaments } from '../api/tournaments';
import { getTeams } from '../api/teams';
import { apiFetch, getMediaUrl } from '../api/client';
import type { ApiSingleResponse } from '../types/api';
import { club } from '../config/club';
import type { TournamentStatus } from '../types/domain';
import './Home.css';

const statusLabels: Record<TournamentStatus, string> = { draft: 'Скоро', registration: 'Регистрация открыта', check_in: 'Подтверждение участия', active: 'Идёт турнир', finished: 'Завершён', cancelled: 'Отменён' };
const filters = ['Все игры', 'Dota 2', 'Counter-Strike 2', 'Valorant'];
const games = [ ['Dota 2', 'D2', 'MOBA'], ['Counter-Strike 2', 'CS2', 'TACTICAL FPS'], ['Valorant', 'V', 'TACTICAL FPS'], ['League of Legends', 'LoL', 'MOBA'], ['Rocket League', 'RL', 'SPORTS'], ['Apex Legends', 'AL', 'BATTLE ROYALE'] ];

export default function Home() {
  const [game, setGame] = useState('Все игры');
  const [now] = useState(() => Date.now());
  const tournaments = useQuery({ queryKey: ['tournaments'], queryFn: getTournaments });
  const teams = useQuery({ queryKey: ['teams'], queryFn: getTeams });
  const about = useQuery({ queryKey: ['about'], queryFn: async () => (await apiFetch<ApiSingleResponse<{ title?: string; description?: string }>>('/api/about')).data });
  const aboutTitle = (about.data?.title || 'Объединяет игра.\nПобеждает команда.').split('\n');
  const aboutParagraphs = (about.data?.description || 'Место встречи игроков, команд и тех, кто любит киберспорт. Здесь можно собрать свой состав, заявиться на турнир и пройти путь от первого матча до финала.\n\nМы за азарт соревнований, уважение к сопернику и игру, в которой каждый становится сильнее.').split('\n\n').filter(Boolean);
  const upcoming = [...(tournaments.data?.data ?? [])]
    .filter((t) => ['registration', 'check_in', 'active'].includes(t.phase) && (game === 'Все игры' || t.discipline?.name === game))
    .filter((t) => t.phase !== 'registration' || (new Date(t.registrationStartsAt).getTime() <= now && new Date(t.registrationEndsAt).getTime() >= now))
    .sort((a, b) => {
      const priority = { registration: 0, active: 1, check_in: 2, draft: 3, finished: 4, cancelled: 5 };
      return priority[a.phase] - priority[b.phase] || new Date(a.startsAt).getTime() - new Date(b.startsAt).getTime();
    }).slice(0, 3);

  return (
    <div className="club-home">
      <FullScreen />
      <div className="home-signal" aria-label="Киберспортивное сообщество Великих Лук">
        <div className="container home-signal__inner"><span><i /> {club.city}</span><span>КОМАНДЫ / ТУРНИРЫ / СООБЩЕСТВО</span><span className="home-signal__end">Всё начинается с игры <FiArrowUpRight /></span></div>
      </div>

      <section className="home-section container home-intro" id="club">
        <div><p className="home-kicker">01 / ЭТО КИБЕРЛУКИ</p><h2>{aboutTitle.map((line, index) => <span className={index === 0 ? 'home-intro__title-lead' : ''} key={index}>{line}</span>)}</h2></div>
        <div className="home-intro__copy">{aboutParagraphs.map((paragraph, index) => <p key={index}>{paragraph}</p>)}<a className="home-text-link" href={club.communityUrl} target="_blank" rel="noopener noreferrer">Знакомься с сообществом <FiArrowUpRight /></a></div>
      </section>

      <section className="home-games" aria-labelledby="home-games-title">
        <div className="container"><div className="home-section-top"><p className="home-kicker" id="home-games-title">ТВОЯ ДИСЦИПЛИНА. ТВОЙ СТИЛЬ.</p><span className="home-subtle">И ещё больше игр — в турнирах</span></div>
          <div className="home-games__grid">{games.map(([name, mark, type], index) => <Link to={`/tournaments?game=${encodeURIComponent(name)}`} className={`home-game home-game--${index}`} key={name}><span className="home-game__type">{type}</span><span className="home-game__mark" aria-hidden="true">{mark}</span><div><strong>{name}</strong><FiArrowUpRight /></div></Link>)}</div>
        </div>
      </section>

      <section className="home-section container" id="home-tournaments">
        <div className="home-section-heading"><div><p className="home-kicker">02 / СОРЕВНОВАНИЯ</p><h2>Время <span>выйти в игру.</span></h2></div><Link className="home-text-link" to="/tournaments">Все турниры <FiArrowUpRight /></Link></div>
        <div className="home-filters" aria-label="Фильтр турниров по игре">{filters.map((name) => <button type="button" key={name} aria-pressed={game === name} className={game === name ? 'is-active' : ''} onClick={() => setGame(name)}>{name}</button>)}</div>
        {tournaments.isLoading && <p className="home-feedback" role="status">Загружаем ближайшие турниры…</p>}
        {tournaments.isError && <p className="home-feedback">Не удалось загрузить турниры. <button type="button" onClick={() => void tournaments.refetch()}>Попробовать ещё раз</button></p>}
        {!tournaments.isLoading && !tournaments.isError && !upcoming.length && <p className="home-feedback">Новых турниров по этой игре пока нет. Следи за анонсами в нашем <a href={club.communityUrl} target="_blank" rel="noopener noreferrer">сообществе VK</a>.</p>}
        <div className="home-tournaments">{upcoming.map((t, index) => <Link to={`/tournaments/${t.documentId}`} className={`home-tournament home-tournament--${index}`} key={t.documentId}>
          <div className="home-tournament__art" style={t.cover ? { backgroundImage: `linear-gradient(0deg,#111c2bea,#111c2b55),url(${getMediaUrl(t.cover.url)})` } : undefined}><span className="home-tournament__status"><i />{statusLabels[t.phase]}</span><span className="home-tournament__game">{t.discipline?.name}</span><FiAward className="home-tournament__symbol" aria-hidden="true" /></div>
          <div className="home-tournament__body"><div className="home-tournament__meta"><span>{new Date(t.startsAt).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })}</span><span>{t.teamSize} × {t.teamSize}</span></div><h3>{t.title}</h3><div className="home-tournament__bottom"><span>{t.phase === 'registration' ? 'Участвовать' : 'Смотреть турнир'}</span><FiArrowUpRight /></div></div>
        </Link>)}</div>
      </section>

      <section className="home-path"><div className="container home-section"><div className="home-section-heading"><div><p className="home-kicker">03 / ТВОЙ СЛЕДУЮЩИЙ УРОВЕНЬ</p><h2>От первого состава<br />до <span>решающего матча.</span></h2></div><p className="home-section-note">Три шага. Одна цель.<br />Покажи, на что вы способны.</p></div>
        <div className="home-steps">{[
          { icon: FiUsers, title: 'Собери своих', text: 'Создай команду, пригласи игроков и выбери дисциплины. Капитан управляет составом в личном кабинете.', link: '/cabinet', action: 'Создать команду' },
          { icon: FiGitMerge, title: 'Прими вызов', text: 'Найди свой турнир и заяви состав. Следи за решением организатора и соперниками в сетке.', link: '/tournaments', action: 'Выбрать турнир' },
          { icon: FiAward, title: 'Иди до финала', text: 'Каждый матч — новый вызов. Результаты и продвижение победителей доступны на странице турнира.', link: '/tournaments', action: 'Открыть соревнования' },
        ].map((step, index) => <article className="home-step" key={step.title}><div className="home-step__top"><step.icon /><span>0{index + 1}</span></div><h3>{step.title}</h3><p>{step.text}</p><Link className="home-text-link" to={step.link}>{step.action}<FiArrowRight /></Link></article>)}</div>
      </div></section>

      <section className="home-section container"><div className="home-section-heading"><div><p className="home-kicker">04 / КОМАНДНАЯ ИГРА</p><h2>У каждого состава<br /><span>своя история.</span></h2></div><Link className="home-text-link" to="/teams">Все команды <FiArrowUpRight /></Link></div>
        {teams.isLoading && <p className="home-feedback" role="status">Загружаем команды…</p>}
        {teams.isError && <p className="home-feedback">Не удалось загрузить команды. <button type="button" onClick={() => void teams.refetch()}>Попробовать ещё раз</button></p>}
        <div className="home-team-grid">{teams.data?.data.slice(0, 4).map((team, index) => <Link to={`/teams/${team.documentId}`} className="home-team" key={team.documentId}><span className="home-team__number">0{index + 1}</span>{team.logo ? <img src={getMediaUrl(team.logo.url)} alt="" className="home-team__avatar" /> : <span className="home-team__avatar">{team.name.slice(0, 2).toUpperCase()}</span>}<div className="home-team__info"><h3>{team.name}</h3><p>{team.discipline?.map(d => d.name).join(' / ')}</p></div><FiArrowUpRight /></Link>)}</div>
        {!teams.isLoading && !teams.isError && !teams.data?.data.length && <p className="home-feedback">Стань первым капитаном — <Link to="/cabinet">создай свою команду</Link>.</p>}
      </section>

      <Staff />
      <section className="container home-community-wrap"><div className="home-community"><FaVk className="home-community__watermark" aria-hidden="true" /><div className="home-community__copy"><p className="home-kicker">ОНЛАЙН НАЧИНАЕТСЯ С ЛЮДЕЙ</p><h2>Больше, чем<br />просто <em>игра.</em></h2><p>Анонсы, общение и всё, что происходит вокруг КиберЛук. Подключайся к сообществу — увидимся в игре.</p><div className="home-community__actions"><a href={club.communityUrl} target="_blank" rel="noopener noreferrer" className="home-button home-button--dark"><FaVk /> Мы ВКонтакте <FiArrowUpRight /></a><a className="home-text-link" href={club.founderUrl} target="_blank" rel="noopener noreferrer">Связаться с создателем <FiArrowUpRight /></a></div></div></div></section>
      <div className="container home-signoff"><span>ИГРАЙ СВОЮ ИГРУ.</span><span>{club.city} <FiArrowUpRight /></span></div>
    </div>
  );
}
