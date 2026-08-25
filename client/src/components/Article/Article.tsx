import './Article.css'
import { Link } from 'react-router-dom';
import type { CSSProperties } from 'react';
import type { ArticleData } from '../../types/api';
import { getMediaUrl } from '../../api/client';

interface ArticleProps {
    articleData: ArticleData;
    style?: CSSProperties;
}

export default function Article({ articleData, style }: ArticleProps) {
    const mediaUrl = getMediaUrl(articleData.cover?.url);
    const articlePath = `/articles/${articleData.slug || articleData.documentId}`;

    return(
        <article className="article" style={style}>
            <Link to={articlePath}>
            {mediaUrl && <img src={mediaUrl} alt={articleData.cover?.alternativeText || articleData.title} className='article__image' />}
            <div className="article__content">
                <div className="article__title">{articleData.title}</div>
                <p>{articleData.description}</p>
            </div>
            </Link>
        </article>
    )
}
