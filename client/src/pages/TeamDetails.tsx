import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { getTeam } from '../api/teams';
import { getMediaUrl } from '../api/client';

const positionLabels = { main: 'Основной состав', substitute: 'Запасной', coach: 'Тренер' };

export default function TeamDetails() {
  const { documentId = '' } = useParams();
  const query = useQuery({ queryKey: ['team', documentId], queryFn: () => getTeam(documentId), enabled: Boolean(documentId) });
  const team = query.data?.data;
  const members = useMemo(() => team?.memberships?.filter((item) => item.membershipStatus === 'active') ?? [], [team]);
  const [selectedId, setSelectedId] = useState('');

  if (query.isLoading) return <div className="page-message">Загрузка команды...</div>;
  if (!team) return <div className="page-message">Команда не найдена</div>;
  const selected = members.find((item) => item.documentId === selectedId) ?? members[0];
  const profile = selected?.player.profile;
  const captainMember = members.find((item) => item.player.id === team.captain?.id);

  return (
    <div className="container team-details">
      <Link to="/teams">← Все команды</Link>
      <header className="panel team-details__header">
        {team.logo ? <img className="team-details__emblem" src={getMediaUrl(team.logo.url)} alt={`Логотип ${team.name}`} /> : <div className="team-card__placeholder team-details__emblem">{team.name.slice(0, 2).toUpperCase()}</div>}
        <div><div className="tags">{team.discipline?.map((item) => <span className="tag" key={item.documentId}>{item.name}</span>)}</div><h1>{team.name}</h1><p>{team.description || 'Описание команды пока не заполнено.'}</p><small>Капитан: {captainMember?.player.profile?.nickname || team.captain?.username}</small></div>
      </header>

      <section className="panel roster-tabs">
        <div className="roster-tabs__list" role="tablist" aria-label="Состав команды">
          <h2>Состав</h2>
          {members.map((membership) => (
            <button type="button" role="tab" aria-selected={membership.documentId === selected?.documentId} className={membership.documentId === selected?.documentId ? 'roster-tab roster-tab--active' : 'roster-tab'} key={membership.documentId} onClick={() => setSelectedId(membership.documentId)}>
              {membership.player.profile?.avatar ? <img className="player-avatar player-avatar--small" src={getMediaUrl(membership.player.profile.avatar.url)} alt="" /> : <span className="player-avatar player-avatar--small">{(membership.player.profile?.nickname || membership.player.username).slice(0, 2).toUpperCase()}</span>}
              <span><strong>{membership.player.profile?.nickname || membership.player.username}</strong><small>{positionLabels[membership.position]}{membership.player.id === team.captain?.id ? ' · Капитан' : ''}</small></span>
            </button>
          ))}
        </div>
        {selected ? <article className="player-card" role="tabpanel">
          {profile?.avatar ? <img className="player-avatar" src={getMediaUrl(profile.avatar.url)} alt={`Аватар ${profile.nickname}`} /> : <div className="player-avatar">{(profile?.nickname || selected.player.username).slice(0, 2).toUpperCase()}</div>}
          <div><span className="eyebrow">{positionLabels[selected.position]}</span><h2>{profile?.nickname || selected.player.username}</h2><p className="player-card__name">{[profile?.firstName, profile?.lastName].filter(Boolean).join(' ') || 'Настоящее имя не указано'}</p><p>{profile?.bio || 'Игрок пока не добавил рассказ о себе.'}</p><dl><div><dt>Страна</dt><dd>{profile?.country || 'Не указана'}</dd></div><div><dt>Аккаунт</dt><dd>{selected.player.username}</dd></div></dl></div>
        </article> : <p>В составе пока нет игроков.</p>}
      </section>
    </div>
  );
}
