import "./About.css"
import Section from '../Section/Section.tsx';
import { useQuery } from '@tanstack/react-query';
import { apiFetch, getMediaUrl } from '../../api/client';
import type { Media, StrapiSingleResponse } from '../../types/api';

interface AboutData {
    title?: string;
    description?: string;
    image?: Media | null;
}

export default function About() {
    const {
        data,
        isLoading,
        error
    } = useQuery({
        queryKey: ['about'],
        queryFn: async () => {
            const json = await apiFetch<StrapiSingleResponse<AboutData>>('/api/about?populate=image');
            return json.data;
        }
    });

    if (isLoading) return <div>Загрузка...</div>;
    if (error) return <div>Ошибка: {error.message}</div>;

    const image = data?.image;
    const imageUrl = getMediaUrl(image?.url);

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
