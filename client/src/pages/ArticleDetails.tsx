import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { apiFetch, getMediaUrl } from '../api/client';
import type { ArticleData, StrapiListResponse, StrapiSingleResponse } from '../types/api';

async function getArticle(identifier: string) {
  const query = new URLSearchParams({
    'filters[slug][$eq]': identifier,
    'populate[0]': 'cover',
    'populate[1]': 'gallery',
  });
  const bySlug = await apiFetch<StrapiListResponse<ArticleData>>(`/api/articles?${query}`);
  if (bySlug.data[0]) return bySlug.data[0];
  const byId = await apiFetch<StrapiSingleResponse<ArticleData>>(`/api/articles/${identifier}?populate=*`);
  return byId.data;
}

export default function ArticleDetails() {
  const { identifier = '' } = useParams();
  const query = useQuery({ queryKey: ['article', identifier], queryFn: () => getArticle(identifier), enabled: Boolean(identifier) });
  if (query.isLoading) return <div className="page-message">Загрузка новости...</div>;
  if (!query.data) return <div className="page-message">Новость не найдена</div>;
  const article = query.data;

  return (
    <article className="article-page container">
      <Link to="/articles">← Все новости</Link>
      <header><span className="eyebrow">Новости</span><h1>{article.title}</h1><p>{article.description}</p><small>{new Date(article.publishedAt ?? article.createdAt).toLocaleDateString('ru-RU')}{article.author ? ` · ${article.author}` : ''}</small></header>
      {article.cover && <img className="article-page__cover" src={getMediaUrl(article.cover.url)} alt={article.title} />}
      <div className="rich-text">{article.content || article.description}</div>
      {article.gallery && article.gallery.length > 0 && <div className="article-gallery">{article.gallery.map((image) => <img key={image.id} src={getMediaUrl(image.url)} alt={image.alternativeText || article.title} />)}</div>}
    </article>
  );
}
