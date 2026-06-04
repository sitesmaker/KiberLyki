import "./FullScreen.css"
import Button from '../Button.tsx'

export default function FullScreen(props) {
    function submitApplication() {
        console.log(document.querySelector('#submit-application'))
        document.querySelector('#submit')?.scrollIntoView();
    }

    return(
        <div className="fullscreen">
            <h1>{props.children}</h1>
            <Button onClick={submitApplication}>Записаться на турнир</Button>
        </div>
    )
}