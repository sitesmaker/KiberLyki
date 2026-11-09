import { Link } from 'react-router-dom';
import { FaVk } from 'react-icons/fa';
import { FiArrowUpRight } from 'react-icons/fi';
import { club } from '../../config/club';
import './Footer.css';

export default function Footer() {
  return (
    <footer className="footer">
      <div className="container footer__grid">
        <div className="footer__item"><Link reloadDocument to="/" className="logo-main"><span>Cyber</span>luki</Link><p className="footer__description">Киберспортивное сообщество<br />{club.city}</p></div>
        <div className="footer__item"><div className="footer__item-title">На платформе</div><nav aria-label="Навигация в футере"><Link reloadDocument to="/">Главная</Link><Link reloadDocument to="/articles">Новости</Link><Link reloadDocument to="/teams">Команды</Link><Link reloadDocument to="/tournaments">Турниры</Link><Link reloadDocument to="/cabinet">Кабинет</Link></nav></div>
        <div className="footer__item"><div className="footer__item-title">Будь на связи</div><a href={club.communityUrl} target="_blank" rel="noopener noreferrer">Сообщество ВКонтакте ↗</a><a href={club.founderUrl} target="_blank" rel="noopener noreferrer">Связаться с создателем ↗</a></div>
        <div className="footer__item"><div className="footer__item-title">Твоя игра начинается здесь</div><p>Собери команду.<br />Выбери турнир.<br />Покажи свой уровень.</p><a href={club.communityUrl} target="_blank" rel="noopener noreferrer" className="footer__community" aria-label="Открыть сообщество КиберЛуки ВКонтакте"><FaVk /><FiArrowUpRight /></a></div>
      </div>
      <div className="container footer__bottom"><span>КиберЛуки · {club.city}</span><span>С уважением к игре и друг к другу.</span></div>
    </footer>
  );
}
