import { type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import Button from '../components/Button';
import { getMediaUrl } from '../api/client';
import { changePassword } from '../api/auth';
import { getMyProfile, saveMyProfile } from '../api/profile';
import { useAuth } from '../context/useAuth';
import { useToast } from '../context/useToast';

export default function PlayerProfilePage() {
  const { user } = useAuth();
  const { showToast } = useToast();
  const queryClient = useQueryClient();
  const profileQuery = useQuery({ queryKey: ['my-profile'], queryFn: getMyProfile });
  const profile = profileQuery.data?.data;

  const saveMutation = useMutation({
    mutationFn: saveMyProfile,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['my-profile'] });
      showToast('Профиль сохранён');
    },
  });
  const passwordMutation = useMutation({
    mutationFn: ({ currentPassword, password, passwordConfirmation }: { currentPassword: string; password: string; passwordConfirmation: string }) => changePassword(currentPassword, password, passwordConfirmation),
    onSuccess: () => showToast('Пароль изменён'),
  });

  function submitProfile(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    saveMutation.mutate(new FormData(event.currentTarget));
  }

  function submitPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const password = String(form.get('password'));
    const passwordConfirmation = String(form.get('passwordConfirmation'));
    if (password !== passwordConfirmation) {
      showToast('Новые пароли не совпадают', 'error');
      return;
    }
    passwordMutation.mutate({
      currentPassword: String(form.get('currentPassword')),
      password,
      passwordConfirmation,
    });
    event.currentTarget.reset();
  }

  if (profileQuery.isLoading) return <div className="page-message">Загрузка профиля...</div>;

  return (
    <main className="dashboard container profile-page">
      <header className="dashboard__header">
        <div><Link to="/cabinet">← В кабинет</Link><h1>Профиль игрока</h1><p>Эти данные принадлежат вам, а не капитану команды.</p></div>
      </header>

      <div className="profile-layout">
        <form className="panel form-grid" onSubmit={submitProfile} key={profile?.updatedAt ?? 'new-profile'}>
          <h2>Игровая информация</h2>
          <div className="profile-avatar form-grid__wide">
            {profile?.avatar ? <img src={getMediaUrl(profile.avatar.url)} alt={`Аватар ${profile.nickname}`} /> : <div className="team-card__placeholder">{(profile?.nickname || user?.username || '?').slice(0, 2).toUpperCase()}</div>}
            <label>Новый аватар<input name="avatar" type="file" accept="image/*" /></label>
          </div>
          <label>Никнейм<input name="nickname" defaultValue={profile?.nickname ?? user?.username ?? ''} required /></label>
          <label>Страна<input name="country" defaultValue={profile?.country ?? ''} /></label>
          <label>Имя<input name="firstName" defaultValue={profile?.firstName ?? ''} /></label>
          <label>Фамилия<input name="lastName" defaultValue={profile?.lastName ?? ''} /></label>
          <label className="form-grid__wide">О себе<textarea name="bio" rows={6} defaultValue={profile?.bio ?? ''} /></label>
          {saveMutation.error && <p className="form-error">{saveMutation.error.message}</p>}
          <Button type="submit" disabled={saveMutation.isPending}>{saveMutation.isPending ? 'Сохранение...' : 'Сохранить профиль'}</Button>
        </form>

        <form className="panel form-grid password-form" onSubmit={submitPassword}>
          <h2>Сменить пароль</h2>
          <p>Если аккаунт создал капитан, после первого входа обязательно замените временный пароль.</p>
          <label className="form-grid__wide">Текущий пароль<input name="currentPassword" type="password" required /></label>
          <label className="form-grid__wide">Новый пароль<input name="password" type="password" minLength={6} required /></label>
          <label className="form-grid__wide">Повторите пароль<input name="passwordConfirmation" type="password" minLength={6} required /></label>
          {passwordMutation.error && <p className="form-error">{passwordMutation.error.message}</p>}
          <Button type="submit" disabled={passwordMutation.isPending}>Изменить пароль</Button>
        </form>
      </div>
    </main>
  );
}
