import './Article.css'

export default function Article({articleData}) {

    return(
        <div className="article">
            <img src="" alt="" />
            <h3>{articleData.title}</h3>
            <p>{articleData.description}</p>
        </div>
    )
}