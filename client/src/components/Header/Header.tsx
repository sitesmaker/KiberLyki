import { Link } from 'react-router-dom';
import './Header.css';

export default function Header() {

    return(
        <header>
            <div className="container">
                <Link to="/" className="logo-main">
                    <span>Cyber</span>luki
                </Link>
                <nav>
                    <Link to="/">Главная</Link>
                    <Link to="/about">О нас</Link>
                    <Link to="/gallery">Галерея</Link>
                    <Link to="/articles">Новости</Link>
                    <Link to="/teams">Команды</Link>
                    <Link to="/callback">Записаться</Link>
                    <Link to="/contacts">Контакты</Link>
                </nav>
            </div>
        </header>
    )
}