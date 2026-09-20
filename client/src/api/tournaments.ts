import { apiFetch } from './client';
import type { StrapiListResponse, StrapiSingleResponse } from '../types/api';
import type { Tournament, TournamentRegistration } from '../types/domain';

export function getTournaments() {
  const query = new URLSearchParams({
    'populate[0]': 'discipline',
    'populate[1]': 'cover',
    'sort[0]': 'startsAt:asc',
  });
  return apiFetch<StrapiListResponse<Tournament>>(`/api/tournaments?${query}`);
}

export function getTournament(documentId: string) {
  return apiFetch<StrapiSingleResponse<Tournament>>(`/api/tournaments/${documentId}/details`);
}

export function registerTeamForTournament(tournamentId: string, playerIds: number[]) {
  return apiFetch('/api/tournament-registrations/register', {
    method: 'POST',
    body: JSON.stringify({ data: { tournamentId, playerIds } }),
  });
}

export function getMyRegistrations() {
  return apiFetch<{ data: TournamentRegistration[] }>('/api/tournament-registrations/mine');
}

export function withdrawRegistration(registrationId: string) {
  return apiFetch(`/api/tournament-registrations/${registrationId}/withdraw`, { method: 'POST' });
}

export function getTournamentRegistrations(tournamentId: string) {
  return apiFetch<{ data: TournamentRegistration[] }>(`/api/tournaments/${tournamentId}/registrations`);
}

export function reviewRegistration(registrationId: string, registrationStatus: 'approved' | 'rejected') {
  return apiFetch(`/api/tournament-registrations/${registrationId}/review`, {
    method: 'PUT',
    body: JSON.stringify({ data: { registrationStatus } }),
  });
}

export function generateBracket(tournamentId: string) {
  return apiFetch(`/api/tournaments/${tournamentId}/generate-bracket`, { method: 'POST' });
}

export function setMatchResult(tournamentId: string, matchId: string, scoreA: number, scoreB: number) {
  return apiFetch(`/api/tournaments/${tournamentId}/matches/${matchId}/result`, {
    method: 'PUT',
    body: JSON.stringify({ data: { scoreA, scoreB } }),
  });
}
