import './Article.css'

export default function Article({articleData}) {
    const mediaUrl = articleData?.cover?.url ? `http://localhost:1337${articleData.cover?.url}` : '';

    console.log(articleData)

    return(
        <div className="article">
            <img src={mediaUrl} alt="" className='article__image' />
            <div className="article__content">
                <div className="article__title">{articleData.title}</div>
                <p>{articleData.description}</p>
            </div>
        </div>
    )
}