import { apiFetch } from './client';
import type { Discipline, Team } from '../types/domain';
import type { StrapiListResponse } from '../types/api';

export interface MyTeamResponse {
  data: Team | null;
  meta: { isCaptain: boolean };
}

export function getDisciplines() {
  return apiFetch<StrapiListResponse<Discipline>>('/api/disciplines?sort=name:asc');
}

export function getMyTeam() {
  return apiFetch<MyTeamResponse>('/api/teams/mine');
}

export function getTeams() {
  const query = new URLSearchParams({
    'populate[0]': 'discipline',
    'populate[1]': 'logo',
    'sort[0]': 'name:asc',
    'filters[isActive][$eq]': 'true',
  });
  return apiFetch<StrapiListResponse<Team>>(`/api/teams?${query}`);
}

export function createTeam(data: { name: string; description: string; discipline: string }) {
  return apiFetch<{ data: Team }>('/api/teams/mine', {
    method: 'POST',
    body: JSON.stringify({ data }),
  });
}

export function addPlayer(teamId: string, data: {
  username: string;
  email: string;
  password: string;
  nickname: string;
  position: string;
}) {
  return apiFetch(`/api/teams/${teamId}/players`, {
    method: 'POST',
    body: JSON.stringify({ data }),
  });
}

export function transferCaptain(teamId: string, playerId: number) {
  return apiFetch(`/api/teams/${teamId}/transfer-captain`, {
    method: 'POST',
    body: JSON.stringify({ data: { playerId } }),
  });
}
