import { Link } from 'react-router-dom';

export default function Header() {

    return(
        <header>
            <div className="container">
                <div className="logo">
                    Cyberluki
                </div>
                <nav>
                    <Link to="/">Главная</Link>
                    <Link to="/about">О нас</Link>
                    <Link to="/gallery">Галерея</Link>
                    <Link to="/articles">Новости</Link>
                    <Link to="/callback">Записаться</Link>
                    <Link to="/contacts">Контакты</Link>
                </nav>
            </div>
        </header>
    )
}