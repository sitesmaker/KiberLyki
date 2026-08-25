import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import Section from '../components/Section/Section';
import { getMediaUrl } from '../api/client';
import { getTournaments } from '../api/tournaments';

const statusLabels = { draft: 'Черновик', registration: 'Регистрация', check_in: 'Подтверждение', active: 'Идёт турнир', finished: 'Завершён', cancelled: 'Отменён' };

export default function Tournaments() {
  const query = useQuery({ queryKey: ['tournaments'], queryFn: getTournaments });
  return (
    <Section className="container">
      <div className="page-heading"><div><span className="eyebrow">Соревнования</span><h1>Турниры</h1></div><p>Капитан регистрирует команду целиком и выбирает состав.</p></div>
      {query.isLoading && <p>Загрузка турниров...</p>}
      {query.error && <p className="form-error">{query.error.message}</p>}
      <div className="cards-grid">
        {query.data?.data.map((tournament) => (
          <Link className="tournament-card" to={`/tournaments/${tournament.documentId}`} key={tournament.documentId}>
            {tournament.cover && <img src={getMediaUrl(tournament.cover.url)} alt={tournament.title} />}
            <div><span className="tag">{tournament.discipline?.name}</span><h2>{tournament.title}</h2><p>{statusLabels[tournament.status]}</p><small>{new Date(tournament.startsAt).toLocaleString('ru-RU')}</small></div>
          </Link>
        ))}
      </div>
      {!query.isLoading && query.data?.data.length === 0 && <p>Опубликованных турниров пока нет. Создайте первый турнир в Strapi.</p>}
    </Section>
  );
}
