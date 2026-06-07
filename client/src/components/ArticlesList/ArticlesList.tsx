import { useQuery } from '@tanstack/react-query';
import "./ArticlesList.css"
import Article from '../Article/Article.tsx';
import Section from '../Section/Section.tsx';

export default function ArticlesList() {
  const { 
    data
  } = useQuery({
    queryKey: ['articles'],
    queryFn: async () => {
      const response = await fetch('http://localhost:1337/api/articles?populate=cover');
      if (!response.ok) throw new Error('Ошибка загрузки');
      const json = await response.json();
      return json.data;
    }
  });

  return (
    <Section>
      <div className="container">
        <h2>Новости</h2>
        <div className="articles-list">
          {data?.map(article => (
            <Article key={article.id} articleData={article}  />
          ))}
        </div>
      </div>
    </Section>

  );
}