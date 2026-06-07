import { Outlet, Link } from 'react-router-dom';
import './PublicLayotStyles.css';
import Header from '../../components/Header/Header'

export default function PublicLayout() {
  return (
    <div className="public-layout">
      <Header />
      <main>
        <Outlet />
      </main>
      <footer>Красивый подвал</footer>
    </div>
  );
}