import { useQuery } from '@tanstack/react-query';
import "./ArticlesList.css"
import Button from '../Button.tsx'
import Article from '../Article/Article.tsx';

export default function ArticlesList() {
  const { 
    data, 
    isLoading, 
    error,
    refetch
  } = useQuery({
    queryKey: ['articles'],
    queryFn: async () => {
      const response = await fetch('http://localhost:1337/api/articles?populate=cover');
      if (!response.ok) throw new Error('Ошибка загрузки');
      const json = await response.json();
      return json.data;
    }
  });

  if (isLoading) return <div>Загрузка статей...</div>;
  if (error) return <div>Ошибка: {error.message}</div>;

  return (
    <div>
      <Button onClick={refetch}>Обновить</Button>
      <div className="articles-list">
        {data.map(article => (
          <Article key={article.id} articleData={article}  />
        ))}
      </div>
    </div>
  );
}