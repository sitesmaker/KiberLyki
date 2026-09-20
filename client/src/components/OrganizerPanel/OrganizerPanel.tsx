import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import Button from '../Button';
import type { Tournament } from '../../types/domain';
import { generateBracket, getTournamentRegistrations, reviewRegistration, setMatchResult } from '../../api/tournaments';
import { useToast } from '../../context/useToast';

export default function OrganizerPanel({ tournament }: { tournament: Tournament }) {
  const queryClient = useQueryClient();
  const { showToast } = useToast();
  const [actionError, setActionError] = useState('');
  const registrations = useQuery({ queryKey: ['tournament-registrations', tournament.documentId], queryFn: () => getTournamentRegistrations(tournament.documentId) });

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['tournament-registrations', tournament.documentId] });
    queryClient.invalidateQueries({ queryKey: ['tournament', tournament.documentId] });
  };
  const review = useMutation({
    mutationFn: ({ id, registrationStatus }: { id: string; registrationStatus: 'approved' | 'rejected' }) => reviewRegistration(id, registrationStatus),
    onSuccess: refresh,
  });
  const bracket = useMutation({
    mutationFn: () => generateBracket(tournament.documentId),
    onSuccess: () => { refresh(); showToast('Турнирная сетка создана'); },
  });
  const result = useMutation({
    mutationFn: ({ matchId, scoreA, scoreB }: { matchId: string; scoreA: number; scoreB: number }) => setMatchResult(tournament.documentId, matchId, scoreA, scoreB),
    onSuccess: () => { refresh(); showToast('Результат сохранён'); },
  });

  async function submitResult(event: FormEvent<HTMLFormElement>, matchId: string) {
    event.preventDefault();
    setActionError('');
    const form = new FormData(event.currentTarget);
    const scoreA = Number(form.get('scoreA'));
    const scoreB = Number(form.get('scoreB'));
    try {
      await result.mutateAsync({ matchId, scoreA, scoreB });
    } catch (caught) {
      setActionError(caught instanceof Error ? caught.message : 'Ошибка результата');
    }
  }

  return (
    <section className="panel organizer-panel">
      <div className="organizer-panel__header"><div><span className="tag">Организатор</span><h2>Управление турниром</h2></div><Button type="button" disabled={bracket.isPending || (tournament.matches?.length ?? 0) > 0} onClick={() => bracket.mutate()}>Сформировать сетку</Button></div>
      {(bracket.error || actionError) && <p className="form-error">{bracket.error?.message || actionError}</p>}
      <h3>Заявки</h3>
      <div className="members-list">{registrations.data?.data.map((registration) => <div className="member-row" key={registration.documentId}><div><strong>{registration.team?.name}</strong><small>{new Date(registration.submittedAt).toLocaleString('ru-RU')}</small></div><span>{registration.registrationStatus}</span>{registration.registrationStatus === 'pending' && <div><button className="mini-action" onClick={() => review.mutate({ id: registration.documentId, registrationStatus: 'approved' })}>Принять</button><button className="mini-action mini-action--danger" onClick={() => review.mutate({ id: registration.documentId, registrationStatus: 'rejected' })}>Отклонить</button></div>}</div>)}</div>
      <h3>Результаты</h3>
      <div className="result-list">{tournament.matches?.filter((match) => match.teamA && match.teamB && match.matchStatus !== 'finished').map((match) => <form key={match.documentId} onSubmit={(event) => submitResult(event, match.documentId)}><span>{match.teamA?.name}</span><input name="scoreA" type="number" min="0" required /><span>:</span><input name="scoreB" type="number" min="0" required /><span>{match.teamB?.name}</span><Button type="submit" disabled={result.isPending}>Сохранить</Button></form>)}</div>
    </section>
  );
}
