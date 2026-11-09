import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useLocation } from 'react-router-dom';
import { apiFetch } from '../api/client';

interface Metadata {
  title: string;
  description: string;
  canonical: string;
  robots: string;
  image: string | null;
  siteName: string;
  type: string;
  ogTitle: string;
  ogDescription: string;
  structuredData: Record<string, unknown> | null;
}

export default function Seo() {
  const { pathname } = useLocation();
  const { data } = useQuery({
    queryKey: ['seo', pathname],
    queryFn: () => apiFetch<{ data: Metadata }>(`/api/seo?${new URLSearchParams({ path: pathname })}`),
    staleTime: 30_000,
  });

  useEffect(() => {
    if (!data) return;
    const seo = data.data;
    document.title = seo.title;
    function meta(key: 'name' | 'property', name: string, value: string | null) {
      const existing = document.head.querySelector<HTMLMetaElement>(`meta[${key}="${name}"]`);
      if (!value) { existing?.remove(); return; }
      const tag = existing ?? document.createElement('meta');
      tag.setAttribute(key, name);
      tag.content = value;
      if (!existing) document.head.append(tag);
    }
    meta('name', 'description', seo.description);
    meta('name', 'robots', seo.robots);
    meta('property', 'og:title', seo.ogTitle);
    meta('property', 'og:description', seo.ogDescription);
    meta('property', 'og:url', seo.canonical);
    meta('property', 'og:type', seo.type);
    meta('property', 'og:site_name', seo.siteName);
    meta('property', 'og:locale', 'ru_RU');
    meta('property', 'og:image', seo.image);
    meta('name', 'twitter:card', seo.image ? 'summary_large_image' : 'summary');
    meta('name', 'twitter:title', seo.ogTitle);
    meta('name', 'twitter:description', seo.ogDescription);
    meta('name', 'twitter:image', seo.image);
    const canonical = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]') ?? document.createElement('link');
    canonical.rel = 'canonical';
    canonical.href = seo.canonical;
    if (!canonical.isConnected) document.head.append(canonical);
    const existing = document.getElementById('site-structured-data');
    if (seo.structuredData) {
      const structured = existing ?? document.createElement('script');
      structured.id = 'site-structured-data';
      structured.setAttribute('type', 'application/ld+json');
      structured.textContent = JSON.stringify(seo.structuredData);
      if (!structured.isConnected) document.head.append(structured);
    } else existing?.remove();
  }, [data]);

  return null;
}
