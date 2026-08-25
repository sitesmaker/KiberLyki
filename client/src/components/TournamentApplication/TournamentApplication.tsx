// TournamentApplication.jsx
import { useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { useToast } from '../../context/useToast';
import Section from "../Section/Section";
import Button from "../Button";
import ursaImg from "../../assets/ursa.png";
import kripImg from "../../assets/krip.png";
import './TournamentApplication.css';

import { apiFetch } from '../../api/client';

interface ApplicationFormData {
  firstName: string;
  lastName: string;
  nickname: string;
  email: string;
  photo: File | null;
}

export default function TournamentApplication() {
  const { showToast } = useToast();
  const [formData, setFormData] = useState<ApplicationFormData>({
    firstName: '',
    lastName: '',
    nickname: '',
    email: '',
    photo: null,
  });
  const [isLoading, setIsLoading] = useState(false);
  const fileInputRef = useRef<HTMLInputElement | null>(null);

  const handleInputChange = (e: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
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

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      let photoId = null;

      // Загрузка фото
      if (formData.photo) {
        const uploadFormData = new FormData();
        uploadFormData.append('files', formData.photo);

        const uploadResult = await apiFetch<Array<{ id: number }>>('/api/upload', {
          method: 'POST',
          body: uploadFormData,
        });
        photoId = uploadResult[0]?.id ?? null;
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

      await apiFetch('/api/participants', {
        method: 'POST',
        body: JSON.stringify(participantData),
      });

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
      
      if (fileInputRef.current) fileInputRef.current.value = '';

    } catch (error) {
      console.error('Ошибка:', error);
      showToast(`Ошибка: ${error instanceof Error ? error.message : 'неизвестная ошибка'}`, 'error');
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
        
        <form id="tournament-application" className="tourney-app-form" onSubmit={handleSubmit}>
          <div className="tourney-app-form__content">
            <input 
              ref={fileInputRef}
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
