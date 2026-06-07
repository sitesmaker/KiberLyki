import Section from "../Section/Section"
import Button from "../Button"
import pudgeImg from "../../assets/pudge.png"

export default function TournamentApplication() {
    return(
        <Section>
            <div className="container">
                <h2>Не будь крипом, запишись на турнир!</h2>
                <form>
                    <input type="text" name="firstName" />
                    <input type="text" name="lastName" />
                    <input type="text" name="nickname" />
                    <input type="email" name="email" />
                    <input type="file" name="photo" />
                    <Button>Записаться</Button>
                    <img src={pudgeImg} alt="" />
                </form>
            </div>
        </Section>
    )
}