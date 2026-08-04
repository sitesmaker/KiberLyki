import { useInfiniteQuery } from '@tanstack/react-query';
import { useRef, useEffect, useCallback } from 'react';
import "./ArticlesList.css";
import Article from '../Article/Article.tsx';
import Section from '../Section/Section.tsx';

export default function ArticlesList() {
  const observerRef = useRef(null);
  const loadMoreRef = useRef(null);

  // Используем useInfiniteQuery вместо useQuery
  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isLoading,
    isError,
    error,
  } = useInfiniteQuery({
    queryKey: ['articles'],
    queryFn: async ({ pageParam = 1 }) => {
      const response = await fetch(
        `http://localhost:1337/api/articles?` + new URLSearchParams({
          'pagination[page]': pageParam,
          'pagination[pageSize]': 6,
          'populate': 'cover',
          'sort[0]': 'createdAt:desc',
        })
      );
      
      if (!response.ok) throw new Error('Ошибка загрузки');
      
      const json = await response.json();
      return {
        data: json.data,
        meta: json.meta,
        nextPage: pageParam < json.meta.pagination.pageCount ? pageParam + 1 : undefined,
      };
    },
    getNextPageParam: (lastPage) => lastPage.nextPage,
    initialPageParam: 1,
    staleTime: 5 * 60 * 1000, // 5 минут
  });

  // Callback для Intersection Observer
  const handleObserver = useCallback(
    (entries) => {
      const target = entries[0];
      if (target.isIntersecting && hasNextPage && !isFetchingNextPage) {
        fetchNextPage();
      }
    },
    [fetchNextPage, hasNextPage, isFetchingNextPage]
  );

  // Настройка Observer
  useEffect(() => {
    const element = loadMoreRef.current;
    const option = {
      root: null,
      rootMargin: '100px',
      threshold: 0.1,
    };

    observerRef.current = new IntersectionObserver(handleObserver, option);
    
    if (element) {
      observerRef.current.observe(element);
    }

    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, [handleObserver]);

  // Функция для получения всех статей из всех страниц
  const allArticles = data?.pages.flatMap(page => page.data) || [];

  if (isLoading) {
    return (
      <Section>
        <div className="container">
          <h2>Новости</h2>
          <div className="articles-loading">
            <div className="spinner"></div>
            <p>Загрузка новостей...</p>
          </div>
        </div>
      </Section>
    );
  }

  if (isError) {
    return (
      <Section>
        <div className="container">
          <h2>Новости</h2>
          <div className="articles-error">
            <p>Ошибка загрузки новостей: {error?.message}</p>
          </div>
        </div>
      </Section>
    );
  }

  return (
    <Section>
      <div className="container">
        <h2>Новости</h2>
        <div className="articles-list">
          {allArticles.map((article, index) => (
            <Article 
              key={article.id} 
              articleData={article}
              // Добавляем анимацию появления для каждой карточки
              style={{
                animationDelay: `${index % 6 * 0.1}s`,
              }}
            />
          ))}
        </div>

        {/* Элемент для отслеживания прокрутки */}
        <div ref={loadMoreRef} className="load-more-trigger" />

        {/* Индикатор загрузки следующих страниц */}
        {isFetchingNextPage && (
          <div className="loading-more">
            <div className="spinner-small"></div>
            <p>Загрузка еще новостей...</p>
          </div>
        )}

        {/* Сообщение о конце списка */}
        {!hasNextPage && allArticles.length > 0 && (
          <div className="end-of-list">
            <p>🎉 Все новости загружены</p>
          </div>
        )}
      </div>
    </Section>
  );
}