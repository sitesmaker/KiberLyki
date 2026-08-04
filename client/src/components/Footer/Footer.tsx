import { Link } from 'react-router-dom';
import { FaVk } from "react-icons/fa";
import { FaTelegramPlane } from "react-icons/fa";
import './Footer.css'

export default function Footer() {

    return(
        <footer className="footer">
            <div className="container footer__grid">
                <div className="footer__item">
                    <Link to="/" className="logo-main">
                        <span>Cyber</span>luki
                    </Link>
                </div>
                <div className="footer__item">
                    <div className="footer__item-title">Навигация</div>
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
                <div className="footer__item">
                    <div className="footer__item-title">Контакты</div>
                    <div className="footer__item-subtitle">Наш адрес</div>
                    <p>г. Великие луки</p>
                    <div className="footer__item-subtitle">Режим работы</div>
                    <p>Пн-Пт 09:00 - 18:00</p>
                    <p>Сб-Вс Выходной</p>
                </div>
                <div className="footer__item">
                    <a href="mailto:mailtest@mail.ru">mailtest@mail.ru</a>
                    <a href="tel:+79999999999">+79999999999</a>
                    <div className="footer__item-subtitle">Мы в соц. сетях</div>
                    <div className='social'>
                        <a href="" className='link--social'><FaVk /></a>
                        <a href="" className='link--social'><FaTelegramPlane /></a>
                    </div>
                </div>
            </div>
        </footer>
    )
}