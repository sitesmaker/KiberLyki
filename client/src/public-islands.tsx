import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './context/AuthProvider';
import { ToastProvider } from './context/ToastContext';
import Header from './components/Header/Header';
import StaffCarousel from './components/Staff/StaffCarousel';
import PublicTournamentList from './components/PublicTournamentList';
import TournamentDetails from './pages/TournamentDetails';
import type { StaffData } from './types/api';
import type { Tournament } from './types/domain';
import type { ReactNode } from 'react';
import './public-pages.css';

interface Props { people?: StaffData[]; tournaments?: Tournament[]; tournament?: Tournament; home?: boolean; game?: string }

export function mountPublicIslands() {
  for (const host of document.querySelectorAll<HTMLElement>('[data-island]')) {
    const props: Props = JSON.parse(host.querySelector('script[data-props]')?.textContent || '{}');
    const client = new QueryClient();
    let component: ReactNode;
    switch (host.dataset.island) {
      case 'header': component = <Header />; break;
      case 'staff': component = <StaffCarousel people={props.people ?? []} />; break;
      case 'tournaments': component = <PublicTournamentList tournaments={props.tournaments ?? []} home={Boolean(props.home)} game={props.game ?? ''} />; break;
      case 'competition':
        if (!props.tournament) continue;
        client.setQueryData(['tournament', props.tournament.documentId], { data: props.tournament });
        client.setQueryDefaults(['tournament', props.tournament.documentId], { staleTime: 30_000 });
        component = <AuthProvider><ToastProvider><TournamentDetails initialDocumentId={props.tournament.documentId} interactiveOnly /></ToastProvider></AuthProvider>;
        break;
      default: continue;
    }
    // These are independent widgets, so navigation must load the destination page.
    host.addEventListener('click', event => {
      const anchor = event.target instanceof Element ? event.target.closest('a') : null;
      if (!(anchor instanceof HTMLAnchorElement) || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || anchor.target || anchor.hasAttribute('download')) return;
      if (new URL(anchor.href).origin !== location.origin) return;
      event.preventDefault();
      location.assign(anchor.href);
    }, true);
    createRoot(host).render(<QueryClientProvider client={client}><BrowserRouter>{component}</BrowserRouter></QueryClientProvider>);
  }
}
