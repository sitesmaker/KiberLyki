import { useQuery } from '@tanstack/react-query';
import StaffCarousel from './StaffCarousel';
import { apiFetch } from '../../api/client';
import type { StaffData, ApiListResponse } from '../../types/api';
import './Staff.css';

export default function Staff() {
    const {
        data,
        isLoading,
        error
    } = useQuery({
        queryKey: ['staff'],
        queryFn: async () => {
            const json = await apiFetch<ApiListResponse<StaffData>>('/api/staffs?pagination[pageSize]=100');
            return json.data; // Это массив сотрудников
        }
    });

    if (isLoading || error) return null;
    
    // Проверка, что data - это массив
    if (!data || !Array.isArray(data) || data.length === 0) {
        return null;
    }

    return <StaffCarousel people={data} />;
}
