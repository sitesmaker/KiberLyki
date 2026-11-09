import "./Employee.css";
import { getMediaUrl } from '../../api/client';
import type { StaffData } from '../../types/api';
import { Link } from 'react-router-dom';
import { FiArrowUpRight } from 'react-icons/fi';

export default function Employee({ data }: { data: StaffData }) {
    const photoUrl = getMediaUrl(data.photo?.url);

    return(
        <Link to={`/club/${data.documentId}`} className="employee">
            <div className="employee__portrait">{photoUrl ? <img src={photoUrl} alt={`${data.firstName} ${data.lastName}`} loading="lazy" /> : <span>{data.firstName.slice(0, 1)}{data.lastName.slice(0, 1)}</span>}<span className="employee__role">{data.specialization || 'Команда клуба'}</span></div>
            <div className="employee__body">
                <h3>{data.firstName} {data.lastName}</h3>
                <p>{data.intro || data.description || 'Знакомься с человеком, который помогает развивать КиберЛуки.'}</p>
                <span className="employee__more">История и вклад в клуб <FiArrowUpRight /></span>
            </div>
        </Link>
    );
}
