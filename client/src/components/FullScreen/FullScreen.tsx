import "./FullScreen.css"
import Button from '../Button.tsx'
import { useQuery } from '@tanstack/react-query';
import { apiFetch, getMediaUrl } from '../../api/client';
import type { Media, StrapiSingleResponse } from '../../types/api';
import { useNavigate } from 'react-router-dom';

interface FirstScreenData {
    title?: string;
    content?: string;
    media?: Media | null;
}

export default function FullScreen() {
    const navigate = useNavigate();
    const { 
        data,
        isLoading,
        error
    } = useQuery({
        queryKey: ['first-screen'],
        queryFn: async () => {
            const json = await apiFetch<StrapiSingleResponse<FirstScreenData>>('/api/first-screen?populate=media');
            return json.data;
        }
    });

    function submitApplication() {
        navigate('/tournaments');
    }

    if (isLoading) return <div>Загрузка...</div>;
    if (error) return <div>Ошибка: {error.message}</div>;

    const media = data?.media;
    const mediaUrl = getMediaUrl(media?.url);

    return(
        <div className="fullscreen">
            {mediaUrl && (
                <div className="fullscreen__media">
                    <video 
                        className="fullscreen-video"
                        autoPlay 
                        loop 
                        muted 
                        playsInline
                    >
                        <source src={mediaUrl} type={media?.mime} />
                        Ваш браузер не поддерживает видео.
                    </video>
                </div>
            )}
            <div className="container">
                <div className="fullscreen__content">
                    <h1>{data?.title}</h1>
                    <p>{data?.content}</p>
                    <Button onClick={submitApplication}>Записаться на турнир</Button>
                </div>
            </div>
        </div>
    )
}
