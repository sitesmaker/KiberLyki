import { useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import './PublicLayotStyles.css';
import Header from '../../components/Header/Header'
import Footer from '../../components/Footer/Footer'
import Seo from '../../components/Seo'

export default function PublicLayout() {
  const { pathname, hash } = useLocation();
  useEffect(() => {
    if (!hash) { window.scrollTo({ top: 0, left: 0, behavior: 'instant' }); return; }
    const id = decodeURIComponent(hash.slice(1));
    const scroll = () => {
      const target = document.getElementById(id);
      if (!target) return false;
      target.scrollIntoView({ behavior: 'instant', block: 'start' });
      return true;
    };
    if (scroll()) return;
    const observer = new MutationObserver(() => { if (scroll()) observer.disconnect(); });
    observer.observe(document.getElementById('root')!, { childList: true, subtree: true });
    return () => observer.disconnect();
  }, [pathname, hash]);
  return (
    <div className={`public-layout${pathname === '/' ? ' public-layout--hero' : ''}`}>
      <Seo />
      <Header key={pathname} />
      <main>
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}
