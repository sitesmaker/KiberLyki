import "./Employee.css";

export default function Employee({ data }) {
    // Получаем URL фото
    const photoUrl = data.photo?.url 
        ? `http://localhost:1337${data.photo.url}` 
        : '';

    return(
        <div className="employee">
            {photoUrl && (
                <img 
                    src={photoUrl} 
                    alt={data.name || 'Фото сотрудника'}
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