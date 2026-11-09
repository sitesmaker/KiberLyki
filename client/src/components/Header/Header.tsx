import { useRef, useState, useSyncExternalStore } from 'react';
import { Link, NavLink } from 'react-router-dom';
import './Header.css';

function subscribeToScroll(onChange: () => void) {
    window.addEventListener('scroll', onChange, { passive: true });
    return () => window.removeEventListener('scroll', onChange);
}

const getScrolled = () => window.scrollY > 24;

export default function Header() {
    const [menuOpen, setMenuOpen] = useState(false);
    const scrolled = useSyncExternalStore(subscribeToScroll, getScrolled, () => false);
    const menuButton = useRef<HTMLButtonElement>(null);
    const closeMenu = () => setMenuOpen(false);
    return(
        <header className={`site-header${scrolled ? ' site-header--scrolled' : ''}${menuOpen ? ' site-header--menu-open' : ''}`} onKeyDown={event => { if (event.key === 'Escape' && menuOpen) { closeMenu(); menuButton.current?.focus(); } }}>
            <div className="container site-header__inner">
                <Link to="/" reloadDocument className="logo-main" onClick={closeMenu}>
                    <span>Cyber</span>luki
                </Link>
                <button ref={menuButton} type="button" className="site-header__toggle" aria-expanded={menuOpen} aria-controls="site-navigation" onClick={() => setMenuOpen(!menuOpen)}>{menuOpen ? 'Закрыть ✕' : 'Меню ☰'}</button>
                <nav id="site-navigation" className={`site-header__nav${menuOpen ? ' site-header__nav--open' : ''}`} aria-label="Основная навигация">
                    <NavLink to="/" reloadDocument end onClick={closeMenu}>Главная</NavLink>
                    <NavLink to="/articles" reloadDocument onClick={closeMenu}>Новости</NavLink>
                    <NavLink to="/teams" reloadDocument onClick={closeMenu}>Команды</NavLink>
                    <NavLink to="/tournaments" reloadDocument onClick={closeMenu}>Турниры</NavLink>
                    <NavLink to="/cabinet" reloadDocument onClick={closeMenu}>Кабинет</NavLink>
                </nav>
            </div>
        </header>
    )
}
