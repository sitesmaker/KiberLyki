import { useQuery } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router-dom';
import Section from '../components/Section/Section';
import { getMediaUrl } from '../api/client';
import { getTournaments } from '../api/tournaments';

const statusLabels = { draft: 'Черновик', registration: 'Регистрация', check_in: 'Подтверждение', active: 'Идёт турнир', finished: 'Завершён', cancelled: 'Отменён' };

export default function Tournaments() {
  const query = useQuery({ queryKey: ['tournaments'], queryFn: getTournaments });
  const [params, setParams] = useSearchParams();
  const game = params.get('game') ?? '';
  const games = [...new Set(query.data?.data.map(t => t.discipline?.name).filter(Boolean) ?? [])].sort();
  const visible = query.data?.data.filter(t => !game || t.discipline?.name === game);
  return (
    <Section className="container">
      <div className="page-heading"><div><span className="eyebrow">Соревнования</span><h1>Турниры</h1></div><p>Капитан регистрирует команду целиком и выбирает состав.</p></div>
      <label className="tournament-filter">Дисциплина<select value={game} onChange={event => { const next = new URLSearchParams(params); if (event.target.value) next.set('game', event.target.value); else next.delete('game'); setParams(next); }}><option value="">Все игры</option>{game && !games.includes(game) && <option value={game}>{game}</option>}{games.map(name => <option key={name} value={name}>{name}</option>)}</select></label>
      {query.isLoading && <p>Загрузка турниров...</p>}
      {query.error && <p className="form-error">{query.error.message}</p>}
      <div className="cards-grid">
        {visible?.map((tournament) => (
          <Link className="tournament-card" to={`/tournaments/${tournament.documentId}`} key={tournament.documentId}>
            {tournament.cover ? <img src={getMediaUrl(tournament.cover.url)} alt={tournament.title} /> : <div className="tournament-card__art" aria-hidden="true">{tournament.discipline?.name}</div>}
            <div><span className="tag">{tournament.discipline?.name}</span><h2>{tournament.title}</h2><p>{statusLabels[tournament.phase]}</p><small>{new Date(tournament.startsAt).toLocaleString('ru-RU')}</small></div>
          </Link>
        ))}
      </div>
      {!query.isLoading && !query.isError && visible?.length === 0 && <p>{game ? 'По этой игре пока нет опубликованных турниров.' : 'Опубликованных турниров пока нет.'}</p>}
    </Section>
  );
}
