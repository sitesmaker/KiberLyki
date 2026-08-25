import { useQuery } from '@tanstack/react-query';
import Section from '../Section/Section.tsx';
import Employee from "../Employee/Employee.tsx";
import { apiFetch } from '../../api/client';
import type { StaffData, StrapiListResponse } from '../../types/api';

export default function Staff() {
    const {
        data,
        isLoading,
        error
    } = useQuery({
        queryKey: ['staff'],
        queryFn: async () => {
            const json = await apiFetch<StrapiListResponse<StaffData>>('/api/staffs?populate=photo');
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
