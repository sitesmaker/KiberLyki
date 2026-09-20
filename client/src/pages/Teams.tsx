import { useQuery } from '@tanstack/react-query';
import Section from '../components/Section/Section';
import { getTeams } from '../api/teams';
import { getMediaUrl } from '../api/client';
import { Link } from 'react-router-dom';

export default function Teams() {
  const query = useQuery({ queryKey: ['teams'], queryFn: getTeams });
  return (
    <Section className="container">
      <div className="page-heading"><div><span className="eyebrow">Участники</span><h1>Команды</h1></div><p>Активные команды разных киберспортивных дисциплин.</p></div>
      {query.isLoading && <p>Загрузка команд...</p>}
      {query.error && <p className="form-error">{query.error.message}</p>}
      <div className="team-cards">{query.data?.data.map((team) => <Link className="panel team-card" to={`/teams/${team.documentId}`} key={team.documentId}>{team.logo ? <img src={getMediaUrl(team.logo.url)} alt={team.name} /> : <div className="team-card__placeholder">{team.name.slice(0, 2).toUpperCase()}</div>}<div><div className="tags">{team.discipline?.map((item) => <span className="tag" key={item.documentId}>{item.name}</span>)}</div><h2>{team.name}</h2><p>{team.description}</p><span className="team-card__more">Посмотреть состав →</span></div></Link>)}</div>
      {!query.isLoading && query.data?.data.length === 0 && <p>Команд пока нет.</p>}
    </Section>
  );
}
