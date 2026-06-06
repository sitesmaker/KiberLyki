import FullScreen from "../components/FullScreen/FullScreen"
import { useState, useEffect } from 'react';
import axios from 'axios';


export default function Home() {
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);

  // Когда страница загружается, получаем статьи
  useEffect(() => {
    axios.get('http://localhost:1337/api/articles?populate=cover')
      .then(response => {
        setArticles(response.data.data);
        setLoading(false);
      })
      .catch(error => {
        console.error('Ошибка:', error);
        setLoading(false);
      });
  }, []);

  if (loading) {
    return <div>Загрузка...</div>;
  }

    return(
        <div>
        <FullScreen>
             КиберЛуки | Центр развития компьютерного спорта
        </FullScreen>

      {articles.map(article => {
        // Получаем URL картинки cover
        // Путь: cover.url (смотри на твои данные)
        const imageUrl = article.cover?.url;
        
        // Полный URL (Strapi хранит относительный путь)
        const fullImageUrl = imageUrl 
          ? `http://localhost:1337${imageUrl}` 
          : null;

        return (
          <div key={article.id}>
            {/* Выводим картинку, если она есть */}
            {fullImageUrl && (
              <img 
                src={fullImageUrl} 
                alt={article.title}
                style={{ maxWidth: '300px' }}
              />
            )}
            
            <h2>{article.title}</h2>
            <p>{article.description}</p>
            <hr />
          </div>
        );
      })}
    </div>
    )
}