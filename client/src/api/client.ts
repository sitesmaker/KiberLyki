const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:1337';

export class ApiError extends Error {
  readonly status: number;

  constructor(
    message: string,
    status: number,
  ) {
    super(message);
    this.status = status;
  }
}

export function getApiUrl(path = '') {
  return `${API_URL}${path}`;
}

export function getMediaUrl(path?: string | null) {
  if (!path) return '';
  return path.startsWith('http') ? path : getApiUrl(path);
}

export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers);
  const token = localStorage.getItem('auth_token');

  if (token) headers.set('Authorization', `Bearer ${token}`);
  if (init.body && !(init.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }

  const response = await fetch(getApiUrl(path), { ...init, headers });
  if (!response.ok) {
    const payload = await response.json().catch(() => null) as {
      error?: { message?: string };
    } | null;
    throw new ApiError(payload?.error?.message ?? 'Ошибка запроса', response.status);
  }

  if (response.status === 204) return undefined as T;
  return response.json() as Promise<T>;
}
