import FullScreen from "../components/FullScreen/FullScreen"
import TournamentApplication from "../components/TournamentApplication/TournamentApplication"
import Contacts from "../components/Contacts/Contacts"
import About from "../components/About/About"
import Staff from "../components/Staff/Staff"

export default function Home() {
    return(
      <div>
        <FullScreen />
        <About />
        <Staff />
        <TournamentApplication />
        <Contacts />
      </div>
    )
}