import { useState } from 'react';
import { FiArrowUpRight, FiAward } from 'react-icons/fi';
import type { Tournament, TournamentStatus } from '../types/domain';
import { getMediaUrl } from '../api/client';

const labels: Record<TournamentStatus, string> = { draft: 'Скоро', registration: 'Регистрация открыта', check_in: 'Подтверждение участия', active: 'Идёт турнир', finished: 'Завершён', cancelled: 'Отменён' };

export default function PublicTournamentList({ tournaments, home, game: initialGame }: { tournaments: Tournament[]; home: boolean; game: string }) {
  const [game, setGame] = useState(initialGame);
  const games = [...new Set(tournaments.map(t => t.discipline?.name).filter(Boolean))].sort();
  const filtered = tournaments.filter(t => !game || t.discipline?.name === game);
  const visible = home ? filtered.slice(0, 3) : filtered;
  function selectGame(value: string) {
    setGame(value);
    const url = new URL(window.location.href);
    if (value) url.searchParams.set('game', value); else url.searchParams.delete('game');
    window.history.replaceState(window.history.state, '', url);
  }
  return <>
    {home ? <div className="home-filters" aria-label="Фильтр турниров по игре">{['', ...games].map(name => <button type="button" key={name} aria-pressed={game === name} className={game === name ? 'is-active' : ''} onClick={() => selectGame(name)}>{name || 'Все игры'}</button>)}</div> :
      <label className="tournament-filter">Дисциплина<select value={game} onChange={event => selectGame(event.target.value)}><option value="">Все игры</option>{game && !games.includes(game) && <option value={game}>{game}</option>}{games.map(name => <option key={name} value={name}>{name}</option>)}</select></label>}
    <div className={home ? 'home-tournaments' : 'cards-grid'}>{visible.map((t, index) => home ?
      <a href={`/tournaments/${t.documentId}`} className={`home-tournament home-tournament--${index}`} key={t.documentId}>
        <div className="home-tournament__art" style={t.cover ? { backgroundImage: `linear-gradient(0deg,#111c2bea,#111c2b55),url(${getMediaUrl(t.cover.url)})` } : undefined}><span className="home-tournament__status"><i />{labels[t.phase]}</span><span className="home-tournament__game">{t.discipline?.name}</span><FiAward className="home-tournament__symbol" aria-hidden="true" /></div>
        <div className="home-tournament__body"><div className="home-tournament__meta"><span>{new Date(t.startsAt).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })}</span><span>{t.teamSize} × {t.teamSize}</span></div><h3>{t.title}</h3><div className="home-tournament__bottom"><span>{t.phase === 'registration' ? 'Участвовать' : 'Смотреть турнир'}</span><FiArrowUpRight /></div></div>
      </a> : <a className="tournament-card" href={`/tournaments/${t.documentId}`} key={t.documentId}>
        {t.cover ? <img src={getMediaUrl(t.cover.url)} alt={t.title} loading="lazy" /> : <div className="tournament-card__art" aria-hidden="true">{t.discipline?.name}</div>}
        <div><span className="tag">{t.discipline?.name}</span><h2>{t.title}</h2><p>{labels[t.phase]}</p><small>{new Date(t.startsAt).toLocaleString('ru-RU')}</small></div>
      </a>)}</div>
    {visible.length === 0 && <p className="home-feedback">По этой игре пока нет опубликованных турниров.</p>}
  </>;
}
