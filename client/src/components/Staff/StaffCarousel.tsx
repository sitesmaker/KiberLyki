import { useCallback, useEffect, useRef, useSyncExternalStore } from 'react';
import { FiArrowLeft, FiArrowRight } from 'react-icons/fi';
import Employee from '../Employee/Employee';
import type { StaffData } from '../../types/api';

function subscribeViewport(callback: () => void) {
  const queries = [window.matchMedia('(max-width: 1000px)'), window.matchMedia('(max-width: 640px)')];
  queries.forEach(query => query.addEventListener('change', callback));
  return () => queries.forEach(query => query.removeEventListener('change', callback));
}
const slidesPerView = () => window.matchMedia('(max-width: 640px)').matches ? 1 : window.matchMedia('(max-width: 1000px)').matches ? 2 : 3;
function subscribeMotion(callback: () => void) {
  const query = window.matchMedia('(prefers-reduced-motion: reduce)');
  query.addEventListener('change', callback);
  return () => query.removeEventListener('change', callback);
}
const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export default function StaffCarousel({ people }: { people: StaffData[] }) {
  const track = useRef<HTMLUListElement>(null);
  const perView = useSyncExternalStore(subscribeViewport, slidesPerView, () => 3);
  const reducedMotion = useSyncExternalStore(subscribeMotion, prefersReducedMotion, () => false);
  const maxIndex = Math.max(0, people.length - perView);

  const readIndex = useCallback(() => {
    const element = track.current;
    if (!element || element.children.length < 2) return 0;
    const first = element.children[0] as HTMLElement;
    const second = element.children[1] as HTMLElement;
    return Math.min(maxIndex, Math.max(0, Math.round(element.scrollLeft / (second.offsetLeft - first.offsetLeft))));
  }, [maxIndex]);

  const goTo = useCallback((next: number, instant = false) => {
    const element = track.current;
    const first = element?.children[0] as HTMLElement | undefined;
    const slide = element?.children[Math.min(maxIndex, Math.max(0, next))] as HTMLElement | undefined;
    if (element && first && slide) element.scrollTo({ left: slide.offsetLeft - first.offsetLeft, behavior: instant || reducedMotion ? 'instant' : 'smooth' });
  }, [maxIndex, reducedMotion]);

  const move = useCallback((direction: number) => {
    const next = readIndex() + direction;
    goTo(next > maxIndex ? 0 : next < 0 ? maxIndex : next);
  }, [readIndex, goTo, maxIndex]);

  useEffect(() => { goTo(readIndex(), true); }, [perView, goTo, readIndex]);
  return <section className="home-section container staff" id="people" aria-labelledby="club-people-title" aria-roledescription="карусель">
    <div className="home-section-heading"><div><p className="home-kicker">05 / ЛЮДИ КИБЕРЛУК</p><h2 id="club-people-title">За каждой игрой<br /><span>стоят люди.</span></h2></div><p className="home-section-note">Знакомься с теми, кто создаёт клуб,<br />проводит турниры и развивает сообщество.</p></div>
    <ul ref={track} className="staff__track" aria-label="Люди клуба" tabIndex={0}
      onKeyDown={event => { if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') { event.preventDefault(); move(event.key === 'ArrowRight' ? 1 : -1); } }}>
      {people.map(person => <li className="staff__slide" key={person.documentId}><Employee data={person} /></li>)}
    </ul>
    {maxIndex > 0 && <div className="staff__controls"><div className="staff__buttons">
      <button type="button" aria-label="Предыдущий человек" onClick={() => move(-1)}><FiArrowLeft /></button>
      <button type="button" aria-label="Следующий человек" onClick={() => move(1)}><FiArrowRight /></button>
    </div></div>}
  </section>;
}
