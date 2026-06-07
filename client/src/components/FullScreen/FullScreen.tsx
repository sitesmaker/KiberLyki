import "./FullScreen.css"
import Button from '../Button.tsx'
import { useQuery } from '@tanstack/react-query';

export default function FullScreen() {
    const { 
        data,
        isLoading,
        error
    } = useQuery({
        queryKey: ['first-screen'],
        queryFn: async () => {
            const response = await fetch('http://localhost:1337/api/first-screen?populate=media');
            if (!response.ok) throw new Error('Ошибка загрузки');
            const json = await response.json();
            return json.data;
        }
    });

    function submitApplication() {
        document.querySelector('#submit')?.scrollIntoView();
    }

    if (isLoading) return <div>Загрузка...</div>;
    if (error) return <div>Ошибка: {error.message}</div>;

    const media = data?.media;
    const mediaUrl = media?.url ? `http://localhost:1337${media.url}` : '';

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
            <div className="fullscreen__content">
                <h1>{data?.title}</h1>
                <p>{data?.content}</p>
                <Button onClick={submitApplication}>Записаться на турнир</Button>
            </div>
        </div>
    )
}