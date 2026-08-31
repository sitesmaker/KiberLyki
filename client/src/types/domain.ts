import type { Media } from './api';

export interface UserRole {
  id: number;
  name: string;
  type: 'public' | 'authenticated' | 'organizer' | string;
}

export interface User {
  id: number;
  documentId?: string;
  username: string;
  email: string;
  confirmed?: boolean;
  blocked?: boolean;
  role?: UserRole;
}

export interface PlayerProfile {
  id: number;
  documentId: string;
  nickname: string;
  firstName?: string | null;
  lastName?: string | null;
  bio?: string | null;
  country?: string | null;
  avatar?: Media | null;
  createdAt?: string;
  updatedAt?: string;
}

export interface Discipline {
  id: number;
  documentId: string;
  name: string;
  slug: string;
  teamSize: number;
  icon?: Media | null;
}

export type MemberPosition = 'main' | 'substitute' | 'coach';

export interface TeamMembership {
  id: number;
  documentId: string;
  status: 'invited' | 'active' | 'left' | 'removed';
  position: MemberPosition;
  joinedAt?: string;
  player: User;
}

export interface Team {
  id: number;
  documentId: string;
  name: string;
  slug: string;
  description?: string | null;
  logo?: Media | null;
  isActive: boolean;
  discipline: Discipline[];
  captain: User;
  memberships: TeamMembership[];
}

export type TournamentStatus = 'draft' | 'registration' | 'check_in' | 'active' | 'finished' | 'cancelled';

export interface Match {
  id: number;
  documentId: string;
  round: number;
  position: number;
  scoreA?: number | null;
  scoreB?: number | null;
  status: 'pending' | 'scheduled' | 'live' | 'finished';
  teamA?: Team | null;
  teamB?: Team | null;
  winner?: Team | null;
}

export interface Tournament {
  id: number;
  documentId: string;
  title: string;
  slug: string;
  description?: string | null;
  rules?: string | null;
  cover?: Media | null;
  format: 'single_elimination';
  teamSize: number;
  maxTeams: number;
  registrationStartsAt: string;
  registrationEndsAt: string;
  startsAt: string;
  status: TournamentStatus;
  discipline: Discipline;
  matches?: Match[];
}

export interface TournamentRegistration {
  id: number;
  documentId: string;
  status: 'pending' | 'approved' | 'rejected' | 'withdrawn';
  submittedAt: string;
  rejectionReason?: string | null;
  seed?: number | null;
  tournament: Tournament;
  team: Team;
}
