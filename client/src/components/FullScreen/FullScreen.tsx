import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { FiArrowDown, FiArrowUpRight } from 'react-icons/fi';
import { apiFetch, getMediaUrl } from '../../api/client';
import type { Media, ApiSingleResponse } from '../../types/api';
import { club } from '../../config/club';
import './FullScreen.css';

interface FirstScreenData {
  title?: string;
  content?: string;
  media?: Media | null;
}

export default function FullScreen() {
  const { data } = useQuery({
    queryKey: ['first-screen'],
    queryFn: async () => (await apiFetch<ApiSingleResponse<FirstScreenData>>('/api/first-screen?populate=media')).data,
  });
  const media = data?.media;
  const mediaUrl = getMediaUrl(media?.url);
  const lines = (data?.title || 'ТВОЯ ИГРА.\nТВОЯ КОМАНДА.').split('\n');
  const isVideo = Boolean(mediaUrl && !media?.mime?.startsWith('image/'));

  return (
    <section className="fullscreen" aria-label="КиберЛуки — киберспортивное сообщество">
      {mediaUrl && <div className="fullscreen__media">{!isVideo ? <img src={mediaUrl} alt="" /> : <video autoPlay loop muted playsInline preload="auto" controls={false} disablePictureInPicture aria-hidden="true" tabIndex={-1}><source src={mediaUrl} type={media?.mime} /></video>}</div>}
      <div className="fullscreen__grid" aria-hidden="true" />
      <div className="container fullscreen__body">
        <div className="fullscreen__eyebrow"><span className="fullscreen__dot" /> КИБЕРЛУКИ <span className="fullscreen__divider">/</span> {club.city}</div>
        <div className="fullscreen__content">
          <h1>{lines.map((line, index) => <span key={index} className={index === lines.length - 1 && lines.length > 1 ? 'fullscreen__accent' : ''}>{line}</span>)}</h1>
          <p>{data?.content || 'Собирай состав. Принимай вызов. Выходи в игру вместе с киберспортивным сообществом Великих Лук.'}</p>
          <div className="fullscreen__actions"><Link to="/tournaments" className="home-button home-button--mint">Найти турнир <FiArrowUpRight /></Link><Link to="/cabinet" className="home-button home-button--outline">Создать команду <FiArrowUpRight /></Link></div>
        </div>
        <div className="fullscreen__bottom"><a href="#club" className="fullscreen__scroll"><FiArrowDown /> Знакомься с клубом</a><div className="fullscreen__bottom-right"><span>ИГРА / КОМАНДА / ПОБЕДА</span></div></div>
      </div>
    </section>
  );
}
