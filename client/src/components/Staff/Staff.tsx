import { useQuery } from '@tanstack/react-query';
import Section from '../Section/Section.tsx';
import Employee from "../Employee/Employee.tsx";

export default function Staff() {
    const {
        data,
        isLoading,
        error
    } = useQuery({
        queryKey: ['staff'],
        queryFn: async () => {
            const response = await fetch('http://localhost:1337/api/staffs?populate=photo');
            if (!response.ok) throw new Error('Ошибка загрузки');
            const json = await response.json();
            return json.data; // Это массив сотрудников
        }
    });

    if (isLoading) return <div>Загрузка...</div>;
    if (error) return <div>Ошибка: {error.message}</div>;
    
    // Проверка, что data - это массив
    if (!data || !Array.isArray(data) || data.length === 0) {
        return <div>Сотрудники не найдены</div>;
    }

    return (
        <Section className="staff">
            {data.map((item) => (
                <Employee data={item} key={item.id} />
            ))}
        </Section>
    );
}