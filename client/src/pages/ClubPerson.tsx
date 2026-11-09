import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { apiFetch, getMediaUrl } from '../api/client';
import type { ApiSingleResponse, StaffData } from '../types/api';
import '../components/Staff/Staff.css';

export default function ClubPerson() {
  const { documentId = '' } = useParams();
  const query = useQuery({ queryKey: ['club-person', documentId], queryFn: () => apiFetch<ApiSingleResponse<StaffData>>(`/api/staffs/${encodeURIComponent(documentId)}`) });
  if (query.isLoading) return <div className="page-message">Загружаем историю…</div>;
  if (!query.data) return <div className="page-message"><h1>История не найдена</h1><Link to="/">Вернуться на главную</Link></div>;
  const person = query.data.data;
  return <article className="container club-person">
    <Link to="/#people" reloadDocument className="home-text-link">← Команда клуба</Link>
    <div className="club-person__layout">
      <div className="club-person__portrait">{person.photo ? <img src={getMediaUrl(person.photo.url)} alt={`${person.firstName} ${person.lastName}`} /> : <span>{person.firstName.slice(0, 1)}{person.lastName.slice(0, 1)}</span>}</div>
      <div><p className="home-kicker">{person.specialization || 'Команда клуба'}</p><h1>{person.firstName}<br />{person.lastName}</h1>{person.intro && <p className="club-person__intro">{person.intro}</p>}<h2>История и вклад в клуб</h2><div className="club-person__story">{person.description || 'Рассказ скоро появится.'}</div></div>
    </div>
  </article>;
}
