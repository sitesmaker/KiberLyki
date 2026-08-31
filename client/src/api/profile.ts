import { apiFetch } from './client';
import type { PlayerProfile } from '../types/domain';

export function getMyProfile() {
  return apiFetch<{ data: PlayerProfile | null }>('/api/player-profile/me');
}

export async function saveMyProfile(form: FormData) {
  const avatar = form.get('avatar');
  let avatarId: number | undefined;

  if (avatar instanceof File && avatar.size > 0) {
    if (!avatar.type.startsWith('image/')) throw new Error('Аватар должен быть изображением');
    if (avatar.size > 5 * 1024 * 1024) throw new Error('Размер аватара не должен превышать 5 МБ');
    const uploadData = new FormData();
    uploadData.append('files', avatar);
    const uploaded = await apiFetch<Array<{ id: number }>>('/api/upload', { method: 'POST', body: uploadData });
    avatarId = uploaded[0]?.id;
  }

  const data = {
    nickname: String(form.get('nickname') ?? ''),
    firstName: String(form.get('firstName') ?? ''),
    lastName: String(form.get('lastName') ?? ''),
    country: String(form.get('country') ?? ''),
    bio: String(form.get('bio') ?? ''),
    ...(avatarId ? { avatar: avatarId } : {}),
  };

  return apiFetch<{ data: PlayerProfile }>('/api/player-profile/me', {
    method: 'PUT',
    body: JSON.stringify({ data }),
  });
}
