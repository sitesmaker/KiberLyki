import "./About.css"
import Section from '../Section/Section.tsx';
import { useQuery } from '@tanstack/react-query';

export default function About() {
    const {
        data,
        isLoading,
        error
    } = useQuery({
        queryKey: ['about'],
        queryFn: async () => {
            const response = await fetch('http://localhost:1337/api/about?populate=image');
            if (!response.ok) throw new Error('Ошибка загрузки');
            const json = await response.json();
            return json.data;
        }
    });

    if (isLoading) return <div>Загрузка...</div>;
    if (error) return <div>Ошибка: {error.message}</div>;

    const image = data?.image;
    const imageUrl = image?.url ? `http://localhost:1337${image.url}` : '';

    return(
        <Section className="container about">
            <div className="about__wrapper">
                <div className="about__content">
                    <h2>{data?.title}</h2>
                    <p>
                        {data?.description}
                    </p>
                </div>
                {imageUrl && (
                    <div className="about__image">
                        <img src={imageUrl} alt="" />
                    </div>
                )}
            </div>
        </Section>
    )
}