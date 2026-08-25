import "./Employee.css";
import { getMediaUrl } from '../../api/client';
import type { StaffData } from '../../types/api';

export default function Employee({ data }: { data: StaffData }) {
    const photoUrl = getMediaUrl(data.photo?.url);

    return(
        <div className="employee">
            {photoUrl && (
                <img 
                    src={photoUrl} 
                    alt={`Фото: ${data.firstName} ${data.lastName}`}
                    style={{ width: 100, height: 100, objectFit: 'cover' }}
                />
            )}
            <div>
                <h3>{data.lastName} {data.firstName}</h3>
                {/* {data.position && <p>{data.position}</p>}
                {data.description && <p>{data.description}</p>} */}
            </div>
        </div>
    );
}
