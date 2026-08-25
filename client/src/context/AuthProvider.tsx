import { useEffect, useState, type ReactNode } from 'react';
import type { User } from '../types/domain';
import { getCurrentUser, loginRequest, registerRequest } from '../api/auth';
import { AuthContext } from './auth-context';

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(() => Boolean(localStorage.getItem('auth_token')));

  useEffect(() => {
    if (!localStorage.getItem('auth_token')) return;
    getCurrentUser()
      .then(setUser)
      .catch(() => localStorage.removeItem('auth_token'))
      .finally(() => setIsLoading(false));
  }, []);

  async function authenticate(request: Promise<{ jwt: string; user: User }>) {
    const result = await request;
    localStorage.setItem('auth_token', result.jwt);
    const currentUser = await getCurrentUser();
    setUser(currentUser);
  }

  async function login(identifier: string, password: string) {
    await authenticate(loginRequest(identifier, password));
  }

  async function register(username: string, email: string, password: string) {
    await authenticate(registerRequest(username, email, password));
  }

  function logout() {
    localStorage.removeItem('auth_token');
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, isLoading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}
