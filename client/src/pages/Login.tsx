import { useState, type FormEvent } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import Button from '../components/Button';
import { useAuth } from '../context/useAuth';

export default function Login() {
  const { user, login, register } = useAuth();
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  if (user) return <Navigate to="/cabinet" replace />;

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');
    setIsSubmitting(true);
    try {
      if (mode === 'login') await login(email, password);
      else await register(username, email, password);
      const target = (location.state as { from?: { pathname?: string } } | null)?.from?.pathname ?? '/cabinet';
      navigate(target, { replace: true });
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : 'Не удалось выполнить вход');
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <main className="auth-page">
      <Link to="/" className="auth-page__logo">Cyberluki</Link>
      <form className="panel auth-form" onSubmit={handleSubmit}>
        <h1>{mode === 'login' ? 'Вход' : 'Регистрация капитана'}</h1>
        {mode === 'register' && (
          <label>Username<input value={username} onChange={(event) => setUsername(event.target.value)} required /></label>
        )}
        <label>Email<input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></label>
        <label>Пароль<input type="password" minLength={6} value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
        {error && <p className="form-error">{error}</p>}
        <Button type="submit" disabled={isSubmitting}>{isSubmitting ? 'Подождите...' : mode === 'login' ? 'Войти' : 'Зарегистрироваться'}</Button>
        <button type="button" className="text-button" onClick={() => setMode(mode === 'login' ? 'register' : 'login')}>
          {mode === 'login' ? 'Нет аккаунта? Зарегистрироваться' : 'Уже есть аккаунт? Войти'}
        </button>
      </form>
    </main>
  );
}
