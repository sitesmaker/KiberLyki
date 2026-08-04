// TournamentApplication.jsx
import { useState } from 'react';
import { useToast } from '../../context/ToastContext';
import Section from "../Section/Section";
import Button from "../Button";
import ursaImg from "../../assets/ursa.png";
import kripImg from "../../assets/krip.png";
import './TournamentApplication.css';

const STRAPI_URL = 'http://localhost:1337';

export default function TournamentApplication() {
  const { showToast } = useToast();
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    nickname: '',
    email: '',
    photo: null,
  });
  const [isLoading, setIsLoading] = useState(false);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        showToast('Файл слишком большой (макс. 5MB)', 'error');
        e.target.value = '';
        return;
      }
      
      if (!file.type.startsWith('image/')) {
        showToast('Пожалуйста, загрузите изображение', 'error');
        e.target.value = '';
        return;
      }
      
      setFormData(prev => ({
        ...prev,
        photo: file
      }));
      showToast(`Выбран файл: ${file.name}`, 'success', 2000);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      let photoId = null;

      // Загрузка фото
      if (formData.photo) {
        const uploadFormData = new FormData();
        uploadFormData.append('files', formData.photo);

        const uploadResponse = await fetch(`${STRAPI_URL}/api/upload`, {
          method: 'POST',
          body: uploadFormData,
        });

        if (!uploadResponse.ok) {
          const errorText = await uploadResponse.text();
          throw new Error(`Ошибка загрузки фото: ${uploadResponse.status}`);
        }

        const uploadResult = await uploadResponse.json();
        photoId = Array.isArray(uploadResult) ? uploadResult[0]?.id : uploadResult.id;
      }

      // Создание записи
      const participantData = {
        data: {
          firstName: formData.firstName,
          lastName: formData.lastName,
          nickname: formData.nickname,
          email: formData.email,
          ...(photoId && { photo: photoId }),
        }
      };

      const response = await fetch(`${STRAPI_URL}/api/participants`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(participantData),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error?.message || 'Ошибка отправки');
      }

      // Успех!
      showToast('Вы успешно записались на турнир! 🎮', 'success');
      
      // Очищаем форму
      setFormData({
        firstName: '',
        lastName: '',
        nickname: '',
        email: '',
        photo: null,
      });
      
      const fileInput = document.querySelector('input[type="file"]');
      if (fileInput) fileInput.value = '';

    } catch (error) {
      console.error('Ошибка:', error);
      showToast(`Ошибка: ${error.message}`, 'error');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Section style={{ background: "var(--blue)" }}>
      <div className="container" style={{ display: 'flex', flexDirection: "column", alignItems: 'center' }}>
        <h2 style={{ color: '#fff', paddingRight: '200px', maxWidth: '600px', marginLeft: 'auto', marginRight: 'auto' }}>
          Не будь крипом, запишись на турнир!
        </h2>
        
        <form className="tourney-app-form" onSubmit={handleSubmit}>
          <div className="tourney-app-form__content">
            <input 
              type="text" 
              name="firstName" 
              placeholder="Имя"
              value={formData.firstName}
              onChange={handleInputChange}
              required
            />
            <input 
              type="text" 
              name="lastName" 
              placeholder="Фамилия"
              value={formData.lastName}
              onChange={handleInputChange}
              required
            />
            <input 
              type="text" 
              name="nickname" 
              placeholder="Никнейм"
              value={formData.nickname}
              onChange={handleInputChange}
              required
            />
            <input 
              type="email" 
              name="email" 
              placeholder="Email"
              value={formData.email}
              onChange={handleInputChange}
              required
            />
            <input 
              type="file" 
              name="photo"
              accept="image/*"
              onChange={handleFileChange}
            />
            
            <Button type="submit" disabled={isLoading}>
              {isLoading ? 'Отправка...' : 'Записаться'}
            </Button>
          </div>
          
          <img src={kripImg} alt="" className="krip-img" />
          <img src={ursaImg} alt="" className="ursa-img" />
        </form>
      </div>
    </Section>
  );
}