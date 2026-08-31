import { apiFetch } from './client';
import type { User } from '../types/domain';

interface AuthResponse {
  jwt: string;
  user: User;
}

export function loginRequest(identifier: string, password: string) {
  return apiFetch<AuthResponse>('/api/auth/local', {
    method: 'POST',
    body: JSON.stringify({ identifier, password }),
  });
}

export function registerRequest(username: string, email: string, password: string) {
  return apiFetch<AuthResponse>('/api/auth/local/register', {
    method: 'POST',
    body: JSON.stringify({ username, email, password }),
  });
}

export function getCurrentUser() {
  return apiFetch<User>('/api/users/me?populate=role');
}

export function changePassword(currentPassword: string, password: string, passwordConfirmation: string) {
  return apiFetch('/api/auth/change-password', {
    method: 'POST',
    body: JSON.stringify({ currentPassword, password, passwordConfirmation }),
  });
}
