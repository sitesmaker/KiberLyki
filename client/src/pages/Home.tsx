import FullScreen from "../components/FullScreen/FullScreen"
import ArticlesList from "../components/ArticlesList/ArticlesList"
import TournamentApplication from "../components/TournamentApplication/TournamentApplication"


export default function Home() {
    return(
      <div>
        <FullScreen />
        <ArticlesList />
        <TournamentApplication />
      </div>
    )
}