import { Outlet, Link } from 'react-router-dom';
import './PublicLayotStyles.css';

export default function PublicLayout() {
  return (
    <div className="public-layout">
      <header>
        <Link to="/">Главная</Link>
        <Link to="/pricing">Цены</Link>
      </header>
      <main>
        <Outlet />
      </main>
      <footer>Красивый подвал</footer>
    </div>
  );
}