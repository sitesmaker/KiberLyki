import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import Button from '../components/Button';
import { addPlayer, createTeam, getDisciplines, getMyTeam, transferCaptain } from '../api/teams';
import { useAuth } from '../context/useAuth';
import { useToast } from '../context/useToast';
import { getMyRegistrations, withdrawRegistration } from '../api/tournaments';

export default function Cabinet() {
  const { user, logout } = useAuth();
  const { showToast } = useToast();
  const queryClient = useQueryClient();
  const [captainTarget, setCaptainTarget] = useState('');

  const teamQuery = useQuery({ queryKey: ['my-team'], queryFn: getMyTeam });
  const disciplinesQuery = useQuery({ queryKey: ['disciplines'], queryFn: getDisciplines });
  const registrationsQuery = useQuery({ queryKey: ['my-registrations'], queryFn: getMyRegistrations });

  const refreshTeam = () => queryClient.invalidateQueries({ queryKey: ['my-team'] });
  const createMutation = useMutation({ mutationFn: createTeam, onSuccess: refreshTeam });
  const playerMutation = useMutation({
    mutationFn: ({ teamId, data }: { teamId: string; data: Parameters<typeof addPlayer>[1] }) => addPlayer(teamId, data),
    onSuccess: () => { refreshTeam(); showToast('Игрок создан и добавлен в команду'); },
  });
  const transferMutation = useMutation({
    mutationFn: ({ teamId, playerId }: { teamId: string; playerId: number }) => transferCaptain(teamId, playerId),
    onSuccess: () => { refreshTeam(); showToast('Права капитана переданы'); },
  });
  const withdrawMutation = useMutation({
    mutationFn: withdrawRegistration,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['my-registrations'] }),
  });

  function submitTeam(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    createMutation.mutate({
      name: String(form.get('name')),
      description: String(form.get('description')),
      discipline: String(form.get('discipline')),
    });
  }

  function submitPlayer(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const team = teamQuery.data?.data;
    if (!team) return;
    const form = new FormData(event.currentTarget);
    playerMutation.mutate({
      teamId: team.documentId,
      data: {
        username: String(form.get('username')),
        email: String(form.get('email')),
        password: String(form.get('password')),
        nickname: String(form.get('nickname')),
        position: String(form.get('position')),
      },
    });
    event.currentTarget.reset();
  }

  if (teamQuery.isLoading) return <div className="page-message">Загрузка кабинета...</div>;
  const team = teamQuery.data?.data;
  const isCaptain = teamQuery.data?.meta.isCaptain ?? false;

  return (
    <main className="dashboard container">
      <header className="dashboard__header">
        <div><Link to="/">← На сайт</Link><h1>Личный кабинет</h1><p>{user?.username} · {user?.email}</p></div>
        <Button type="button" onClick={logout}>Выйти</Button>
      </header>

      {!team ? (
        <form className="panel form-grid" onSubmit={submitTeam}>
          <h2>Создать команду</h2>
          <label>Название<input name="name" required /></label>
          <label>Дисциплина<select name="discipline" required defaultValue=""><option value="" disabled>Выберите дисциплину</option>{disciplinesQuery.data?.data.map((item) => <option key={item.documentId} value={item.documentId}>{item.name}</option>)}</select></label>
          <label className="form-grid__wide">Описание<textarea name="description" rows={4} /></label>
          {createMutation.error && <p className="form-error">{createMutation.error.message}</p>}
          <Button type="submit" disabled={createMutation.isPending}>Создать команду</Button>
        </form>
      ) : (
        <>
          <section className="panel team-summary">
            <div><span className="tag">{team.discipline?.name}</span><h2>{team.name}</h2><p>{team.description || 'Описание пока не добавлено'}</p></div>
            <div><strong>{isCaptain ? 'Вы капитан' : 'Вы игрок'}</strong><p>Состав: {team.memberships?.filter((item) => item.status === 'active').length ?? 0}</p></div>
          </section>

          <section className="panel">
            <h2>Состав</h2>
            <div className="members-list">
              {team.memberships?.filter((item) => item.status === 'active').map((membership) => (
                <div className="member-row" key={membership.documentId}>
                  <div><strong>{membership.player.username}</strong><small>{membership.player.email}</small></div>
                  <span>{membership.position === 'main' ? 'Основной' : membership.position === 'substitute' ? 'Запасной' : 'Тренер'}</span>
                  {membership.player.id === team.captain?.id && <span className="tag">Капитан</span>}
                </div>
              ))}
            </div>
          </section>

          {isCaptain && (
            <div className="dashboard-grid">
              <form className="panel form-grid" onSubmit={submitPlayer}>
                <h2>Добавить игрока</h2>
                <label>Username<input name="username" required /></label>
                <label>Никнейм<input name="nickname" required /></label>
                <label>Email<input name="email" type="email" required /></label>
                <label>Временный пароль<input name="password" type="password" minLength={6} required /></label>
                <label>Позиция<select name="position"><option value="main">Основной</option><option value="substitute">Запасной</option><option value="coach">Тренер</option></select></label>
                {playerMutation.error && <p className="form-error">{playerMutation.error.message}</p>}
                <Button type="submit" disabled={playerMutation.isPending}>Создать игрока</Button>
              </form>

              <section className="panel form-grid">
                <h2>Передать капитанство</h2>
                <p>После передачи управлять командой сможет выбранный игрок.</p>
                <select value={captainTarget} onChange={(event) => setCaptainTarget(event.target.value)}>
                  <option value="">Выберите игрока</option>
                  {team.memberships?.filter((item) => item.status === 'active' && item.player.id !== team.captain?.id).map((item) => <option key={item.documentId} value={item.player.id}>{item.player.username}</option>)}
                </select>
                <Button type="button" disabled={!captainTarget || transferMutation.isPending} onClick={() => transferMutation.mutate({ teamId: team.documentId, playerId: Number(captainTarget) })}>Передать права</Button>
              </section>
            </div>
          )}
          <Link className="button-link" to="/tournaments">Перейти к турнирам</Link>
        </>
      )}

      <section className="panel">
        <h2>Мои заявки на турниры</h2>
        <div className="members-list">
          {registrationsQuery.data?.data.map((registration) => (
            <div className="member-row" key={registration.documentId}>
              <div><Link to={`/tournaments/${registration.tournament?.documentId}`}><strong>{registration.tournament?.title}</strong></Link><small>{new Date(registration.submittedAt).toLocaleString('ru-RU')}</small></div>
              <span>{registration.status}</span>
              {['pending', 'approved'].includes(registration.status) && <button className="mini-action mini-action--danger" onClick={() => withdrawMutation.mutate(registration.documentId)}>Отозвать</button>}
            </div>
          ))}
          {!registrationsQuery.isLoading && registrationsQuery.data?.data.length === 0 && <p>Заявок пока нет.</p>}
        </div>
      </section>
    </main>
  );
}
