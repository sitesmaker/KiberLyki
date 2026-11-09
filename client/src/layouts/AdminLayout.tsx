import { getApiUrl } from '../api/client';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/useAuth';

export default function AdminLayout() {
  const { user } = useAuth();
  if (!['admin', 'manager', 'content_manager', 'organizer'].includes(user?.role?.type ?? '')) return <Navigate to="/cabinet" replace />;
  return (
    <div className="container">
      <h1>Управление клубом</h1>
      <p>В панели доступны разделы, разрешённые вашей роли.</p>
      <a href={getApiUrl('/admin/')}>Открыть панель управления</a>
    </div>
  );
}
