// components/Toast/Toast.jsx
import { useEffect, useState } from 'react';
import './Toast.css';
import type { ToastType } from '../../context/toast-context';

interface ToastProps {
  message: string;
  type?: ToastType;
  duration?: number;
  onClose: () => void;
}

const Toast = ({ message, type = 'success', duration = 3000, onClose }: ToastProps) => {
  const [isVisible, setIsVisible] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => {
      setIsVisible(false);
      setTimeout(onClose, 300); // Даем время на анимацию исчезновения
    }, duration);

    return () => clearTimeout(timer);
  }, [duration, onClose]);

  if (!isVisible) return null;

  return (
    <div className={`toast toast-${type}`} role="alert">
      <div className="toast-icon">
        {type === 'success' && '✅'}
        {type === 'error' && '❌'}
        {type === 'info' && 'ℹ️'}
        {type === 'warning' && '⚠️'}
      </div>
      <div className="toast-message">{message}</div>
      <button 
        className="toast-close" 
        onClick={() => {
          setIsVisible(false);
          setTimeout(onClose, 300);
        }}
      >
        ✕
      </button>
    </div>
  );
};

export default Toast;
