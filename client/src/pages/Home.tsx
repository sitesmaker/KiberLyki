import FullScreen from "../components/FullScreen/FullScreen"
import Contacts from "../components/Contacts/Contacts"
import About from "../components/About/About"
import Staff from "../components/Staff/Staff"

export default function Home() {
    return(
      <div>
        <FullScreen />
        <About />
        <Staff />
        <Contacts />
      </div>
    )
}
