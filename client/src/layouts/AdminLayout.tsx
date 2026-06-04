import { Outlet, Link } from 'react-router-dom';

export default function AdminLayout() {
  return (
    <div className="admin-panel">
      <aside className="sidebar">
        <Link to="/admin/users">Пользователи</Link>
        <Link to="/admin/posts">Посты</Link>
        <Link to="/admin/settings">Настройки</Link>
      </aside>
      <div className="content">
        <Outlet />
      </div>
    </div>
  );
}