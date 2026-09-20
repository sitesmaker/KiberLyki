import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import Button from '../components/Button';
import Bracket from '../components/Bracket/Bracket';
import { getTournament, registerTeamForTournament } from '../api/tournaments';
import { getMyTeam } from '../api/teams';
import { getMediaUrl } from '../api/client';
import { useAuth } from '../context/useAuth';
import { useToast } from '../context/useToast';
import OrganizerPanel from '../components/OrganizerPanel/OrganizerPanel';

export default function TournamentDetails() {
  const { documentId = '' } = useParams();
  const { user } = useAuth();
  const { showToast } = useToast();
  const queryClient = useQueryClient();
  const tournamentQuery = useQuery({ queryKey: ['tournament', documentId], queryFn: () => getTournament(documentId), enabled: Boolean(documentId) });
  const teamQuery = useQuery({ queryKey: ['my-team'], queryFn: getMyTeam, enabled: Boolean(user) });
  const [selected, setSelected] = useState<number[]>([]);
  const tournament = tournamentQuery.data?.data;
  const activePlayers = useMemo(() => teamQuery.data?.data?.memberships?.filter((item) => item.membershipStatus === 'active' && item.position !== 'coach') ?? [], [teamQuery.data]);

  const registerMutation = useMutation({
    mutationFn: () => registerTeamForTournament(documentId, selected),
    onSuccess: () => { showToast('Заявка команды отправлена'); queryClient.invalidateQueries({ queryKey: ['my-registrations'] }); },
  });

  if (tournamentQuery.isLoading) return <div className="page-message">Загрузка турнира...</div>;
  if (!tournament) return <div className="page-message">Турнир не найден</div>;
  const canRegister = tournament.phase === 'registration';
  const approvedTeams = tournament.registrations?.filter((item) => item.registrationStatus === 'approved') ?? [];
  const finalMatch = tournament.matches?.find((match) => match.round === Math.max(0, ...(tournament.matches?.map((item) => item.round) ?? [])));

  return (
    <main>
      <section className="tournament-hero" style={tournament.cover ? { backgroundImage: `linear-gradient(#08111dcc, #08111df2), url(${getMediaUrl(tournament.cover.url)})` } : undefined}>
        <div className="container"><Link to="/tournaments">← Все турниры</Link><span className="tag">{tournament.discipline?.name}</span><h1>{tournament.title}</h1><p>{tournament.description}</p><div className="facts"><span>Старт: {new Date(tournament.startsAt).toLocaleString('ru-RU')}</span><span>Команда: {tournament.teamSize} игроков</span><span>Лимит: {tournament.maxTeams} команд</span></div></div>
      </section>

      <div className="container tournament-layout">
        {user?.role?.type === 'organizer' && <div className="tournament-layout__wide"><OrganizerPanel tournament={tournament} /></div>}
        <section className="panel"><div className="bracket-heading"><div><h2>Турнирная сетка</h2><p>Победитель каждого матча проходит в следующий раунд. Проигравшая команда выбывает.</p></div>{finalMatch?.winner && <div className="champion"><small>Победитель турнира</small><Link to={`/teams/${finalMatch.winner.documentId}`}>🏆 {finalMatch.winner.name}</Link></div>}</div><Bracket matches={tournament.matches ?? []} /></section>
        <aside className="panel registration-panel">
          <h2>Заявка команды</h2>
          {!user && <p><Link to="/login">Войдите как капитан</Link>, чтобы отправить заявку.</p>}
          {user && !teamQuery.data?.data && <p>Сначала <Link to="/cabinet">создайте команду</Link>.</p>}
          {user && teamQuery.data?.data && !teamQuery.data.meta.isCaptain && <p>Подавать заявку может только капитан.</p>}
          {user && teamQuery.data?.meta.isCaptain && (
            <>
              <p><strong>{teamQuery.data.data?.name}</strong></p>
              <p>Выберите ровно {tournament.teamSize} основных игроков:</p>
              <div className="roster-picker">{activePlayers.map((membership) => <label key={membership.documentId}><input type="checkbox" checked={selected.includes(membership.player.id)} onChange={() => setSelected((current) => current.includes(membership.player.id) ? current.filter((id) => id !== membership.player.id) : [...current, membership.player.id])} /> {membership.player.username} <small>{membership.position}</small></label>)}</div>
              {registerMutation.error && <p className="form-error">{registerMutation.error.message}</p>}
              <Button type="button" disabled={!canRegister || selected.length !== tournament.teamSize || registerMutation.isPending} onClick={() => registerMutation.mutate()}>Отправить заявку</Button>
              {!canRegister && <small>Регистрация сейчас закрыта.</small>}
            </>
          )}
        </aside>
        <section className="panel tournament-teams"><h2>Команды турнира</h2>{approvedTeams.length ? <ol>{approvedTeams.map((registration) => <li key={registration.documentId}><Link to={`/teams/${registration.team.documentId}`}>{registration.team.name}</Link>{registration.seed && <small>Посев №{registration.seed}</small>}</li>)}</ol> : <p>Одобренных команд пока нет.</p>}</section>
        {tournament.rules && <section className="panel rules"><h2>Правила</h2><div className="rich-text">{tournament.rules}</div></section>}
      </div>
    </main>
  );
}
